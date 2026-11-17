<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\City;
use App\Models\State;
use App\Models\User;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run()
    {
        $state = State::where('uf', 'PR')->first();
        $city = $state ? City::where('state_id', $state->id)->where('name', 'ASSIS CHATEAUBRIAND')->first() : null;

        $branch = Branch::updateOrCreate(
            ['slug' => 'matriz'],
            [
                'name' => 'Amigo Bicho - Matriz',
                'address' => 'Rua Rio de Janeiro',
                'number' => '178',
                'neighborhood' => 'Jardim América',
                'city' => 'Assis Chateaubriand',
                'state' => 'PR',
                'zip_code' => '85935-000',
                'phone' => '(44) 3528-2481',
                'email' => 'contato@amigobicho.com.br',
                'cnpj' => '00.000.000/0001-00',
                'municipio_ibge' => '4102000',
                'state_id' => $state?->id,
                'city_id' => $city?->id,
                'is_active' => true,
                'is_main' => true,
                'notes' => 'Horário: Seg-Sex 08:00 às 18:00, Sáb 08:00 às 12:00. Resp. Técnica: Dra. Joelma Scarabelli Neves (CRMV-PR 08147)',
            ]
        );

        User::whereNull('branch_id')->update(['branch_id' => $branch->id]);
    }
}
