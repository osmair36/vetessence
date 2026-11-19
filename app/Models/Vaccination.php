<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BranchScoped;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Vaccination extends Model
{
    use HasFactory, BranchScoped;

    public const FOLLOWUP_PENDING = 'pending';
    public const FOLLOWUP_COMPLETED = 'completed';
    public const FOLLOWUP_CANCELLED = 'cancelled';
    public const FOLLOWUP_NONE = 'none';

    protected $fillable = [
        'pet_id', 'vaccine', 'batch', 'date', 'next_date',
        'lot', 'manufacturer', 'laboratory', 'dose', 'status',
        'followup_status', 'completed_by_vaccination_id',
        'last_contact_at', 'last_contact_notes',
        'application_site', 'vet_id', 'medical_record_id',
        'product_id', 'notes', 'branch_id',
        'sisvetw_recno', 'sisvetw_ficha', 'sisvetw_cod_vac',
    ];

    protected $casts = [
        'date' => 'date',
        'next_date' => 'date',
        'last_contact_at' => 'datetime',
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

            // Sincroniza followup_status com presenca de next_date
            if (empty($vaccination->next_date)) {
                if (empty($vaccination->followup_status) || $vaccination->followup_status === self::FOLLOWUP_PENDING) {
                    $vaccination->followup_status = self::FOLLOWUP_NONE;
                }
            } else {
                if (empty($vaccination->followup_status) || $vaccination->followup_status === self::FOLLOWUP_NONE) {
                    $vaccination->followup_status = self::FOLLOWUP_PENDING;
                }
            }
        });

        static::created(function () {
            Cache::forget('vaccination_center_dashboard_stats');
        });

        static::updated(function (Vaccination $vaccination) {
            Cache::forget('vaccination_center_dashboard_stats');

            if ($vaccination->wasChanged('next_date')) {
                $vaccination->reminders()
                    ->whereIn('status', [VaccinationReminder::STATUS_PREPARED, VaccinationReminder::STATUS_PENDING])
                    ->update([
                        'status' => VaccinationReminder::STATUS_CANCELLED,
                        'cancelled_at' => now(),
                        'cancelled_reason' => 'Data prevista da próxima vacinação alterada',
                    ]);
            }

            if ($vaccination->wasChanged('followup_status') && in_array($vaccination->followup_status, [self::FOLLOWUP_COMPLETED, self::FOLLOWUP_CANCELLED], true)) {
                $reason = $vaccination->followup_status === self::FOLLOWUP_COMPLETED
                    ? 'Vacinação registrada como aplicada'
                    : 'Vacinação pendente cancelada';

                $vaccination->reminders()
                    ->whereIn('status', [
                        VaccinationReminder::STATUS_PREPARED,
                        VaccinationReminder::STATUS_PENDING,
                        VaccinationReminder::STATUS_FAILED,
                    ])
                    ->update([
                        'status' => VaccinationReminder::STATUS_CANCELLED,
                        'cancelled_at' => now(),
                        'cancelled_reason' => $reason,
                    ]);
            }
        });

        static::deleted(function () {
            Cache::forget('vaccination_center_dashboard_stats');
        });
    }

    public function getLaboratoryAttribute($value): ?string
    {
        if ($value !== null && $value !== '') {
            return $value;
        }
        return $this->attributes['manufacturer'] ?? null;
    }

    public function getClassificationAttribute(): string
    {
        if ($this->followup_status === self::FOLLOWUP_COMPLETED) {
            return 'completed';
        }
        if ($this->followup_status === self::FOLLOWUP_CANCELLED) {
            return 'cancelled';
        }
        if (!$this->next_date || $this->followup_status === self::FOLLOWUP_NONE) {
            return 'none';
        }

        $today = now()->startOfDay();
        $next = $this->next_date->copy()->startOfDay();

        if ($next->equalTo($today)) {
            return 'due_today';
        }
        if ($next->lt($today)) {
            return 'overdue';
        }
        if ($next->lte($today->copy()->addDays(7))) {
            return 'next_7_days';
        }
        if ($next->lte($today->copy()->addDays(30))) {
            return 'next_30_days';
        }

        return 'scheduled';
    }

    public function getClassificationLabelAttribute(): string
    {
        return match ($this->classification) {
            'due_today' => 'Vence Hoje',
            'overdue' => 'Em Atraso',
            'next_7_days' => 'Próximos 7 dias',
            'next_30_days' => 'Próximos 30 dias',
            'completed' => 'Concluída',
            'cancelled' => 'Cancelada',
            'scheduled' => 'Programada',
            default => 'Sem próxima dose',
        };
    }

    public function getClassificationBadgeAttribute(): string
    {
        return match ($this->classification) {
            'due_today' => 'warning',
            'overdue' => 'danger',
            'next_7_days' => 'info',
            'next_30_days' => 'primary',
            'completed' => 'success',
            'cancelled' => 'secondary',
            default => 'light',
        };
    }

    public function scopeOpenPending($query)
    {
        return $query->where('followup_status', self::FOLLOWUP_PENDING)
            ->whereNotNull('next_date')
            ->whereHas('pet', fn ($q) => $q->where('is_active', true));
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

    public function reminders(): HasMany
    {
        return $this->hasMany(VaccinationReminder::class);
    }

    public function completedByVaccination(): BelongsTo
    {
        return $this->belongsTo(Vaccination::class, 'completed_by_vaccination_id');
    }
}
