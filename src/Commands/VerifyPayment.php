<?php

namespace Shengamo\TumenyPay\Commands;

use Illuminate\Console\Command;
use Shengamo\TumenyPay\Jobs\VerifyPendingOrderPayments;

class VerifyPayment extends Command
{
    protected $signature = 'tumeny:verify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify payments for all shengamo_orders that are pending from the payment gateway.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        (new VerifyPendingOrderPayments())->handle();

        return Command::SUCCESS;
    }
}
