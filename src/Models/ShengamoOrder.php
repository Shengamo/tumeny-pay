<?php

namespace Shengamo\TumenyPay\Models;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Shengamo\TumenyPay\Services\FormatterService;

class ShengamoOrder extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'team_id',
        'tx_ref',
        'plan',
        'amount',
        'team_subscription_id',
        'status',
    ];


    protected $casts = [
//        'plan' => PlanEnum::class,
    ];

    public function orderStatus()
    {
        return $this->belongsTo(ShengamoOrderStatus::class,'status');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected function amount(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => FormatterService::ngweeToKwacha($value),
            set: fn ($value) => FormatterService::kwachaToNgwee($value),
        );
    }
}
