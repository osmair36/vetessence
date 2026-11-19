<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BranchScoped;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vaccination extends Model
{
    use HasFactory, BranchScoped;

    protected $fillable = [
        'pet_id', 'vaccine', 'batch', 'date', 'next_date',
        'lot', 'manufacturer', 'laboratory', 'dose', 'status',
        'application_site', 'vet_id', 'medical_record_id',
        'product_id', 'notes', 'branch_id',
        'sisvetw_recno', 'sisvetw_ficha', 'sisvetw_cod_vac',
    ];

    protected $casts = [
        'date' => 'date',
        'next_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (Vaccination $vaccination) {
            if ($vaccination->isDirty('laboratory')) {
                $vaccination->manufacturer = $vaccination->laboratory;
            } elseif ($vaccination->isDirty('manufacturer') && !$vaccination->isDirty('laboratory')) {
                $vaccination->laboratory = $vaccination->manufacturer;
            } elseif (empty($vaccination->laboratory) && !empty($vaccination->manufacturer)) {
                $vaccination->laboratory = $vaccination->manufacturer;
            } elseif (empty($vaccination->manufacturer) && !empty($vaccination->laboratory)) {
                $vaccination->manufacturer = $vaccination->laboratory;
            }

            if ($vaccination->isDirty('batch') && !$vaccination->isDirty('lot')) {
                $vaccination->lot = $vaccination->batch;
            } elseif ($vaccination->isDirty('lot') && !$vaccination->isDirty('batch')) {
                $vaccination->batch = $vaccination->lot;
            }
        });
    }

    public function getLaboratoryAttribute($value): ?string
    {
        if ($value !== null && $value !== '') {
            return $value;
        }
        return $this->attributes['manufacturer'] ?? null;
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function vet(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vet_id');
    }

    public function veterinarian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vet_id');
    }

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
