<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PetTransport extends Model
{
    use HasFactory;

    protected $fillable = [
        'pet_id', 'tutor_id', 'branch_id', 'driver_id',
        'pickup_at', 'delivery_at',
        'origin_address', 'destination_address',
        'status', 'price', 'notes', 'invoice_id',
    ];

    protected $casts = [
        'pickup_at'   => 'datetime',
        'delivery_at' => 'datetime',
        'price'       => 'decimal:2',
    ];

    const STATUSES = [
        'scheduled'  => 'Agendado',
        'collected'  => 'Coletado',
        'in_route'   => 'Em Rota',
        'delivered'  => 'Entregue',
        'cancelled'  => 'Cancelado',
    ];

    const STATUS_COLORS = [
        'scheduled' => 'blue',
        'collected' => 'yellow',
        'in_route'  => 'orange',
        'delivered' => 'green',
        'cancelled' => 'red',
    ];

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(Tutor::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }
}
