<?php

namespace Shengamo\TumenyPay\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShengamoOrderStatus extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['status'];

    public function orders()
    {
        return $this->hasMany(Order::class,'status');
    }
}
