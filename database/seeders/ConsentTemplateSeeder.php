<?php

namespace Database\Seeders;

use App\Models\ConsentTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ConsentTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Termo de Consentimento para Internamento Hospitalar',
                'slug' => 'termo-internamento-hospitalar',
                'category' => 'internamento',
                'description' => 'Autorização de internação clínica, monitorização, realização de exames de urgência e procedimentos de suporte à vida.',
                'content' => "<p>Eu, <strong>{tutor_name}</strong>, portador(a) do documento <strong>{tutor_document}</strong>, na qualidade de proprietário(a) / responsável legal pelo animal <strong>{pet_name}</strong> (Espécie: <strong>{pet_species}</strong>, Raça: <strong>{pet_breed}</strong>), autorizo a internação do referido paciente na clínica <strong>Amigo Bicho</strong>.</p>
<p>Declaro estar ciente de que o paciente receberá os cuidados médicos e terapêuticos necessários ao seu quadro clínico, incluindo fluidoterapia, administração de medicamentos, oxigenioterapia e exames complementares de monitoramento sob responsabilidade do corpo médico veterinário.</p>
<p>Estou ciente de que em pacientes internados podem ocorrer intercorrências inerentes à gravidade da patologia, autorizando desde já os procedimentos emergenciais de ressuscitação e suporte básico de vida que forem julgados imprescindíveis para a manutenção da vida do animal.</p>
<p>Comprometo-me a arcar com os custos das diárias de internação, medicamentos, materiais e procedimentos realizados durante o período de permanência.</p>",
                'is_active' => true,
            ],
            [
                'name' => 'Termo de Consentimento para Eutanásia e Destinação de Cadáver',
                'slug' => 'termo-eutanasia',
                'category' => 'eutanasia',
                'description' => 'Autorização expressa para prática de eutanásia humanitária em conformidade com as resoluções do CFMV.',
                'content' => "<p>Eu, <strong>{tutor_name}</strong>, portador(a) do documento <strong>{tutor_document}</strong>, proprietário(a) e responsável legal pelo animal <strong>{pet_name}</strong> (Espécie: <strong>{pet_species}</strong>, Raça: <strong>{pet_breed}</strong>), venho por meio deste autorizar a equipe médica veterinária da clínica <strong>Amigo Bicho</strong> a realizar o procedimento de <strong>EUTANÁSIA HUMANITÁRIA</strong> no referido paciente.</p>
<p>Declaro que fui plenamente informado(a) pelo(a) Médico(a) Veterinário(a) sobre o diagnóstico, prognóstico desfavorável/infausto e a ausência de recursos terapêuticos que garantam a qualidade de vida do animal, estando ciente de que o procedimento visa abreviar o sofrimento físico intolerável e irreversível do paciente, sendo realizado de forma indolor e ética com anestesia geral prévia.</p>
<p>Declaro ainda a decisão quanto à destinação do corpo do animal (cremação / sepultamento / recolhimento público especializado), isentando a clínica de qualquer responsabilidade posterior.</p>",
                'is_active' => true,
            ],
            [
                'name' => 'Termo de Consentimento e Risco Cirúrgico / Anestésico',
                'slug' => 'termo-cirurgia-anestesia',
                'category' => 'cirurgia',
                'description' => 'Consentimento informado para procedimentos cirúrgicos e administração anestésica.',
                'content' => "<p>Eu, <strong>{tutor_name}</strong>, portador(a) do documento <strong>{tutor_document}</strong>, autorizo a realização do procedimento cirúrgico e dos procedimentos anestésicos necessários no animal <strong>{pet_name}</strong> (Espécie: <strong>{pet_species}</strong>, Raça: <strong>{pet_breed}</strong>).</p>
<p>Fui devidamente esclarecido(a) pela equipe cirúrgica e anestésica a respeito da técnica a ser empregada, objetivos, cuidados pré e pós-operatórios, bem como sobre os riscos inerentes a qualquer ato cirúrgico e anestésico, incluindo choque anafilático, parada cardiorrespiratória e hemorragias, que independem da perícia do profissional.</p>
<p>Autorizo a equipe médica veterinária a realizar qualquer outro procedimento emergencial não previsto inicialmente, caso julgado estritamente necessário para salvaguardar a vida do paciente durante o ato operatório.</p>
<p>Comprometo-me a seguir rigorosamente todas as prescrições, uso de colar elizabetano / roupa cirúrgica e medicações do pós-operatório.</p>",
                'is_active' => true,
            ],
            [
                'name' => 'Termo de Ciência e Cuidados para Paciente Atópico / Dermatológico',
                'slug' => 'termo-animal-atopico',
                'category' => 'dermatologia',
                'description' => 'Orientações e termo de ciência sobre dermatite atópica canina/felina e natureza crônica da doença.',
                'content' => "<p>Eu, <strong>{tutor_name}</strong>, responsável pelo animal <strong>{pet_name}</strong> (Espécie: <strong>{pet_species}</strong>, Raça: <strong>{pet_breed}</strong>), declaro ter sido devidamente orientado(a) pelo Médico Veterinário sobre o diagnóstico de <strong>DERMATITE ATÓPICA / ALERGIA CRÔNICA</strong>.</p>
<p>Declaro estar ciente de que:</p>
<ul>
    <li>A atopia é uma afecção inflamatória e pruriginosa crônica de base genética, que <strong>NÃO TEM CURA DEFINITIVA</strong>, exigindo controle clínico continuado ao longo de toda a vida do animal.</li>
    <li>Podem ocorrer recidivas e crises sazonais que exigirão ajustes periódicos de medicações, imunoterapia, banhos medicamentosos e controle rigoroso de ectoparasitas (pulgas e carrapatos).</li>
    <li>A interrupção inadvertida dos tratamentos ou banhos prescritos sem consentimento veterinário pode acarretar infecções bacterianas e fúngicas secundárias graves (piodermites e malasseziose).</li>
</ul>
<p>Comprometo-me a manter o acompanhamento dermatológico regular e a relatar imediatamente quaisquer reações ou piora do prurido.</p>",
                'is_active' => true,
            ],
            [
                'name' => 'Termo de Esclarecimento e Consentimento de Vacinação',
                'slug' => 'termo-vacinacao',
                'category' => 'imunizacao',
                'description' => 'Termo de esclarecimento sobre imunização, reações adversas e cuidados pós-vacinais.',
                'content' => "<p>Eu, <strong>{tutor_name}</strong>, portador(a) do documento <strong>{tutor_document}</strong>, autorizo a administração das vacinas prescritas no animal <strong>{pet_name}</strong> (Espécie: <strong>{pet_species}</strong>, Raça: <strong>{pet_breed}</strong>).</p>
<p>Declaro que fui informado(a) sobre a importância do esquema vacinal, cumprimento rigoroso das datas de reforço e que o animal deve estar hígido e vermifugado no momento da aplicação.</p>
<p>Fui também informado(a) sobre a possibilidade de reações adversas pós-vacinais comuns (febre transitória, dor local, sonolência) e raros eventos de hipersensibilidade individual (edema facial, urticária, anafilaxia), cabendo a mim comunicar imediatamente a clínica caso surjam alterações clínicas.</p>
<p>Estou ciente de que a imunidade efetiva ocorre apenas após o término do protocolo completo de doses e reforços preconizados.</p>",
                'is_active' => true,
            ],
            [
                'name' => 'Termo de Ciência e Autorização para Tosa de Raças com Subpelo (Spitz, Chow Chow, Husky)',
                'slug' => 'termo-tosa-subpelo',
                'category' => 'estetica',
                'description' => 'Autorização com ciência expressa sobre riscos de alopecia pós-tosa em raças de pelagem dupla/subpelo.',
                'content' => "<p>Eu, <strong>{tutor_name}</strong>, proprietário(a) do animal <strong>{pet_name}</strong>, da raça <strong>{pet_breed}</strong>, solicito e autorizo a realização do procedimento de tosa curta / máquina no meu animal na clínica e estética <strong>Amigo Bicho</strong>.</p>
<p>Declaro expressamente que fui alertado(a) pelos profissionais de estética e medicina veterinária a respeito das características biológicas das raças com pelagem dupla e subpelo (como Spitz Alemão/Lulu da Pomerânia, Chow Chow, Husky Siberiano, Samoieda, Golden Retriever, Pastor Alemão, entre outras), estando plenamente ciente dos seguintes riscos:</p>
<ul>
    <li><strong>Alopecia pós-tosa:</strong> a raspagem ou tosa baixa pode danificar os folículos pilosos, levando à perda permanente ou prolongada de crescimento dos pelos.</li>
    <li><strong>Alteração de textura e cor:</strong> o pelo pode nascer ressecado, opaco, com tonalidade diferente ou falhas localizadas.</li>
    <li><strong>Perda de proteção térmica e cutânea:</strong> a pelagem original serve como isolante térmico natural contra calor e frio, e sua remoção expõe a pele diretamente a queimaduras solares e dermatites.</li>
</ul>
<p>Mesmo após todos os esclarecimentos e desaconselhamentos técnicos, opto por realizar a tosa sob minha total e exclusiva responsabilidade, isentando a clínica e seus profissionais de qualquer ônus ou responsabilidade por eventuais sequelas dermatológicas ou estéticas.</p>",
                'is_active' => true,
            ],
            [
                'name' => 'Termo de Declaração Sanitária e Viagem / Trânsito Animal',
                'slug' => 'termo-declaracao-viagem',
                'category' => 'viagem',
                'description' => 'Atestado de higidez clínica e declaração sanitária para viagens aéreas e rodoviárias nacionais.',
                'content' => "<p>Atesto, para os devidos fins de trânsito e viagem, que examinei clinicamente nesta data o animal <strong>{pet_name}</strong>, espécie <strong>{pet_species}</strong>, raça <strong>{pet_breed}</strong>, sexo <strong>{pet_gender}</strong>, de propriedade de <strong>{tutor_name}</strong> (Documento: <strong>{tutor_document}</strong>).</p>
<p>Certifico que, no momento do exame clínico minucioso, o paciente apresenta-se em bom estado geral de higidez física e sanitária, sem sinais de doenças infectocontagiosas ou parasitárias transmissíveis, encontrando-se apto a viajar pelo meio de transporte informado com destino a <strong>{destination}</strong>.</p>
<p>O animal possui vacinação antirrábica e polivalente atualizadas conforme carteira de imunização anexa, com vermifugação e controle ectoparasitário devidamente administrados dentro dos prazos regulamentares vigentes.</p>
<p>Este documento tem validade conforme normas sanitárias vigentes para o transporte de animais domésticos.</p>",
                'is_active' => true,
            ],
        ];

        foreach ($templates as $data) {
            ConsentTemplate::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}
