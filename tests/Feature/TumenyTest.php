<?php

namespace Shengamo\TumenyPay\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
//use Orchestra\Testbench\TestCase;
use Shengamo\TumenyPay\Jobs\VerifyPendingOrderPayments;
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
        $tumeny->processPayment(1, 'default','0968666077',1,'Payment for goods');

//        Check to see if an order is created
        $this->assertEquals(1, ShengamoOrder::count());
        $this->assertEquals($paymentId, ShengamoOrder::first()->tx_ref);

    }

    public function test_payment_verified_as_successful()
    {
        Http::fake([
            config('tumeny.base_url') . 'token' => Http::response([
                "token"=> "abcdef123456",
                "expireAt"=> [
                    "date"=> Carbon::parse()->addHours(2)->timezone('UTC'),
                    "timezone_type"=> 3,
                    "timezone"=> "UTC"
                ]
            ], 200)
        ]);

        Http::fake([
            config('tumeny.base_url') . 'v1/payment/*' => Http::response([
                'payment' => [
                    "id"=> "0005a2ea-06f5-446c-9e5a-51a3eeab93be",
                    "amount"=> 1,
                    "status"=> "success",
                    "message"=> "Successful"
                ],
            ], 200)
        ]);

        ShengamoOrder::create([
            'tx_ref'=>"0005a2ea-06f5-446c-9e5a-51a3eeab93be",
            'plan'=>"tumeny",
            'amount'=>1,
            'status'=>1
        ]);

        $very = new VerifyPendingOrderPayments();
        $very->handle();

        $this->assertEquals(2, ShengamoOrder::first()->status);
        $this->assertEquals('Success', ShengamoOrder::first()->orderStatus->status);
    }

    public function test_payment_verified_has_failed()
    {
        Http::fake([
            config('tumeny.base_url') . 'token' => Http::response([
                "token"=> "abcdef123456",
                "expireAt"=> [
                    "date"=> Carbon::parse()->addHours(2)->timezone('UTC'),
                    "timezone_type"=> 3,
                    "timezone"=> "UTC"
                ]
            ], 200)
        ]);

        Http::fake([
            config('tumeny.base_url') . 'v1/payment/*' => Http::response([
                'payment' => [
                    "id"=> "0005a2ea-06f5-446c-9e5a-51a3eeab93be",
                    "amount"=> 1,
                    "status"=> "failed",
                    "message"=> "Failed"
                ],
            ], 200)
        ]);

        ShengamoOrder::create([
            'tx_ref'=>"0005a2ea-06f5-446c-9e5a-51a3eeab93be",
            'plan'=>"tumeny",
            'amount'=>1,
            'status'=>1
        ]);

        $this->assertEquals('Pending', ShengamoOrder::first()->orderStatus->status);

        $very = new VerifyPendingOrderPayments();
        $very->handle();

        $this->assertEquals(3, ShengamoOrder::first()->status);
        $this->assertEquals('Failed', ShengamoOrder::first()->orderStatus->status);
    }

    public function test_payment_verified_is_still_pending()
    {
        Http::fake([
            config('tumeny.base_url') . 'token' => Http::response([
                "token"=> "abcdef123456",
                "expireAt"=> [
                    "date"=> Carbon::parse()->addHours(2)->timezone('UTC'),
                    "timezone_type"=> 3,
                    "timezone"=> "UTC"
                ]
            ], 200)
        ]);

        Http::fake([
            config('tumeny.base_url') . 'v1/payment/*' => Http::response([
                'payment' => [
                    "id"=> "0005a2ea-06f5-446c-9e5a-51a3eeab93be",
                    "amount"=> 1,
                    "status"=> "pending",
                    "message"=> "Pending"
                ],
            ], 200)
        ]);

        ShengamoOrder::create([
            'tx_ref'=>"0005a2ea-06f5-446c-9e5a-51a3eeab93be",
            'plan'=>"tumeny",
            'amount'=>1,
            'status'=>1
        ]);

        $this->assertEquals('Pending', ShengamoOrder::first()->orderStatus->status);
        $very = new VerifyPendingOrderPayments();
        $very->handle();

        $this->assertEquals(1, ShengamoOrder::first()->status);
        $this->assertEquals('Pending', ShengamoOrder::first()->orderStatus->status);
    }

}
