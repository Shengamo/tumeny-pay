<?php
namespace Shengamo\TumenyPay;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Shengamo\TumenyPay\Models\ShengamoOrder;

class TumenyPay
{

    private string $apiKey;
    private string $apiSecret;
    private string $baseUrl;

    function __construct()
    {
        $this->apiKey = config('tumeny.key');
        $this->apiSecret = config('tumeny.secret');
        $this->baseUrl = config('tumeny.base_url');
    }

    public function getToken() : string|null
    {
        if (!Cache::has('tumeny_token')) {
            $response = $this->generateToken();
            if($response->status() == 200){
                $body = json_decode($response->body());
                $time = Carbon::parse($body->expireAt->date)->timezone('Africa/Harare');

                Cache::put('tumeny_token', $body->token, $time);
            }
        }
        return Cache::get('tumeny_token');
    }

    protected function generateToken(): PromiseInterface|Response
    {
        return Http::withHeaders([
            'apiKey'=>$this->apiKey,
            'apiSecret'=>$this->apiSecret,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . 'token');
    }

    public function processPayment($amount, $plan, $mobile, $qty, $description, $paymentType='mobile_money', $currency="ZMW")
    {
        if($paymentType == 'mobile_money'){
            $name = explode(' ', auth()->user()->name);
            $data = [
                'description' => $description,
                'customerFirstName' => $name[0],
                'customerLastName' => $name[1] ?? "Name",
                'email' => 'email',
                'phoneNumber' => $mobile,
                'amount' => $amount*$qty,
            ];

            $this->initializePayment($data, $plan);
        }
    }

    private function initializePayment($data, $plan): array
    {

        try {
            $response = Http::withToken($this->getToken())
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->baseUrl . "v1/payment", $data);

            if ($response->successful()) {
                $responseData = json_decode($response->body());

//                if ($responseData->payment->status == "PENDING") {
                    ShengamoOrder::create([
                        'team_id' => auth()->user()->currentTeam->id,
                        'tx_ref' => $responseData->payment->id,
                        'plan' => $plan,
                        'amount' => $data['amount'],
                        'status' => 1,
                    ]);

                    Log::info('Payment initialized successfully. Status: PENDING', [
                        'team_id' => auth()->user()->currentTeam->id,
                        'tx_ref' => $responseData->payment->id,
                        'plan' => $plan,
                        'amount' => $data['amount'],
                    ]);

                    return ['status' => 'Pending', 'message' => 'Transaction is pending. Please approve on your mobile phone.'];
//                }
//
//                // Handle other response statuses or errors if needed
//                Log::error('Payment initialization failed. Unexpected status received.', [
//                    'status' => $responseData->payment->status,
//                    'response' => $response->body(),
//                ]);
//
//                return ['status' => 'failed', 'message' => 'Payment initialization failed. Unexpected status received.'];
            }

            // Handle other response statuses or errors if needed
            Log::error('Payment initialization failed. HTTP request unsuccessful.', [
                'status_code' => $response->status(),
                'response' => $response->body(),
            ]);

            return ['status' => 'failed', 'message' => 'Payment initialization failed. HTTP request unsuccessful.'];
        } catch (\Exception $exception) {
            // Log any unexpected exceptions
            Log::error('Exception occurred during payment initialization.', [
                'exception_message' => $exception->getMessage(),
                'exception_trace' => $exception->getTrace(),
            ]);

            return ['status' => 'failed', 'message' => 'An unexpected error occurred during payment initialization.'];
        }
    }


    public function verifyPayment(ShengamoOrder $order)
    {
        $response = Http::withToken($this->getToken())
            ->get($this->baseUrl . "v1/payment/".$order->tx_ref);

        if($response->status() === 200) {
            $responseData = json_decode($response->body());
            return $responseData->payment->status;
        }
    }
}
