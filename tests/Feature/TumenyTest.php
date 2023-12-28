<?php

namespace Shengamo\TumenyPay\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
//use Orchestra\Testbench\TestCase;
use Shengamo\TumenyPay\Models\Order;
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
                "token"=> "abcdef123456",
                "expireAt"=> [
                    "date"=> Carbon::parse()->addHours(2)->timezone('UTC'),
                    "timezone_type"=> 3,
                    "timezone"=> "UTC"
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
        // Mock the HTTP response from the Tumeny Pay API
        Http::fake([
            config('tumeny.base_url') . '*' => Http::response([
                "token"=> "123456abcdef",
                "expireAt"=> [
                    "date"=> Carbon::parse()->addHours(2)->timezone('UTC'),
                    "timezone_type"=> 3,
                    "timezone"=> "UTC"
                ]
            ], 200)
        ]);

        Cache::put('tumeny_token', 'abcdef123456', Carbon::parse()->addHours(1)->timezone('UTC'));

        // Create an instance of the TumenyPay class
        $tumeny = new TumenyPay();

        // Execute the method that generates and caches the token
        $tumeny->getToken();

        // Check that the token is stored in the cache
        $this->assertEquals('abcdef123456', Cache::get('tumeny_token'));
    }

    public function test_sending_payment_request()
    {
        $paymentId = "120022";
        Http::fake([
            config('tumeny.base_url') . 'v1/payment' => Http::response([
                'payment' => [
                    'id' => $paymentId,
                    'amount' => 1,
                    'status' => "PENDING",
                    'message' => "PENDING",
                    ],
            ], 200)
        ]);

        Cache::put('tumeny_token', 'abcdef123456', Carbon::parse()->addHours(1)->timezone('UTC'));
        $tumeny = new TumenyPay();

        // Execute the method that generates and caches the token
        $tumeny->processPayment(1, 'default','0968666077',1,'Payment for goods',1);

//        Check to see if an order is created
        $this->assertEquals(1, ShengamoOrder::count());
        $this->assertEquals($paymentId, ShengamoOrder::first()->tx_ref);

    }

    public function test_order_status_is_updated()
    {
//        create an order with a status of pending
//
    }
}
