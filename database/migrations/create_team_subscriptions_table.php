<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create(
            'team_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->nullable();
            $table->string('name');
            $table->string('gateway_id')->nullable();
            $table->string('gateway_name')->nullable();
            $table->string('gateway_plan')->nullable();
            $table->string('gateway_status')->nullable();
            $table->integer('quantity')->default(1);
            $table->bigInteger('amount');
            $table->integer('app_fee')->default(0);
            $table->string('tx_ref')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->tinyInteger('status')->nullable()->default(1);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('team_subscriptions');
    }
};
