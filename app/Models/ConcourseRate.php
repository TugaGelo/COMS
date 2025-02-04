<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConcourseRate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'price',
        'is_active',
    ];

    public function concourses()
    {
        return $this->hasMany(Concourse::class, 'rate_id')->where('is_active', true);
    }

    protected static function booted()
    {
        static::updated(function ($concourseRate) {
            if ($concourseRate->isDirty('price')) {
                $concourseRate->updateSpacePrices();
            }
        });
    }

    public function updateSpacePrices()
    {
        $this->concourses()->each(function ($concourse) {
            $concourse->spaces()->each(function ($space) {
                $spacePrice = $this->calculateSpacePrice($space->sqm);
                $space->update(['price' => $spacePrice]);
            });
        });
    }

    private function calculateSpacePrice($sqm)
    {
        return $this->price * $sqm;
    }
}
