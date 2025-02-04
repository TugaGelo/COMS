<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Concourse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'address',
        'rate_id',
        'lat',
        'lng',
        'spaces',
        'image',
        'layout',
        'lease_term',
        'is_active',
        'water_bills',
        'electricity_bills',
        'total_water_consumption',
        'total_electricity_consumption',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
    ];

    public function getLocationAttribute()
    {
        return [
            'lat' => $this->lat,
            'lng' => $this->lng,
            'formatted_address' => $this->address,
        ];
    }

    public function setLocationAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['lat'] = $value['lat'] ?? null;
            $this->attributes['lng'] = $value['lng'] ?? null;
            $this->attributes['address'] = $value['formatted_address'] ?? null;
        }
    }

    public function concourseRate()
    {
        return $this->belongsTo(ConcourseRate::class, 'rate_id')->where('is_active', true);
    }

    public function spaces()
    {
        return $this->hasMany(Space::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function calculateWaterRate()
    {
        $totalWaterBill = $this->water_bills ?? 0;
        $totalWaterConsumption = $this->total_water_consumption;

        if ($totalWaterConsumption > 0) {
            return $totalWaterBill / $totalWaterConsumption;
        }

        return 0;
    }

    public function calculateElectricityRate()
    {
        $totalElectricityBill = $this->electricity_bills ?? 0;
        $totalElectricityConsumption = $this->total_electricity_consumption;

        if ($totalElectricityConsumption > 0) {
            return $totalElectricityBill / $totalElectricityConsumption;
        }

        return 0;
    }

    public function getTotalWaterConsumptionAttribute()
    {
        return $this->spaces()->sum('water_consumption');
    }
    

    public function getTotalElectricityConsumptionAttribute()
    {
        return $this->spaces()->sum('electricity_consumption');
    }

    public function updateTotalElectricityConsumption()
    {
        $this->total_electricity_consumption = $this->getTotalElectricityConsumptionAttribute();
        $this->save();
    }

    public function updateTotalWaterConsumption()
    {
        $this->total_water_consumption = $this->getTotalWaterConsumptionAttribute();
        $this->save();
    }

   
    public function updateSpacesWaterBills()
    {
        $waterRate = $this->calculateWaterRate();
        
        $this->spaces()->chunk(100, function ($spaces) use ($waterRate) {
            foreach ($spaces as $space) {
                $waterBill = $waterRate * $space->water_consumption;
                $space->update(['water_bills' => round($waterBill, 2)]);
            }
        });
    }

    public function updateSpacesElectricityBills()
    {
        $electricityRate = $this->calculateElectricityRate();
        
        $this->spaces()->chunk(100, function ($spaces) use ($electricityRate) {
            foreach ($spaces as $space) {
                $electricityBill = $electricityRate * $space->electricity_consumption;
                $space->update(['electricity_bills' => round($electricityBill, 2)]);
            }
        });
    }

    protected static function booted()
    {
        static::saved(function ($concourse) {
            if ($concourse->isDirty('water_bills')) {
                $concourse->updateSpacesWaterBills();
            }

            if ($concourse->isDirty('electricity_bills')) {
                $concourse->updateSpacesElectricityBills();
            }
        });
    }

}

