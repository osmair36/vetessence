<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BranchScoped;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabEquipmentIntegration extends Model
{
    use HasFactory, BranchScoped;

    protected $fillable = [
        'name', 'equipment_type', 'protocol', 'endpoint_url',
        'api_key', 'ip_address', 'port', 'is_active',
        'config', 'notes', 'branch_id', 'last_contact_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array',
        'last_contact_at' => 'datetime',
    ];

    public function results(): HasMany
    {
        return $this->hasMany(LabEquipmentResult::class, 'integration_id');
    }
}
