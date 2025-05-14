<?php

namespace Shengamo\TumenyPay\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Shengamo\TumenyPay\TumenyPayServiceProvider;
class Team extends Model
{
    protected $fillable = ['name', 'user_id', 'personal_team'];
}

