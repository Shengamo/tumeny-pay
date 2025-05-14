<?php
namespace Shengamo\TumenyPay\Tests;

use Illuminate\Support\Facades\Schema;
use Shengamo\TumenyPay\TumenyPayServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app)
    {
        return [
            TumenyPayServiceProvider::class,
        ];
    }

    protected function setUpDatabase()
    {
        // Create users table
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
            $table->foreignId('current_team_id')->nullable();
            $table->timestamps();
        });

        // Create teams table
        Schema::create('teams', function ($table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('name');
            $table->boolean('personal_team');
            $table->timestamps();
        });

        // Include migrations from your package
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function getEnvironmentSetUp($app)
    {
        // perform environment setup
    }
    protected function signIn($role = 'lawyer', $station_id = 1)
    {
        $user = User::create(
            [
                'name' => 'Test User',
                'email' => 'test@mail.com',
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'profile_photo_path' => null,
                'current_team_id' => null,
            ]
        );
        Team::create([
            'name' => 'Test Company',
            'user_id' => 1,
            'personal_team' => true,
        ]);

        $this->actingAs($user);
    }
}
