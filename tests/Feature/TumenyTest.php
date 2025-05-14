<?php

namespace Shengamo\TumenyPay\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Shengamo\TumenyPay\Jobs\VerifyPendingOrderPayments;
use Shengamo\TumenyPay\Models\ShengamoOrder;
use Shengamo\TumenyPay\Tests\TestCase;
use Shengamo\TumenyPay\TumenyPay;

class TumenyTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_auth_token_stored_in_cache()
    {
        // Mock the HTTP response from the Tumeny Pay API
        Http::fake([
            config('tumeny.base_url') . '*' => Http::response([
                "token" => "abcdef123456",
                "expireAt" => [
                    "date" => Carbon::now()->addHours(2)->timezone('UTC'),
                    "timezone_type" => 3,
                    "timezone" => "UTC"
                ]
            ], 200)
        ]);

        // Create an instance of the TumenyPay class
        $tumeny = new TumenyPay();

        // Execute the method that generates and caches the token
        $tumeny->getToken();

        // Check that the token is stored in the cache
        $this->assertEquals('abcdef123456', Cache::get('tumeny_token'));
    }

    public function test_token_is_not_generated_if_it_already_exists()
    {
        // Store a token in the cache first
        Cache::put('tumeny_token', 'abcdef123456', Carbon::now()->addHours(1)->timezone('UTC'));

        // Mock the HTTP response from the Tumeny Pay API
        // This should never be called, but we set it up just in case
        Http::fake([
            config('tumeny.base_url') . '*' => Http::response([
                "token" => "123456abcdef", // Different token
                "expireAt" => [
                    "date" => Carbon::now()->addHours(2)->timezone('UTC'),
                    "timezone_type" => 3,
                    "timezone" => "UTC"
                ]
            ], 200)
        ]);

        // Create an instance of the TumenyPay class
        $tumeny = new TumenyPay();

        // Execute the method that generates and caches the token
        $tumeny->getToken();

        // Check that the token is still the original one stored in the cache
        $this->assertEquals('abcdef123456', Cache::get('tumeny_token'));
    }

    public function test_if_result_returns_unauthorized_regenerate_token()
    {
        // Set up the cached token
        Cache::put('tumeny_token', 'abcdef111111', Carbon::now()->addHours(1)->timezone('UTC'));

        // Mock HTTP responses for both endpoints in one fake call
        Http::fake([
            config('tumeny.base_url') . 'token' => Http::response([
                "token" => "123456abcdef",
                "expireAt" => [
                    "date" => Carbon::now()->addHours(2)->timezone('UTC'),
                    "timezone_type" => 3,
                    "timezone" => "UTC"
                ]
            ], 200),
            config('tumeny.base_url') . 'v1/payment' => Http::response([
                "type" => "https://tools.ietf.org/html/rfc2616#section-10",
                "title" => "An error occurred",
                "status" => 401,
                "detail" => "Unauthorized"
            ], 401),
            // Add this for the retry attempt with new token
            config('tumeny.base_url') . 'v1/payment' => Http::sequence()
                ->push([
                    "type" => "https://tools.ietf.org/html/rfc2616#section-10",
                    "title" => "An error occurred",
                    "status" => 401,
                    "detail" => "Unauthorized"
                ], 401)
                ->push([
                    "payment" => [
                        "id" => "0005a2ea-06f5-446c-9e5a-51a3eeab93be",
                        "status" => "pending",
                    ]
                ], 200)
        ]);

        $this->assertEquals('abcdef111111', Cache::get('tumeny_token'));

        // Sign in to create the user context needed for auth()->user()
        $this->signIn();

        // Create an instance of the TumenyPay class
        $tumeny = new TumenyPay();

        // Execute the method that should regenerate the token
        $tumeny->processPayment(1, 'Lite', '0968666077', 1, 'testing connection', 'mobile_money', "ZMW");

        // Check that the token is updated in the cache
        $this->assertEquals('123456abcdef', Cache::get('tumeny_token'));
    }

    public function test_payment_verified_as_successful()
    {
        // Mock HTTP responses
        Http::fake([
            config('tumeny.base_url') . 'token' => Http::response([
                "token" => "abcdef123456",
                "expireAt" => [
                    "date" => Carbon::now()->addHours(2)->timezone('UTC'),
                    "timezone_type" => 3,
                    "timezone" => "UTC"
                ]
            ], 200),
            config('tumeny.base_url') . 'v1/payment/*' => Http::response([
                'payment' => [
                    "id" => "0005a2ea-06f5-446c-9e5a-51a3eeab93be",
                    "amount" => 1,
                    "status" => "success",
                    "message" => "Successful"
                ],
            ], 200)
        ]);

        // Create a test order
        ShengamoOrder::create([
            'tx_ref' => "0005a2ea-06f5-446c-9e5a-51a3eeab93be",
            'plan' => "tumeny",
            'amount' => 1,
            'status' => 1
        ]);

        // Run the verification job
        $very = new VerifyPendingOrderPayments();
        $very->handle();

        // Check that the order status was updated correctly
        $this->assertEquals(2, ShengamoOrder::first()->status);
        $this->assertEquals('Success', ShengamoOrder::first()->orderStatus->status);
    }

    public function test_payment_verified_has_failed()
    {
        // Mock HTTP responses
        Http::fake([
            config('tumeny.base_url') . 'token' => Http::response([
                "token" => "abcdef123456",
                "expireAt" => [
                    "date" => Carbon::now()->addHours(2)->timezone('UTC'),
                    "timezone_type" => 3,
                    "timezone" => "UTC"
                ]
            ], 200),
            config('tumeny.base_url') . 'v1/payment/*' => Http::response([
                'payment' => [
                    "id" => "0005a2ea-06f5-446c-9e5a-51a3eeab93be",
                    "amount" => 1,
                    "status" => "failed",
                    "message" => "Failed"
                ],
            ], 200)
        ]);

        // Create a test order
        ShengamoOrder::create([
            'tx_ref' => "0005a2ea-06f5-446c-9e5a-51a3eeab93be",
            'plan' => "tumeny",
            'amount' => 1,
            'status' => 1
        ]);

        $this->assertEquals('Pending', ShengamoOrder::first()->orderStatus->status);

        // Run the verification job
        $very = new VerifyPendingOrderPayments();
        $very->handle();

        // Check that the order status was updated correctly
        $this->assertEquals(3, ShengamoOrder::first()->status);
        $this->assertEquals('Failed', ShengamoOrder::first()->orderStatus->status);
    }

    public function test_payment_verified_is_still_pending()
    {
        // Mock HTTP responses
        Http::fake([
            config('tumeny.base_url') . 'token' => Http::response([
                "token" => "abcdef123456",
                "expireAt" => [
                    "date" => Carbon::now()->addHours(2)->timezone('UTC'),
                    "timezone_type" => 3,
                    "timezone" => "UTC"
                ]
            ], 200),
            config('tumeny.base_url') . 'v1/payment/*' => Http::response([
                'payment' => [
                    "id" => "0005a2ea-06f5-446c-9e5a-51a3eeab93be",
                    "amount" => 1,
                    "status" => "pending",
                    "message" => "Pending"
                ],
            ], 200)
        ]);

        // Create a test order
        ShengamoOrder::create([
            'tx_ref' => "0005a2ea-06f5-446c-9e5a-51a3eeab93be",
            'plan' => "tumeny",
            'amount' => 1,
            'status' => 1
        ]);

        $this->assertEquals('Pending', ShengamoOrder::first()->orderStatus->status);

        // Run the verification job
        $very = new VerifyPendingOrderPayments();
        $very->handle();

        // Check that the order status remains unchanged
        $this->assertEquals(1, ShengamoOrder::first()->status);
        $this->assertEquals('Pending', ShengamoOrder::first()->orderStatus->status);
    }
}
