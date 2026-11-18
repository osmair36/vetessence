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
    public $is_neutered = false;
    public $is_aggressive = false;
    public $pathologies = '';
    public $surgical_history = '';
    public $photo;
    public $notes = '';

    public $speciesOptions = [];
    public $breeds = [];
    public $tutorSearch = '';

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
        $this->is_neutered = (bool) $pet->is_neutered;
        $this->is_aggressive = (bool) $pet->is_aggressive;
        $this->pathologies = $pet->pathologies ?? '';
        $this->surgical_history = $pet->surgical_history ?? '';
        $this->notes = $pet->notes ?? '';
        $this->loadBreeds();
    }

    #[On('createPetForTutor')]
    public function createForTutor($tutorId)
    {
        $this->resetForm();
        $this->tutor_id = (string) $tutorId;
        $this->tutorName = Tutor::find($tutorId)?->name ?? '';
    }

    public function selectTutor($id, $name)
    {
        $this->tutor_id = (string) $id;
        $this->tutorName = $name;
        $this->tutorSearch = '';
    }

    public function clearTutor()
    {
        $this->tutor_id = '';
        $this->tutorName = '';
        $this->tutorSearch = '';
    }

    #[On('tutor-saved')]
    public function onTutorSaved($tutorId = null)
    {
        if ($tutorId) {
            $tutor = Tutor::find($tutorId);
            if ($tutor) {
                $this->selectTutor($tutor->id, $tutor->name);
            }
        }
    }

    #[On('resetForm')]
    public function resetForm()
    {
        $this->petId = null;
        $this->name = '';
        $this->tutor_id = '';
        $this->tutorName = '';
        $this->tutorSearch = '';
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
        $this->is_neutered = false;
        $this->is_aggressive = false;
        $this->pathologies = '';
        $this->surgical_history = '';
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
            'is_neutered' => (bool) $this->is_neutered,
            'is_aggressive' => (bool) $this->is_aggressive,
            'birth_date' => $this->birth_date ?: null,
            'weight' => $this->weight ?: null,
            'color' => $this->color ?: null,
            'microchip' => $this->microchip ?: null,
            'microchip_date' => $this->microchip_date ?: null,
            'rg_number' => $this->rg_number ?: null,
            'rg_issuer' => $this->rg_issuer ?: null,
            'size' => $this->size,
            'notes' => $this->notes ?: null,
            'pathologies' => $this->pathologies ?: null,
            'surgical_history' => $this->surgical_history ?: null,
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
        if ($this->tutor_id && empty($this->tutorName)) {
            $this->tutorName = Tutor::find($this->tutor_id)?->name ?? '';
        }

        $tutorResults = [];
        if (!$this->petId && !$this->tutor_id) {
            $query = Tutor::select('id', 'name', 'cpf', 'phone');
            if (trim($this->tutorSearch) !== '') {
                $search = trim($this->tutorSearch);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('cpf', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
                $tutorResults = $query->orderBy('name')->limit(15)->get();
            } else {
                $tutorResults = $query->orderBy('name')->limit(10)->get();
            }
        }

        return view('livewire.pet-form', [
            'tutorResults' => $tutorResults,
        ]);
    }
}
