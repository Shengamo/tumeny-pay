<?php

namespace Shengamo\TumenyPay\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Shengamo\TumenyPay\Models\ShengamoOrder;
use Shengamo\TumenyPay\TumenyPay;

class VerifyPendingOrderPayments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $pendingOrders = ShengamoOrder::where('status', 1)->get();

        $tumeny = new TumenyPay();
        foreach ($pendingOrders as $order) {
//            NOTIFY ADMIN OF FAILED TRANSACTION
            $paymentStatus = $tumeny->verifyPayment($order);

            if($paymentStatus==='pending'){
                return;
            }
            // Update the order status based on the payment status
            if ($paymentStatus === 'success') {
                $order->update(['status' => 2]); // Payment successful
            }else{
//                $order->update(['status' => 3]); // Payment failed
            }
        }
    }
}
