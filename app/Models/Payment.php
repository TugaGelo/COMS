<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'payment_type',
        'payment_method',
        'concourse_id',
        'space_id',
        'water_due',
        'electricity_due',
        'rent_due',
        'paid_date',
        'due_date',
        'water_bill',
        'water_consumption',
        'electricity_bill',
        'electricity_consumption',
        'amount',
        'rent_bill',
        'payment_status',
        'penalty',
        'is_water_late',
        'is_electricity_late',
        'is_rent_late',
        'is_penalty',
    ];

    protected $casts = [
        'amount' => 'float',
        'water_bill' => 'float',
        'water_consumption' => 'float',
        'electricity_bill' => 'float',
        'electricity_consumption' => 'float',
        'rent_bill' => 'float',
        'penalty' => 'float',
        'payment_details' => 'array',
        'due_date' => 'date',
        'paid_date' => 'date',
        'water_due' => 'date',
        'electricity_due' => 'date',
        'rent_due' => 'date',
        'is_water_late' => 'boolean',
        'is_electricity_late' => 'boolean',
        'is_rent_late' => 'boolean',
        'is_penalty' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function concourse(): BelongsTo
    {
        return $this->belongsTo(Concourse::class);
    }

    // Add these constants
    const STATUS_PAID = 'paid';
    const STATUS_UNPAID = 'unpaid';
    const STATUS_DELAYED = 'delayed';

    public function updateDelayedStatus()
    {
        if ($this->due_date && $this->paid_date) {
            $this->days_delayed = max(0, $this->paid_date->diffInDays($this->due_date));
            if ($this->days_delayed > 0) {
                $this->payment_status = self::STATUS_DELAYED;
            }
        } elseif ($this->due_date && $this->due_date->isPast()) {
            $this->days_delayed = $this->due_date->diffInDays(now());
            $this->payment_status = self::STATUS_DELAYED;
        }
        $this->save();
    }
}
