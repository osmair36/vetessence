<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\AmigoBichoSettingsSeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AmigoBichoBrandingTest extends TestCase
{
    public function test_branding_defaults_return_amigo_bicho()
    {
        $clinicName = branding_clinic_name();
        $this->assertStringContainsString('Amigo Bicho', $clinicName);

        $css = branding_css_vars();
        $this->assertStringContainsString('#1E7E34', $css);
        $this->assertStringContainsString('#132E27', $css);
    }

    public function test_amigo_bicho_settings_seeder_persists_clinic_information()
    {
        $this->seed(AmigoBichoSettingsSeeder::class);

        $this->assertEquals('Amigo Bicho - Clínica Veterinária e Pet Shop', Setting::get('branding.clinic_name'));
        $this->assertEquals('Rua Rio de Janeiro, 178 - Jardim América', Setting::get('clinic.address'));
        $this->assertEquals('Assis Chateaubriand', Setting::get('clinic.city'));
        $this->assertEquals('PR', Setting::get('clinic.state'));
        $this->assertEquals('85935-000', Setting::get('clinic.zipcode'));
        $this->assertEquals('(44) 3528-2481', Setting::get('clinic.phone'));
        $this->assertEquals('(44) 99833-0660', Setting::get('clinic.whatsapp'));
        $this->assertEquals('Dra. Joelma Scarabelli Neves', Setting::get('clinic.technical_responsible'));
        $this->assertEquals('CRMV-PR 08147', Setting::get('clinic.crmv'));
        $this->assertEquals('08:00 às 18:00', Setting::get('clinic.hours_weekdays'));
    }

    public function test_branch_seeder_configures_assis_chateaubriand_matriz()
    {
        $this->seed(BranchSeeder::class);

        $matriz = Branch::where('slug', 'matriz')->first();
        $this->assertNotNull($matriz);
        $this->assertEquals('Amigo Bicho - Matriz', $matriz->name);
        $this->assertEquals('Assis Chateaubriand', $matriz->city);
        $this->assertEquals('PR', $matriz->state);
        $this->assertEquals('85935-000', $matriz->zip_code);
        $this->assertEquals('(44) 3528-2481', $matriz->phone);
    }

    public function test_user_seeder_configures_dra_joelma_as_responsible_vet()
    {
        $this->seed(BranchSeeder::class);
        $this->seed(UserSeeder::class);

        $vet = User::where('email', 'vet@vet.com')->first();
        $this->assertNotNull($vet);
        $this->assertEquals('Dra. Joelma Scarabelli Neves', $vet->name);
        $this->assertEquals('CRMV-PR 08147', $vet->crmv);
        $this->assertTrue((bool)$vet->is_veterinarian);
    }
}
