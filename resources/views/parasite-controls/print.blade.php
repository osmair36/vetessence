@extends('layouts.print', [
    'title' => 'Histórico de Vermifugação e Controle de Parasitas',
    'subtitle' => 'Paciente: ' . $pet->name . ' | Espécie: ' . ($pet->species ?? '-') . ' | Raça: ' . ($pet->breed ?? 'SRD'),
    'pdfUrl' => $pdfUrl ?? null,
    'docTitle' => 'Controle Parasitário - ' . $pet->name,
])

@section('content')
<div class="section-box">
    <div class="section-title">Dados do Paciente e Tutor</div>
    <div class="section-body">
        <table style="width: 100%; border: none; font-size: 11px;">
            <tr>
                <td style="width: 25%;"><span class="info-label">Paciente:</span> <br><strong style="font-size: 13px;">{{ $pet->name }}</strong></td>
                <td style="width: 25%;"><span class="info-label">Espécie / Raça:</span> <br>{{ ucfirst($pet->species ?? '-') }} / {{ $pet->breed ?? 'SRD' }}</td>
                <td style="width: 25%;"><span class="info-label">Sexo / Idade:</span> <br>{{ $pet->gender === 'male' ? 'Macho' : 'Fêmea' }} | {{ $pet->age ?? '-' }}</td>
                <td style="width: 25%;"><span class="info-label">Peso:</span> <br>{{ $pet->weight ? number_format($pet->weight, 2, ',', '.') . ' kg' : '-' }}</td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top: 6px;"><span class="info-label">Tutor(es):</span> <br><strong>{{ $pet->tutors->pluck('name')->join(', ') ?: '-' }}</strong></td>
                <td style="padding-top: 6px;"><span class="info-label">Contato:</span> <br>{{ $pet->tutors->first()?->phone ?? '-' }}</td>
                <td style="padding-top: 6px;"><span class="info-label">Microchip:</span> <br>{{ $pet->microchip ?? '-' }}</td>
            </tr>
        </table>
    </div>
</div>

<div class="section-box">
    <div class="section-title">Aplicações de Vermífugos e Antiparasitários Registradas</div>
    <div class="section-body" style="padding: 0 10px;">
        <table class="table-doc">
            <thead>
                <tr>
                    <th style="width: 24%;">Produto / Princípio Ativo</th>
                    <th style="width: 14%;">Tipo / Categoria</th>
                    <th style="width: 14%;">Data Aplicação</th>
                    <th style="width: 14%;">Próxima Dose</th>
                    <th style="width: 16%;">Dose / Lote</th>
                    <th style="width: 18%;">Veterinário Responsável</th>
                </tr>
            </thead>
            <tbody>
                @forelse($controls as $ctrl)
                <tr>
                    <td>
                        <strong>{{ $ctrl->product_name }}</strong>
                        @if($ctrl->active_ingredient)<br><small style="color: #555;">{{ $ctrl->active_ingredient }}</small>@endif
                    </td>
                    <td>{{ ucfirst($ctrl->type ?? 'Antiparasitário') }}</td>
                    <td>{{ $ctrl->application_date ? $ctrl->application_date->format('d/m/Y') : '-' }}</td>
                    <td>
                        <strong>{{ $ctrl->next_due_date ? $ctrl->next_due_date->format('d/m/Y') : '-' }}</strong>
                    </td>
                    <td>{{ $ctrl->dose ?? '-' }} @if($ctrl->batch)<br><small>Lote: {{ $ctrl->batch }}</small>@endif</td>
                    <td>{{ $ctrl->vet->name ?? 'Corpo Clínico' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center" style="color: #777;">Nenhuma aplicação de vermífugo ou antiparasitário registrada para este pet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="section-box" style="margin-top: 20px;">
    <div class="section-title">Orientações Clínicas para Controle de Parasitas</div>
    <div class="section-body" style="font-size: 10px; color: #444; line-height: 1.5;">
        • O controle contínuo contra pulgas, carrapatos e vermes é indispensável para prevenir a transmissão de hemoparasitoses graves (como Erliquiose, Anaplasmose e Babesiose) e zoonoses.<br>
        • Respeite rigorosamente a frequência de reaplicação indicada pelo médico veterinário para garantir cobertura ininterrupta.
    </div>
</div>

<div class="signature-area">
    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <div class="signature-line"></div>
                <div class="signature-name">{{ $pet->tutors->first()?->name ?? 'Tutor / Responsável' }}</div>
                <div class="signature-sub">Assinatura do Tutor</div>
            </td>
            <td style="width: 50%;">
                <div class="signature-line"></div>
                <div class="signature-name">Dra. Joelma Scarabelli Neves</div>
                <div class="signature-sub">Médica Veterinária — CRMV-PR 08147</div>
            </td>
        </tr>
    </table>
</div>
@endsection
