<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Seeder;

class DepartmentPositionSeeder extends Seeder
{
    public function run(): void
    {
        $structure = [
            'Administrativo e Financeiro' => [
                'Gerente Geral',
                'Assistente Financeiro',
                'Auxiliar Administrativo',
            ],
            'Atendimento e Recepção' => [
                'Líder de Recepção',
                'Recepcionista',
                'Atendente',
            ],
            'Clínica Médica e Cirúrgica' => [
                'Médico Veterinário',
                'Cirurgião Veterinário',
                'Anestesista Veterinário',
                'Auxiliar Veterinário',
            ],
            'Estética Animal e Pet Shop' => [
                'Tosador',
                'Banhista',
                'Auxiliar de Banho e Tosa',
            ],
            'Farmácia e Estoque' => [
                'Farmacêutico Veterinário',
                'Auxiliar de Estoque',
            ],
        ];

        foreach ($structure as $deptName => $positions) {
            $department = Department::firstOrCreate(['name' => $deptName]);

            foreach ($positions as $posName) {
                Position::firstOrCreate(
                    ['name' => $posName, 'department_id' => $department->id],
                    ['description' => $posName]
                );
            }
        }
    }
}
