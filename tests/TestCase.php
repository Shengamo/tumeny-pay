<?php
namespace Shengamo\TumenyPay\Tests;

use App\Models\Team;
use App\Models\User;
use Shengamo\TumenyPay\TumenyPayServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            TumenyPayServiceProvider::class,
        ];
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
