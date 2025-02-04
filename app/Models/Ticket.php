<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'incident_ticket_number',
        'created_by',
        'assigned_to',
        'space_id',
        'concourse_id',
        'title',
        'description',
        'concern_type',
        'remarks',
        'status',
        'priority',
        'images',
        'activity_log',
    ];

    protected $casts = [
        'images' => 'array', // Ensure JSON encoding/decoding
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function concourse(): BelongsTo
    {
        return $this->belongsTo(Concourse::class);
    }
}
