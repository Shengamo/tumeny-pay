<?php

namespace Shengamo\TumenyPay\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Shengamo\TumenyPay\TumenyPayServiceProvider;

// Create mock User model for testing
class User extends Authenticatable
{
    protected $fillable = [
        'name', 'email', 'password', 'email_verified_at',
        'two_factor_secret', 'two_factor_recovery_codes',
        'profile_photo_path', 'current_team_id'
    ];

    public function currentTeam()
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }
}
