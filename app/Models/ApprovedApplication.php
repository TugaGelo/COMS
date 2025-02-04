<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovedApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'space_id',
        'concourse_id',
        'business_name',
        'owner_name',
        'email',
        'phone_number',
        'address',
        'status',
        'remarks',
        'business_type',
        'concourse_lease_term',
    ];
}
