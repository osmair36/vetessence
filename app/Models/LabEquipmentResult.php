<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BranchScoped;

class LabEquipmentResult extends Model
{
    use HasFactory, BranchScoped;

    protected $fillable = [
        'integration_id', 'result_identifier', 'pet_id',
        'laboratory_order_id', 'test_type', 'raw_data',
        'parsed_results', 'status', 'error_message', 'received_at',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'parsed_results' => 'array',
        'received_at' => 'datetime',
    ];

    public function integration()
    {
        return $this->belongsTo(LabEquipmentIntegration::class, 'integration_id');
    }

    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }

    public function laboratoryOrder()
    {
        return $this->belongsTo(LaboratoryOrder::class);
    }
}
