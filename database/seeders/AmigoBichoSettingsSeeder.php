<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class AmigoBichoSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'branding.clinic_name'          => 'Amigo Bicho - Clínica Veterinária e Pet Shop',
            'branding.primary_color'        => '#1E7E34',
            'branding.secondary_color'      => '#F4A261',
            'branding.sidebar_bg'           => '#132E27',
            'branding.accent_color'         => '#E76F51',
            'branding.show_clinic_name'     => '1',
            'branding.clinic_name_position' => 'below',
            'branding.login_background'     => '#F8F9FA',
            'branding.sidebar_logo_width'   => '140',
            'branding.logo_path'            => 'img/logo-amigobicho.png',
            'branding.sidebar_logo_path'    => 'img/logo-amigobicho.png',

            'clinic.name'                   => 'Amigo Bicho - Clínica Veterinária e Pet Shop',
            'clinic.address'                => 'Rua Rio de Janeiro, 178 - Jardim América',
            'clinic.city'                   => 'Assis Chateaubriand',
            'clinic.state'                  => 'PR',
            'clinic.zipcode'                => '85935-000',
            'clinic.ibge'                   => '4102000',
            'clinic.phone'                  => '(44) 3528-2481',
            'clinic.whatsapp'               => '(44) 99833-0660',
            'clinic.technical_responsible'  => 'Dra. Joelma Scarabelli Neves',
            'clinic.crmv'                   => 'CRMV-PR 08147',
            'clinic.hours_weekdays'         => '08:00 às 18:00',
            'clinic.hours_saturday'         => '08:00 às 12:00',
            'clinic.hours_sunday'           => 'Fechado',
            'clinic.instagram'              => 'https://www.instagram.com/amigobichoassis/',
        ];

        foreach ($settings as $key => $value) {
            Setting::set($key, $value);
        }
    }
}
