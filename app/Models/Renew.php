<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Renew extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'space_id',
        'concourse_id',
        'business_name',
        'owner_name',
        'space_type',
        'email',
        'phone_number',
        'address',
        'requirements_status',
        'application_status',
        'business_type',
        'remarks',
        'monthly_payment',
        'payment_status',
        'payment_due',
        'remarks',
        'application_status',
        'payment_due_date',
        'payment_due_status',
        'concourse_lease_term',
    ];

    protected $dates = ['deleted_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function space()
    {
        return $this->belongsTo(Space::class);
    }

    public function concourse()
    {
        return $this->belongsTo(Concourse::class);
    }

    public function renewRequirements()
    {
        return $this->hasMany(RenewAppRequirements::class);
    }

    public function renewAppRequirements()
    {
        return $this->hasMany(RenewAppRequirements::class, 'application_id');
    }
}
