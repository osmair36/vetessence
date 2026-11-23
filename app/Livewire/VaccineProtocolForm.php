<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\VaccineProtocol;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\On;
use Livewire\Component;

class VaccineProtocolForm extends Component
{
    public $vaccineProtocolId;
    public $product_id = '';
    public $protocol_type = 'vaccine';
    public $species = '';
    public $vaccine_name = '';
    public $age_start_weeks = '';
    public $age_end_weeks = '';
    public $is_initial = false;
    public $dose_number = '';
    public $interval_value = '';
    public $interval_unit = '';
    public $booster_interval_months = '';
    public $is_core = false;
    public $notes = '';
    public $is_active = true;
    public $products = [];

    protected $rules = [
        'product_id' => 'nullable|exists:products,id',
        'protocol_type' => 'nullable|in:vaccine,antiparasitic,dewormer,medication,other',
        'species' => 'required|string|max:50',
        'vaccine_name' => 'required|string|max:200',
        'age_start_weeks' => 'nullable|integer|min:0',
        'age_end_weeks' => 'nullable|integer|min:0',
        'is_initial' => 'boolean',
        'dose_number' => 'nullable|integer|min:1',
        'interval_value' => 'nullable|integer|min:1|max:3650',
        'interval_unit' => 'nullable|in:days,weeks,months|required_with:interval_value',
        'booster_interval_months' => 'nullable|integer|min:1',
        'is_core' => 'boolean',
        'notes' => 'nullable|string',
        'is_active' => 'boolean',
    ];

    public function mount($id = null)
    {
        $this->products = Product::where('is_active', true)->orderBy('name')->get();
        if ($id) $this->load($id);
    }

    #[On('editVaccineProtocol')]
    public function load($id)
    {
        $this->vaccineProtocolId = $id;
        $protocol = VaccineProtocol::findOrFail($id);
        $this->product_id = (string) ($protocol->product_id ?? '');
        $this->protocol_type = (string) ($protocol->protocol_type ?? 'vaccine');
        $this->species = $protocol->species;
        $this->vaccine_name = $protocol->vaccine_name;
        $this->age_start_weeks = (string) ($protocol->age_start_weeks ?? '');
        $this->age_end_weeks = (string) ($protocol->age_end_weeks ?? '');
        $this->is_initial = $protocol->is_initial;
        $this->dose_number = (string) ($protocol->dose_number ?? '');
        $this->interval_value = (string) ($protocol->interval_value ?? '');
        $this->interval_unit = (string) ($protocol->interval_unit ?? '');
        $this->booster_interval_months = (string) ($protocol->booster_interval_months ?? '');
        $this->is_core = $protocol->is_core;
        $this->notes = $protocol->notes ?? '';
        $this->is_active = $protocol->is_active;
        $this->products = Product::where('is_active', true)->orderBy('name')->get();
    }

    #[On('resetForm')]
    public function resetForm()
    {
        $this->vaccineProtocolId = null;
        $this->product_id = '';
        $this->protocol_type = 'vaccine';
        $this->species = '';
        $this->vaccine_name = '';
        $this->age_start_weeks = '';
        $this->age_end_weeks = '';
        $this->is_initial = false;
        $this->dose_number = '';
        $this->interval_value = '';
        $this->interval_unit = '';
        $this->booster_interval_months = '';
        $this->is_core = false;
        $this->notes = '';
        $this->is_active = true;
        $this->products = Product::where('is_active', true)->orderBy('name')->get();
        $this->resetValidation();
    }

    public function save()
    {
        foreach (['product_id', 'age_start_weeks', 'age_end_weeks', 'dose_number', 'interval_value', 'interval_unit', 'booster_interval_months', 'notes'] as $f) {
            $this->$f = $this->$f !== '' && $this->$f !== null ? $this->$f : null;
        }
        $this->is_initial = (bool) $this->is_initial;
        $this->is_core = (bool) $this->is_core;
        $this->is_active = (bool) $this->is_active;
        $this->validate();

        $data = [
            'species' => $this->species,
            'vaccine_name' => $this->vaccine_name,
            'age_start_weeks' => $this->age_start_weeks,
            'age_end_weeks' => $this->age_end_weeks,
            'is_initial' => $this->is_initial,
            'dose_number' => $this->dose_number,
            'booster_interval_months' => $this->booster_interval_months,
            'is_core' => $this->is_core,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
        ];

        if (Schema::hasColumn('vaccine_protocols', 'product_id')) {
            $data['product_id'] = $this->product_id;
            $data['protocol_type'] = $this->protocol_type ?: 'vaccine';
            $data['interval_value'] = $this->interval_value;
            $data['interval_unit'] = $this->interval_value ? $this->interval_unit : null;
        }

        if ($this->vaccineProtocolId) {
            VaccineProtocol::findOrFail($this->vaccineProtocolId)->update($data);
        } else {
            VaccineProtocol::create($data);
        }

        $this->dispatch('vaccine-protocol-saved');
        $this->dispatch('close-modal');
    }

    public function render()
    {
        return view('livewire.vaccine-protocol-form');
    }
}
