<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('shengamo_order_statuses')) {
            Schema::create('shengamo_order_statuses', function (Blueprint $table) {
                $table->id();
                $table->string('status');
                $table->timestamps();
                $table->softDeletes();
            });
//        Seed data
            DB::table('shengamo_order_statuses')->insert(array(
                0 =>
                    array(
                        'id' => 1,
                        'status' => 'Pending',
                        'created_at' => '2023-12-11 11:11:11',
                        'updated_at' => NULL,
                        'deleted_at' => NULL,
                    ),
                1 =>
                    array(
                        'id' => 2,
                        'status' => 'Success',
                        'created_at' => '2023-12-11 11:11:11',
                        'updated_at' => NULL,
                        'deleted_at' => NULL,
                    ),
                2 =>
                    array(
                        'id' => 3,
                        'status' => 'Failed',
                        'created_at' => '2023-12-11 11:11:11',
                        'updated_at' => NULL,
                        'deleted_at' => NULL,
                    ),
            ));

        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shengamo_order_statuses');
    }
};
