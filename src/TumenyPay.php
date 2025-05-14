<?php

namespace Shengamo\TumenyPay;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Shengamo\TumenyPay\Models\ShengamoOrder;

class TumenyPay
{
    private const MAX_RETRY_ATTEMPTS = 3;
    private string $apiKey;
    private string $apiSecret;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = $this->getConfig('key');
        $this->apiSecret = $this->getConfig('secret');
        $this->baseUrl = $this->getConfig('base_url');
    }

    private function getConfig(string $key): string
    {
        return config('tumeny.' . $key);
    }

    public function processPayment($amount, $plan, $mobile, $qty, $description, $paymentType = 'mobile_money', $currency = "ZMW")
    {
        $name = explode(' ', auth()->user()->name ?? 'Fake User');
        $data = [
            'description' => $description,
            'customerFirstName' => $name[0],
            'customerLastName' => $name[1] ?? "Name",
            'email' => 'email',
            'phoneNumber' => $mobile,
            'amount' => $amount * $qty,
        ];

        $this->initializePayment($data, $plan);
    }

    private function initializePayment($data, $plan, $retryCount = 0): array
    {
        try {
            $response = Http::withToken($this->getToken())
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->baseUrl . "v1/payment", $data);

            if ($response->successful()) {

                $responseData = json_decode($response->body());

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
            }

            if ($response->status() === 401 && $retryCount < self::MAX_RETRY_ATTEMPTS) {
                Cache::forget('tumeny_token');
                Log::error('Unauthorized access, the token is invalid, regenerate token.', [
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                ]);
                return $this->initializePayment($data, $plan, $retryCount + 1);
            }

            Log::error('Payment initialization failed. HTTP request unsuccessful.', [
                'status_code' => $response->status(),
                'response' => $response->body(),
            ]);

            return ['status' => 'failed', 'message' => 'Payment initialization failed. HTTP request unsuccessful.'];
        } catch (Exception $exception) {
            Log::error('Exception occurred during payment initialization.', [
                'exception_message' => $exception->getMessage(),
                'exception_trace' => $exception->getTrace(),
            ]);
            return ['status' => 'failed', 'message' => 'An unexpected error occurred during payment initialization.'];
        }
    }

    public function getToken(): string|null
    {
        if (!Cache::has('tumeny_token')) {
            $response = Http::withHeaders([
                'apiKey' => $this->apiKey,
                'apiSecret' => $this->apiSecret,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . 'token');

            if ($response->status() === 200) {
                $body = json_decode($response->body());

                // Parse the expiration time as UTC since that's what the API provides
                $expireTimeUtc = Carbon::parse($body->expireAt->date, 'UTC');

                // Get the current time in UTC for a proper comparison
                $nowUtc = now()->setTimezone('UTC');

                // Calculate the TTL with a safety buffer, ensuring a minimum TTL
                $ttl = max(3000, $expireTimeUtc->diffInSeconds($nowUtc) - 900);

                // Log with timezone information for debugging
                Log::info('Token expiration details', [
                    'api_expiry_time_utc' => $body->expireAt->date,
                    'parsed_expiry_utc' => $expireTimeUtc->toDateTimeString(),
                    'now_utc' => $nowUtc->toDateTimeString(),
                    'now_app_timezone' => now()->toDateTimeString(),
                    'app_timezone' => config('app.timezone'),
                    'calculated_ttl' => $ttl
                ]);

                // Store token in cache with calculated TTL
                Cache::put('tumeny_token', $body->token, $ttl);

                Log::info('Token generated and cached', [
                    'token' => $body->token,
                    'ttl_seconds' => $ttl,
                    'cached_token' => Cache::get('tumeny_token')
                ]);

                return $body->token;
            } else {
                Log::error('Failed to generate a Token from Tumeny.', [
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                ]);
                return null;
            }
        }
        return Cache::get('tumeny_token');
    }

    public function verifyPayment(ShengamoOrder $order)
    {
        $response = Http::withToken($this->getToken())
            ->get($this->baseUrl . "v1/payment/" . $order->tx_ref);

        if ($response->status() === 200) {
            $responseData = json_decode($response->body());
            return $responseData->payment->status;
        }

        return $response->status() === 401 ? 'pending' : 'failed';
    }
}
