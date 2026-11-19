@extends('layouts.print', [
    'title' => 'Carteira de Vacinação Veterinária',
    'subtitle' => 'Paciente: ' . $pet->name . ' | Espécie: ' . ($pet->species ?? '-') . ' | Raça: ' . ($pet->breed ?? 'SRD'),
    'pdfUrl' => $pdfUrl ?? null,
    'docTitle' => 'Carteira de Vacinação - ' . $pet->name,
])

@section('content')
<div class="section-box">
    <div class="section-title">Dados de Identificação do Paciente e Tutor</div>
    <div class="section-body">
        <table style="width: 100%; border: none; font-size: 11px;">
            <tr>
                <td style="width: 25%;"><span class="info-label">Nome do Pet:</span> <br><strong style="font-size: 13px;">{{ $pet->name }}</strong></td>
                <td style="width: 25%;"><span class="info-label">Espécie / Raça:</span> <br>{{ ucfirst($pet->species ?? '-') }} / {{ $pet->breed ?? 'SRD' }}</td>
                <td style="width: 25%;"><span class="info-label">Sexo / Idade:</span> <br>{{ $pet->gender === 'male' ? 'Macho' : 'Fêmea' }} | {{ $pet->age ?? '-' }}</td>
                <td style="width: 25%;"><span class="info-label">Microchip:</span> <br>{{ $pet->microchip ?? '-' }}</td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top: 6px;"><span class="info-label">Tutor(es):</span> <br><strong>{{ $pet->tutors->pluck('name')->join(', ') ?: '-' }}</strong></td>
                <td style="padding-top: 6px;"><span class="info-label">Telefone:</span> <br>{{ $pet->tutors->first()?->phone ?? '-' }}</td>
                <td style="padding-top: 6px;"><span class="info-label">Peso Atual:</span> <br>{{ $pet->weight ? number_format($pet->weight, 2, ',', '.') . ' kg' : '-' }}</td>
            </tr>
        </table>
    </div>
</div>

<div class="section-box">
    <div class="section-title">Registro de Imunizações e Vacinas Aplicadas</div>
    <div class="section-body" style="padding: 0 10px;">
        <table class="table-doc">
            <thead>
                <tr>
                    <th style="width: 22%;">Vacina / Imunizante</th>
                    <th style="width: 14%;">Data Aplicação</th>
                    <th style="width: 14%;">Próxima Dose</th>
                    <th style="width: 15%;">Lote / Laboratório</th>
                    <th style="width: 20%;">Veterinário Responsável</th>
                    <th style="width: 15%;">Rubrica / Visto</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vaccinations as $vax)
                <tr>
                    <td><strong>{{ $vax->vaccine ?? $vax->name ?? $vax->vaccine_name ?? '-' }}</strong></td>
                    <td>{{ $vax->applied_at ? \Carbon\Carbon::parse($vax->applied_at)->format('d/m/Y') : ($vax->date ? \Carbon\Carbon::parse($vax->date)->format('d/m/Y') : '-') }}</td>
                    <td>
                        @php
                            $next = $vax->next_date ?? $vax->next_due_date ?? $vax->revaccination_at;
                        @endphp
                        <strong>{{ $next ? \Carbon\Carbon::parse($next)->format('d/m/Y') : '-' }}</strong>
                    </td>
                    <td>{{ $vax->batch ?? $vax->lot ?? '-' }} @if($vax->laboratory ?? $vax->manufacturer)<br><small>{{ $vax->laboratory ?? $vax->manufacturer }}</small>@endif</td>
                    <td>{{ $vax->veterinarian->name ?? $vax->vet->name ?? 'Dra. Joelma Scarabelli Neves' }}</td>
                    <td class="text-center" style="font-size: 9px; color: #888;">[ Assinado ]</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center" style="color: #777;">Nenhuma vacina registrada na carteira deste pet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="section-box" style="margin-top: 20px;">
    <div class="section-title">Observações Importantes sobre o Esquema Vacinal</div>
    <div class="section-body" style="font-size: 10px; color: #444; line-height: 1.5;">
        • Mantenha a vacinação do seu animal rigorosamente em dia para garantir a proteção contra zoonoses e viroses graves.<br>
        • Não exponha filhotes a passeios na rua ou contato com outros animais antes de completar 15 a 21 dias após a última dose do protocolo inicial.<br>
        • Em caso de dúvidas ou reações adversas pós-vacinais, contate imediatamente a equipe clínica da <strong>{{ branding_clinic_name() }}</strong>.
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
