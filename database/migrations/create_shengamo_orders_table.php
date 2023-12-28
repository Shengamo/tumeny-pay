<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'shengamo_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->nullable();
            $table->foreignId('team_subscription_id')->nullable();
            $table->string('plan')->nullable();
            $table->bigInteger('amount')->nullable();
            $table->string('tx_ref');
            $table->timestamps();
            $table->softDeletes();
            $table->tinyInteger('status')->default(1);
        }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('shengamo_orders');
    }
};
