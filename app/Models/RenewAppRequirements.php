<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RenewAppRequirements extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'requirement_id',
        'user_id',
        'space_id',
        'concourse_id',
        'application_id',
        'name',
        'status',
        'file',
        'remarks',
    ];

    public function renew()
    {
        return $this->belongsTo(Renew::class, 'application_id');
    }

    public function requirement()
    {
        return $this->belongsTo(Requirement::class);
    }

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

    
}