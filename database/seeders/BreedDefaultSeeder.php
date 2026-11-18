<?php

namespace Database\Seeders;

use App\Models\BreedDefault;
use Illuminate\Database\Seeder;

class BreedDefaultSeeder extends Seeder
{
    public function run(): void
    {
        $breeds = [
            'canine' => [
                'Labrador Retriever', 'Golden Retriever', 'Poodle', 'Bulldog Francês',
                'Pastor Alemão', 'Rottweiler', 'Beagle', 'Shih Tzu', 'Yorkshire Terrier',
                'Dachshund', 'Boxer', 'Pug', 'Husky Siberiano', 'Border Collie',
                'Maltês', 'Lhasa Apso', 'Chihuahua', 'Cocker Spaniel Inglês',
                'Buldogue Inglês', 'Dobermann', 'Akita', 'Shiba', 'Pinscher',
                'Pit Bull', 'Chow Chow', 'Dálmata', 'Pastor Belga', 'Spitz Alemão',
                'Basset Hound', 'Schnauzer', 'Cane Corso', 'Vira-Lata (SRD)',
            ],
            'feline' => [
                'Abissínio', 'American Shorthair', 'American Bobtail', 'American Curl',
                'Aphrodite', 'Australian Mist', 'Balinese', 'Bengal', 'Birman',
                'Bombay', 'British Shorthair', 'Burmese', 'Burmilla', 'Chartreux', 'Chausie',
                'Cornish Rex', 'Cymric', 'Devon Rex', 'Donskoy', 'Egyptian Mau',
                'Exotic Shorthair', 'Havana', 'Highlander', 'Himalayan',
                'Japanese Bobtail', 'Khaomanee', 'Korat', 'Kurilian Bobtail',
                'Laperm', 'Lykoi', 'Maine Coon', 'Manx', 'Minuet', 'Munchkin',
                'Nebelung', 'Norueguês da Floresta', 'Ocicat', 'Oriental',
                'Persa', 'Peterbald', 'Pixiebob', 'Ragdoll', 'Russian Blue',
                'Savannah', 'Scottish Fold', 'Scottish Straight', 'Selkirk Rex',
                'Serengeti', 'Siamês', 'Siberian', 'Singapura', 'Snowshoe',
                'Somali', 'Sphynx', 'Thai', 'Tonkinese', 'Toybob', 'Toyger',
                'Turkish Angora', 'Turkish Van', 'Vira-Lata (SRD)',
            ],
            'equine' => [
                'Quarto de Milha', 'Mangalarga Marchador', 'Crioulo', 'Campolina',
                'Puro Sangue Inglês (PSI)', 'Árabe', 'Appaloosa', 'Pampa',
                'Lusitano', 'Bretão', 'Sem Raça Definida (SRD)',
            ],
            'bovine' => [
                'Nelore', 'Holandês', 'Angus', 'Gir Leiteiro', 'Brahman',
                'Girolando', 'Guzerá', 'Senepol', 'Brangus', 'Sem Raça Definida (SRD)',
            ],
            'avian' => [
                'Calopsita', 'Periquito Australiano', 'Papagaio Verdadeiro', 'Canário Belga',
                'Agapornis', 'Cacatua', 'Arara Canindé', 'Arara Vermelha', 'Calafate',
                'Manon', 'Diamante de Gould', 'Sem Raça Definida (SRD)',
            ],
            'lagomorph' => [
                'Mini Lion', 'Fuzzy Lop', 'Holland Lop', 'Netherland Dwarf',
                'Mini Rex', 'Cabeça de Leão', 'Angorá', 'Sem Raça Definida (SRD)',
            ],
            'rodent' => [
                'Hamster Sírio', 'Hamster Anão Russo', 'Porquinho da Índia', 'Chinchila',
                'Gerbil (Esquilo da Mongólia)', 'Rato Twister (Mecol)', 'Camundongo', 'Sem Raça Definida (SRD)',
            ],
            'reptile' => [
                'Iguana Verde', 'Pogona (Dragão Barbudo)', 'Gecko Leopardo', 'Jibóia',
                'Corn Snake (Cobra do Milho)', 'Tigre D\'Água', 'Tartaruga de Ouvido Vermelho',
                'Sem Raça Definida (SRD)',
            ],
        ];

        foreach ($breeds as $species => $names) {
            foreach ($names as $name) {
                BreedDefault::firstOrCreate(
                    ['species' => $species, 'breed' => $name],
                    ['is_active' => true]
                );
            }
        }
    }
}
