<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Space extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'application_id',
        'concourse_id',
        'name',
        'email',
        'space_type',
        'price',
        'sqm',
        'status',
        'lease_start',
        'lease_end',
        'lease_due',
        'lease_term',
        'lease_status',
        'application_status',
        'requirements_status',
        'address',
        'phone_number',
        'remarks',
        'business_type',
        'business_name',
        'owner_name',
        'water_bills',
        'water_due',
        'water_consumption',
        'electricity_bills',
        'electricity_due',
        'electricity_consumption',
        'rent_bills',
        'water_payment_status',
        'electricity_payment_status',
        'rent_payment_status',
        'penalty',
        'rent_due',
        'payment_due_status',
        'is_active',
        'space_width',
        'space_length',
        'space_area',
        'space_dimension',
        'space_coordinates_x',
        'space_coordinates_y',
        'space_coordinates_x2',
        'space_coordinates_y2',
        'water_consumption',
        'electricity_consumption',
        'water_payment_status',
        'electricity_payment_status',
        'rent_payment_status',
    ];

    protected $casts = [
        'sqm' => 'float',
        'price' => 'float',
        'lease_start' => 'datetime',
        'lease_end' => 'datetime',
        'lease_due' => 'datetime',
        'water_consumption' => 'float',
        'electricity_consumption' => 'float',
        'water_bills' => 'float',
        'electricity_bills' => 'float',
        'rent_bills' => 'float',
    ];

    protected $dates = [
        'lease_start',
        'lease_end',
        'created_at',
        'updated_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function concourse(): BelongsTo
    {
        return $this->belongsTo(Concourse::class);
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(User::class, 'tenant_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function updatePriceBasedOnRate()
    {
        $concourse = $this->concourse;
        if ($concourse && $concourse->rate) {
            $newPrice = $concourse->rate->price * $this->sqm;
            if ($this->price != $newPrice) {
                $this->update(['price' => $newPrice]);
            }
        }
    }

    
    public function calculateWaterBill()
    {
        $concourse = $this->concourse;
        if (!$concourse) {
            return;
        }

        $waterRate = $concourse->calculateWaterRate();
        $waterBill = $waterRate * $this->water_consumption;
        
        $waterPaymentStatus = ($this->water_consumption > 0) ? 'unpaid' : null;
        
        $this->update([
            'water_bills' => round($waterBill, 2),
            'water_payment_status' => $waterPaymentStatus,
        ]);
    }

    public function calculateElectricityBill()
    {
        $concourse = $this->concourse;
        if (!$concourse) {
            return;
        }

        $electricityRate = $concourse->calculateElectricityRate();
        $electricityBill = $electricityRate * $this->electricity_consumption;
        
        $electricityPaymentStatus = ($this->electricity_consumption > 0) ? 'unpaid' : null;
        
        $this->update([
            'electricity_bills' => round($electricityBill, 2),
            'electricity_payment_status' => $electricityPaymentStatus,
        ]);
    }
}
