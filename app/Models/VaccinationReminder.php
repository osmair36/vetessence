<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class VaccinationReminder extends Model
{
    use HasFactory;

    // 7 estados distintos do ciclo de vida da mensagem
    public const STATUS_PREPARED = 'prepared';
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT_TO_API = 'sent_to_api';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_READ = 'read';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    // Categorias de notificacao da Central WhatsApp
    public const CATEGORY_VACCINATION = 'vaccination';
    public const CATEGORY_BIRTHDAY = 'birthday';
    public const CATEGORY_GROOMING = 'grooming';

    // Etapas / tipos de lembrete
    public const TYPE_BEFORE_7_DAYS = 'before_7_days';
    public const TYPE_BEFORE_2_DAYS = 'before_2_days';
    public const TYPE_DUE_TODAY = 'due_today';
    public const TYPE_OVERDUE = 'overdue';
    public const TYPE_MANUAL = 'manual';
    public const TYPE_BIRTHDAY = 'birthday';
    public const TYPE_BIRTHDAY_MANUAL = 'birthday_manual';
    public const TYPE_GROOMING_1_DAY = 'grooming_1_day';
    public const TYPE_GROOMING_TODAY = 'grooming_today';
    public const TYPE_GROOMING_MANUAL = 'grooming_manual';

    protected $fillable = [
        'category', 'vaccination_id', 'appointment_id', 'pet_id', 'tutor_id', 'reminder_type',
        'reference_date', 'attempt_number', 'idempotency_key',
        'scheduled_date', 'sent_at', 'delivered_at', 'read_at',
        'cancelled_at', 'cancelled_reason', 'channel', 'destination',
        'message_body', 'provider', 'provider_message_id',
        'status', 'error_message', 'sent_by_user_id', 'branch_id',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'reference_date' => 'date',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('vaccination_center_dashboard_stats'));
        static::deleted(fn () => Cache::forget('vaccination_center_dashboard_stats'));
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PREPARED => 'Preparada',
            self::STATUS_PENDING => 'Aguardando envio',
            self::STATUS_SENT_TO_API, 'sent' => 'Enviada à API',
            self::STATUS_DELIVERED => 'Entregue',
            self::STATUS_READ => 'Lida',
            self::STATUS_FAILED => 'Com falha',
            self::STATUS_CANCELLED => 'Cancelada',
            default => ucfirst((string) $this->status),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PREPARED => 'secondary',
            self::STATUS_PENDING => 'warning',
            self::STATUS_SENT_TO_API, 'sent' => 'info',
            self::STATUS_DELIVERED => 'primary',
            self::STATUS_READ => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_CANCELLED => 'dark',
            default => 'light',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->reminder_type) {
            self::TYPE_BEFORE_7_DAYS => 'Vacina: 7 dias antes',
            self::TYPE_BEFORE_2_DAYS => 'Vacina: 2 dias antes',
            self::TYPE_DUE_TODAY => 'Vacina: no dia previsto',
            self::TYPE_OVERDUE => 'Vacina: em atraso (#' . ($this->attempt_number ?? 1) . ')',
            self::TYPE_MANUAL => 'Vacina: envio manual',
            self::TYPE_BIRTHDAY => 'Aniversariante do dia',
            self::TYPE_BIRTHDAY_MANUAL => 'Aniversário (manual)',
            self::TYPE_GROOMING_1_DAY => 'Banho e Tosa (1 dia antes)',
            self::TYPE_GROOMING_TODAY => 'Banho e Tosa (hoje)',
            self::TYPE_GROOMING_MANUAL => 'Banho e Tosa (manual)',
            default => ucfirst((string) $this->reminder_type),
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            self::CATEGORY_BIRTHDAY => 'Aniversariante',
            self::CATEGORY_GROOMING => 'Banho e Tosa',
            default => 'Vacinação',
        };
    }

    public function getCategoryBadgeAttribute(): string
    {
        return match ($this->category) {
            self::CATEGORY_BIRTHDAY => 'pink',
            self::CATEGORY_GROOMING => 'info',
            default => 'success',
        };
    }

    public function vaccination(): BelongsTo
    {
        return $this->belongsTo(Vaccination::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(Tutor::class);
    }

    public function sentByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }
}
