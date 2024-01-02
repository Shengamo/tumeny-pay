<?php

namespace Shengamo\TumenyPay;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Shengamo\TumenyPay\Models\ShengamoOrder;

class TumenyPay
{

    private string $apiKey;
    private string $apiSecret;
    private string $baseUrl;
    // Build your next great package.

    function __construct()
    {
        $this->apiKey = config('tumeny.key');
        $this->apiSecret = config('tumeny.secret');
        $this->baseUrl = config('tumeny.base_url');
    }

    public function getToken() : string|null
    {
        $token = null;
        if (!Cache::has('tumeny_token')) {
            $response = $this->generateToken();
            if($response->status() == 200){
                $body = json_decode($response->body());
                $time = Carbon::parse($body->expireAt->date)->timezone('Africa/Harare');

                Cache::put('tumeny_token', $body->token, $time);
                $token = Cache::get('tumeny_token');
            }
        }
        return $token;
    }

    protected function generateToken()
    {
        $response = Http::withHeaders([
            'apiKey'=>$this->apiKey,
            'apiSecret'=>$this->apiSecret,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . 'token');

        return $response;
    }

    public function processPayment($amount, $plan, $mobile, $qty, $description, $paymentType='mobile_money', $currency="ZMW")
    {
        if($paymentType == 'mobile_money'){
            $data = [
                'description' => $description,
                'customerFirstName' => 'firstName',
                'customerLastName' => 'lastName',
                'email' => 'email',
                'phoneNumber' => $mobile,
                'amount' => $amount*$qty,
            ];

            $this->initializePayment($data, $plan);
        }
    }

    private function initializePayment($data, $plan)
    {
        Http::fake([
            config('tumeny.base_url') . 'v1/payment' => Http::response([
                'payment' => [
                    'id' => '0005a2ea-06f5-446c-9e5a-51a3eeab93be',
                    'amount' => 1,
                    'status' => "PENDING",
                    'message' => "PENDING",
                ],
            ], 200)
        ]);

        $response = Http::withToken($this->getToken())
        ->withHeaders(
            [
                'Content-Type'=>'application/json',
            ]
        )->post($this->baseUrl . "v1/payment", $data);

        if($response->status() === 200){
            $responseData = json_decode($response->body());
            $status = "failed";

            if($responseData->payment->status=="PENDING"){
                ShengamoOrder::create([
                    'team_id'=>auth()->user()->currentTeam->id,
                    'tx_ref'=>$responseData->payment->id,
                    'plan'=>$plan,
                    'amount'=>$data['amount'],
                    'status'=>1
                ]);
                $status = "Pending";
            }
            return $status;
        }
    }

    public function verifyPayment(ShengamoOrder $order)
    {
        $response = Http::withToken($this->getToken())
            ->withHeaders(
                [
                    'Content-Type'=>'application/json',
                ]
            )->get($this->baseUrl . "v1/payment/", $order->tx_ref);

        if($response->status() === 200) {
            $responseData = json_decode($response->body());
            return $responseData->payment->status;
        }
    }
}
