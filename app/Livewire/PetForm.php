<?php

namespace App\Livewire;

use App\Models\BreedDefault;
use App\Models\Pet;
use App\Models\PetTutor;
use App\Models\Tutor;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class PetForm extends Component
{
    use WithFileUploads;

    public $petId;
    public $name = '';
    public $tutor_id = '';
    public $tutorName = '';
    public $species = '';
    public $breed = '';
    public $breed_default_id = '';
    public $gender = '';
    public $birth_date = '';
    public $weight = '';
    public $color = '';
    public $microchip = '';
    public $microchip_date = '';
    public $rg_number = '';
    public $rg_issuer = '';
    public $size = 'medium';
    public $photo;
    public $notes = '';

    public $speciesOptions = [];
    public $breeds = [];

    protected function rules()
    {
        return [
            'name' => 'required|string|max:100',
            'tutor_id' => 'required|exists:tutors,id',
            'species' => 'required|in:' . implode(',', array_keys(config('species'))),
            'gender' => 'required|in:male,female',
        ];
    }

    public function mount($id = null)
    {
        $this->speciesOptions = config('species');
        $this->species = '';
        $this->loadBreeds();
        if ($id) $this->load($id);
    }

    #[On('editPet')]
    public function load($id)
    {
        $this->petId = $id;
        $pet = Pet::with('tutors', 'breedRelation')->findOrFail($id);
        $this->name = $pet->name;
        $this->tutor_id = (string) ($pet->tutors->first()->id ?? '');
        $this->tutorName = $pet->tutors->first()->name ?? '';
        $this->species = $pet->species;
        $this->breed = $pet->breed ?? '';
        $this->breed_default_id = (string) ($pet->breed_default_id ?? '');
        $this->gender = $pet->gender;
        $this->birth_date = $pet->birth_date ? $pet->birth_date->format('Y-m-d') : '';
        $this->weight = (string) ($pet->weight ?? '');
        $this->color = $pet->color ?? '';
        $this->microchip = $pet->microchip ?? '';
        $this->microchip_date = $pet->microchip_date ? $pet->microchip_date->format('Y-m-d') : '';
        $this->rg_number = $pet->rg_number ?? '';
        $this->rg_issuer = $pet->rg_issuer ?? '';
        $this->size = $pet->size ?? 'medium';
        $this->notes = $pet->notes ?? '';
        $this->loadBreeds();
    }

    #[On('createPetForTutor')]
    public function createForTutor($tutorId)
    {
        $this->resetForm();
        $this->tutor_id = (string) $tutorId;
    }

    #[On('resetForm')]
    public function resetForm()
    {
        $this->petId = null;
        $this->name = '';
        $this->tutor_id = '';
        $this->tutorName = '';
        $this->species = '';
        $this->breed = '';
        $this->breed_default_id = '';
        $this->gender = '';
        $this->birth_date = '';
        $this->weight = '';
        $this->color = '';
        $this->microchip = '';
        $this->microchip_date = '';
        $this->rg_number = '';
        $this->rg_issuer = '';
        $this->size = 'medium';
        $this->photo = null;
        $this->notes = '';
        $this->loadBreeds();
        $this->resetValidation();
    }

    public function updatedSpecies($value)
    {
        $this->breed = '';
        $this->breed_default_id = '';
        $this->loadBreeds();
    }

    public function onSpeciesChange()
    {
        $this->breed = '';
        $this->breed_default_id = '';
        $this->loadBreeds();
    }

    public function updatedBreedDefaultId($value)
    {
        if ($value === 'other') {
            $this->breed = '';
            return;
        }

        if ($value && !str_starts_with((string)$value, 'custom_')) {
            $breed = BreedDefault::find($value);
            if ($breed) {
                $this->breed = $breed->breed;
                if (!$this->species) {
                    $this->species = $breed->species;
                    $this->loadBreeds();
                }
            }
        }
    }

    public function loadBreeds()
    {
        $query = BreedDefault::where('is_active', true);
        if ($this->species) {
            $query->where('species', $this->species);
        }
        $this->breeds = $query->orderBy('breed')
            ->pluck('breed', 'id')
            ->toArray();

        // Se o pet atual tiver uma raça que não consta na lista, adiciona na lista
        if ($this->breed && !in_array($this->breed, $this->breeds)) {
            $key = $this->breed_default_id ?: ('custom_' . md5($this->breed));
            $this->breeds[$key] = $this->breed;
        }
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'species' => $this->species,
            'breed' => $this->breed ?: null,
            'breed_default_id' => (is_numeric($this->breed_default_id) && (int)$this->breed_default_id > 0) ? (int)$this->breed_default_id : null,
            'gender' => $this->gender,
            'birth_date' => $this->birth_date ?: null,
            'weight' => $this->weight ?: null,
            'color' => $this->color ?: null,
            'microchip' => $this->microchip ?: null,
            'microchip_date' => $this->microchip_date ?: null,
            'rg_number' => $this->rg_number ?: null,
            'rg_issuer' => $this->rg_issuer ?: null,
            'size' => $this->size,
            'notes' => $this->notes ?: null,
            'is_active' => true,
        ];

        if ($this->petId) {
            $pet = Pet::findOrFail($this->petId);
            $pet->update($data);
            if ($this->photo) {
                $pet->savePhoto($this->photo, 'pets');
            }
            PetTutor::where('pet_id', $this->petId)->where('is_primary', true)->update(['tutor_id' => $this->tutor_id]);
        } else {
            $pet = Pet::create($data);
            if ($this->photo) {
                $pet->savePhoto($this->photo, 'pets');
            }
            PetTutor::create([
                'pet_id' => $pet->id,
                'tutor_id' => $this->tutor_id,
                'is_primary' => true,
                'relationship' => 'proprietário',
            ]);
        }

        $this->dispatch('pet-saved');
        $this->dispatch('close-modal');
    }

    public function render()
    {
        $tutors = Tutor::select('id', 'name')->orderBy('name')->get();
        return view('livewire.pet-form', compact('tutors'));
    }
}
