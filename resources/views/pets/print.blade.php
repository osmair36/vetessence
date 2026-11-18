@extends('layouts.print', [
    'title' => 'Ficha Cadastral e Clínica do Paciente',
    'subtitle' => 'Paciente: ' . $pet->name . ' | Espécie: ' . ($pet->species ?? '-') . ' | Raça: ' . ($pet->breed ?? 'SRD'),
    'pdfUrl' => $pdfUrl ?? null,
    'docTitle' => 'Ficha - ' . $pet->name,
])

@section('content')
{{-- IDENTIFICAÇÃO DO PACIENTE E TUTOR --}}
<div class="section-box">
    <div class="section-title">1. Dados Cadastrais do Animal</div>
    <div class="section-body">
        <table style="width: 100%; border: none; font-size: 11px;">
            <tr>
                <td style="width: 25%;"><span class="info-label">Nome:</span> <br><strong style="font-size: 13px;">{{ $pet->name }}</strong></td>
                <td style="width: 25%;"><span class="info-label">Espécie:</span> <br>{{ ucfirst($pet->species ?? '-') }}</td>
                <td style="width: 25%;"><span class="info-label">Raça:</span> <br>{{ $pet->breed ?? 'SRD' }}</td>
                <td style="width: 25%;"><span class="info-label">Sexo:</span> <br>{{ $pet->gender === 'male' ? 'Macho' : ($pet->gender === 'female' ? 'Fêmea' : ($pet->gender ?? '-')) }}</td>
            </tr>
            <tr>
                <td style="padding-top: 8px;"><span class="info-label">Idade / Nasc.:</span> <br>{{ $pet->age ?? '-' }} @if($pet->birth_date) ({{ $pet->birth_date->format('d/m/Y') }}) @endif</td>
                <td style="padding-top: 8px;"><span class="info-label">Pelagem / Cor:</span> <br>{{ $pet->color ?? '-' }}</td>
                <td style="padding-top: 8px;"><span class="info-label">Porte:</span> <br>{{ ucfirst($pet->size ?? '-') }}</td>
                <td style="padding-top: 8px;"><span class="info-label">Peso Atual:</span> <br>{{ $pet->weight ? number_format($pet->weight, 2, ',', '.') . ' kg' : '-' }}</td>
            </tr>
            <tr>
                <td style="padding-top: 8px;"><span class="info-label">Microchip:</span> <br>{{ $pet->microchip ?? '-' }}</td>
                <td style="padding-top: 8px;"><span class="info-label">RGA:</span> <br>{{ $pet->rga ?? '-' }}</td>
                <td style="padding-top: 8px;"><span class="info-label">Castrado:</span> <br>{{ $pet->is_neutered ? 'Sim' : 'Não' }}</td>
                <td style="padding-top: 8px;"><span class="info-label">Cadastrado em:</span> <br>{{ $pet->created_at ? $pet->created_at->format('d/m/Y') : '-' }}</td>
            </tr>
        </table>
    </div>
</div>

<div class="section-box">
    <div class="section-title">2. Dados do Tutor / Responsável</div>
    <div class="section-body">
        @forelse($pet->tutors as $tutor)
        <table style="width: 100%; border: none; font-size: 11px; margin-bottom: 6px;">
            <tr>
                <td style="width: 35%;"><span class="info-label">Nome:</span> <br><strong>{{ $tutor->name ?? $tutor->user->name ?? '-' }}</strong></td>
                <td style="width: 25%;"><span class="info-label">CPF/Documento:</span> <br>{{ $tutor->cpf ?? $tutor->document ?? '-' }}</td>
                <td style="width: 20%;"><span class="info-label">Telefone:</span> <br>{{ $tutor->phone ?? $tutor->mobile ?? '-' }}</td>
                <td style="width: 20%;"><span class="info-label">E-mail:</span> <br>{{ $tutor->email ?? $tutor->user->email ?? '-' }}</td>
            </tr>
            <tr>
                <td colspan="4" style="padding-top: 6px;"><span class="info-label">Endereço:</span> <br>{{ $tutor->address ?? '-' }}{{ $tutor->city ? ' - ' . $tutor->city : '' }}{{ $tutor->state ? '/' . $tutor->state : '' }}</td>
            </tr>
        </table>
        @empty
        <p style="margin: 0; color: #777;">Nenhum tutor vinculado.</p>
        @endforelse
    </div>
</div>

@if($pet->allergies || $pet->notes || $pet->chronic_conditions)
<div class="section-box">
    <div class="section-title" style="background: #fff3cd; color: #856404;">3. Alertas Médicos, Alergias e Condições Especiais</div>
    <div class="section-body">
        @if($pet->allergies)
            <p style="margin: 0 0 4px 0;"><strong>Alergias:</strong> {{ $pet->allergies }}</p>
        @endif
        @if($pet->chronic_conditions)
            <p style="margin: 0 0 4px 0;"><strong>Condições Crônicas:</strong> {{ $pet->chronic_conditions }}</p>
        @endif
        @if($pet->notes)
            <p style="margin: 0;"><strong>Observações Gerais:</strong> {{ $pet->notes }}</p>
        @endif
    </div>
</div>
@endif

{{-- HISTÓRICO DE VACINAÇÃO --}}
<div class="section-box">
    <div class="section-title">4. Histórico de Vacinações</div>
    <div class="section-body" style="padding: 0 10px;">
        <table class="table-doc">
            <thead>
                <tr>
                    <th style="width: 25%;">Vacina</th>
                    <th style="width: 15%;">Data Aplicação</th>
                    <th style="width: 15%;">Próxima Dose</th>
                    <th style="width: 15%;">Lote/Lab</th>
                    <th style="width: 30%;">Veterinário / Obs</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pet->vaccinations as $vax)
                <tr>
                    <td><strong>{{ $vax->name ?? $vax->vaccine_name }}</strong></td>
                    <td>{{ $vax->applied_at ? \Carbon\Carbon::parse($vax->applied_at)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $vax->next_due_date ? \Carbon\Carbon::parse($vax->next_due_date)->format('d/m/Y') : ($vax->revaccination_at ? \Carbon\Carbon::parse($vax->revaccination_at)->format('d/m/Y') : '-') }}</td>
                    <td>{{ $vax->batch ?? '-' }}</td>
                    <td>{{ $vax->veterinarian->name ?? $vax->notes ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center" style="color: #777;">Nenhuma vacina registrada.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- HISTÓRICO DE VERMÍFUGOS E ANTIPARASITÁRIOS --}}
<div class="section-box">
    <div class="section-title">5. Controle de Parasitas e Vermifugação</div>
    <div class="section-body" style="padding: 0 10px;">
        <table class="table-doc">
            <thead>
                <tr>
                    <th style="width: 25%;">Produto / Princípio Ativo</th>
                    <th style="width: 15%;">Tipo</th>
                    <th style="width: 15%;">Data Aplicação</th>
                    <th style="width: 15%;">Próxima Aplicação</th>
                    <th style="width: 15%;">Dose / Lote</th>
                    <th style="width: 15%;">Resp.</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pet->parasiteControls as $pc)
                <tr>
                    <td><strong>{{ $pc->product_name }}</strong> @if($pc->active_ingredient) <br><small style="color:#555;">{{ $pc->active_ingredient }}</small> @endif</td>
                    <td>{{ ucfirst($pc->type ?? 'Antiparasitário') }}</td>
                    <td>{{ $pc->application_date ? $pc->application_date->format('d/m/Y') : '-' }}</td>
                    <td>{{ $pc->next_due_date ? $pc->next_due_date->format('d/m/Y') : '-' }}</td>
                    <td>{{ $pc->dose ?? '-' }} @if($pc->batch) / Lote: {{ $pc->batch }} @endif</td>
                    <td>{{ $pc->vet->name ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center" style="color: #777;">Nenhum controle antiparasitário registrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- HISTÓRICO DE PESAGENS --}}
@if($pet->weightRecords && $pet->weightRecords->isNotEmpty())
<div class="section-box">
    <div class="section-title">6. Histórico de Pesagens Recentes</div>
    <div class="section-body" style="padding: 0 10px;">
        <table class="table-doc">
            <thead>
                <tr>
                    <th style="width: 25%;">Data</th>
                    <th style="width: 25%;">Peso</th>
                    <th style="width: 25%;">Escore Corporal (ECC)</th>
                    <th style="width: 25%;">Aferido por</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pet->weightRecords as $wr)
                <tr>
                    <td>{{ $wr->measurement_date ? $wr->measurement_date->format('d/m/Y') : '-' }}</td>
                    <td><strong>{{ number_format($wr->weight, 2, ',', '.') }} kg</strong></td>
                    <td>{{ $wr->bcs ? $wr->bcs . '/9' : '-' }}</td>
                    <td>{{ $wr->measuredBy->name ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- HISTÓRICO CLÍNICO RESUMIDO --}}
<div class="section-box">
    <div class="section-title">7. Atendimentos Clínicos Recentes</div>
    <div class="section-body" style="padding: 0 10px;">
        <table class="table-doc">
            <thead>
                <tr>
                    <th style="width: 15%;">Data</th>
                    <th style="width: 25%;">Queixa / Motivo</th>
                    <th style="width: 30%;">Diagnóstico / Conduta</th>
                    <th style="width: 30%;">Veterinário Responsável</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pet->medicalRecords as $mr)
                <tr>
                    <td>{{ $mr->record_date ? \Carbon\Carbon::parse($mr->record_date)->format('d/m/Y') : $mr->created_at->format('d/m/Y') }}</td>
                    <td>{{ $mr->chief_complaint ?? $mr->anamnesis ?? '-' }}</td>
                    <td><strong>{{ $mr->diagnosis ?? '-' }}</strong> @if($mr->treatment) <br><small>{{ Str::limit($mr->treatment, 80) }}</small> @endif</td>
                    <td>{{ $mr->veterinarian->name ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center" style="color: #777;">Nenhum atendimento clínico registrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
