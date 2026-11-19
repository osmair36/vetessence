<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\ConsentLoggable;
use App\Traits\HasPhoto;

class Tutor extends Authenticatable
{
    use HasFactory, HasPhoto, Notifiable, ConsentLoggable;

    protected static function booted(): void
    {
        static::deleting(function (Tutor $tutor) {
            if ($tutor->pets()->exists()) {
                throw new \RuntimeException(
                    'Não é possível excluir o tutor "' . $tutor->name . '" pois possui ' .
                    $tutor->pets()->count() . ' pet(s) vinculado(s).'
                );
            }
        });
    }

    protected $fillable = [
        'name', 'birth_date', 'user_id', 'cpf', 'rg', 'phone', 'phone_secondary', 'email',
        'zipcode', 'address', 'number', 'complement', 'neighborhood',
        'city', 'state', 'profession', 'photo', 'notes',
        'password', 'remember_token',
        'created_at_branch_id',
        'state_id', 'city_id',
        'notify_sms', 'notify_whatsapp', 'notify_email',
        'whatsapp_opt_in', 'whatsapp_opt_in_at', 'whatsapp_opt_out_at', 'whatsapp_opt_in_source',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $appends = ['name'];

    protected $casts = [
        'birth_date' => 'date',
        'email_verified_at' => 'datetime',
        'notify_sms' => 'boolean',
        'notify_whatsapp' => 'boolean',
        'notify_email' => 'boolean',
        'whatsapp_opt_in' => 'boolean',
        'whatsapp_opt_in_at' => 'datetime',
        'whatsapp_opt_out_at' => 'datetime',
    ];

    /**
     * Normaliza telefone brasileiro para envio WhatsApp (retorna null se invalido).
     */
    public function normalizedWhatsAppPhone(): ?string
    {
        $candidates = array_filter([$this->phone, $this->phone_secondary]);
        foreach ($candidates as $raw) {
            $digits = preg_replace('/\D/', '', (string) $raw);
            if (!$digits) {
                continue;
            }
            $digits = ltrim($digits, '0');
            // 10 ou 11 digitos (DDD + numero BR)
            if (strlen($digits) === 10 || strlen($digits) === 11) {
                return '55' . $digits;
            }
            // 12 ou 13 digitos ja com DDI 55
            if ((strlen($digits) === 12 || strlen($digits) === 13) && str_starts_with($digits, '55')) {
                return $digits;
            }
        }
        return null;
    }

    public function hasValidWhatsAppPhone(): bool
    {
        return $this->normalizedWhatsAppPhone() !== null;
    }

    /**
     * Verifica consentimento explicito para envios automaticos pelo WhatsApp (LGPD).
     * Nao presume autorizacao apenas porque o telefone esta cadastrado.
     */
    public function canReceiveAutoWhatsApp(): bool
    {
        return $this->whatsapp_opt_in === true && $this->hasValidWhatsAppPhone();
    }

    public function getNameAttribute()
    {
        if ($this->user) {
            return $this->user->name;
        }
        if ($this->attributes['name'] ?? null) {
            return $this->attributes['name'];
        }
        return $this->email ?? 'Tutor';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pets(): BelongsToMany
    {
        return $this->belongsToMany(Pet::class, 'pet_tutor')
            ->withPivot('is_primary', 'relationship');
    }

    public function primaryPets(): BelongsToMany
    {
        return $this->belongsToMany(Pet::class, 'pet_tutor')
            ->wherePivot('is_primary', true);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function convenioSubscriptions(): HasMany
    {
        return $this->hasMany(ConvenioSubscription::class);
    }

    public function activeConvenioSubscriptionForPet(Pet $pet): ?ConvenioSubscription
    {
        return $this->convenioSubscriptions()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->whereHas('coveredPets', fn($q) => $q->where('pet_id', $pet->id))
            ->first();
    }

    public function createdAtBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'created_at_branch_id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
