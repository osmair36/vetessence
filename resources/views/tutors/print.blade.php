@extends('layouts.print', [
    'title' => 'Ficha Cadastral do Tutor / Cliente',
    'subtitle' => 'Tutor: ' . $tutor->name . ($tutor->cpf ? ' | CPF: ' . $tutor->cpf : ''),
    'pdfUrl' => $pdfUrl ?? null,
    'docTitle' => 'Ficha - ' . $tutor->name,
])

@section('content')
<div class="section-box">
    <div class="section-title">1. Dados Pessoais e de Contato</div>
    <div class="section-body">
        <table style="width: 100%; border: none; font-size: 11px;">
            <tr>
                <td style="width: 40%;"><span class="info-label">Nome Completo:</span> <br><strong style="font-size: 13px;">{{ $tutor->name }}</strong></td>
                <td style="width: 30%;"><span class="info-label">CPF:</span> <br>{{ $tutor->cpf ?? '-' }}</td>
                <td style="width: 30%;"><span class="info-label">RG / Documento:</span> <br>{{ $tutor->rg ?? $tutor->document ?? '-' }}</td>
            </tr>
            <tr>
                <td style="padding-top: 8px;"><span class="info-label">Telefone Principal:</span> <br>{{ $tutor->phone ?? '-' }}</td>
                <td style="padding-top: 8px;"><span class="info-label">Celular / WhatsApp:</span> <br>{{ $tutor->mobile ?? $tutor->whatsapp ?? $tutor->phone ?? '-' }}</td>
                <td style="padding-top: 8px;"><span class="info-label">E-mail:</span> <br>{{ $tutor->email ?? '-' }}</td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top: 8px;"><span class="info-label">Endereço Residencial:</span> <br>{{ $tutor->address ?? '-' }}{{ $tutor->number ? ', ' . $tutor->number : '' }}{{ $tutor->complement ? ' (' . $tutor->complement . ')' : '' }}{{ $tutor->neighborhood ? ' - ' . $tutor->neighborhood : '' }}</td>
                <td style="padding-top: 8px;"><span class="info-label">Cidade / UF / CEP:</span> <br>{{ $tutor->city ?? '-' }} / {{ $tutor->state ?? '-' }} @if($tutor->zip_code) - CEP: {{ $tutor->zip_code }} @endif</td>
            </tr>
            <tr>
                <td colspan="3" style="padding-top: 8px;"><span class="info-label">Data de Cadastro:</span> {{ $tutor->created_at ? $tutor->created_at->format('d/m/Y H:i') : '-' }}</td>
            </tr>
        </table>
    </div>
</div>

<div class="section-box">
    <div class="section-title">2. Animais Vinculados ao Tutor ({{ $tutor->pets->count() }})</div>
    <div class="section-body" style="padding: 0 10px;">
        <table class="table-doc">
            <thead>
                <tr>
                    <th style="width: 25%;">Nome do Pet</th>
                    <th style="width: 15%;">Espécie</th>
                    <th style="width: 20%;">Raça</th>
                    <th style="width: 10%;">Sexo</th>
                    <th style="width: 15%;">Idade / Peso</th>
                    <th style="width: 15%;">Microchip</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tutor->pets as $pet)
                <tr>
                    <td><strong>{{ $pet->name }}</strong></td>
                    <td>{{ ucfirst($pet->species ?? '-') }}</td>
                    <td>{{ $pet->breedRelation?->name ?? $pet->breed ?? 'SRD' }}</td>
                    <td>{{ $pet->gender === 'male' ? 'Macho' : ($pet->gender === 'female' ? 'Fêmea' : '-') }}</td>
                    <td>{{ $pet->age ?? '-' }} @if($pet->weight) ({{ number_format($pet->weight, 2, ',', '.') }} kg) @endif</td>
                    <td>{{ $pet->microchip ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center" style="color: #777;">Nenhum animal vinculado a este tutor.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($tutor->notes)
<div class="section-box">
    <div class="section-title">3. Observações Cadastrais</div>
    <div class="section-body">
        <p style="margin: 0;">{{ $tutor->notes }}</p>
    </div>
</div>
@endif

<div class="section-box" style="margin-top: 20px;">
    <div class="section-title">Declaração e Ciência do Tutor</div>
    <div class="section-body" style="font-size: 10px; color: #444; line-height: 1.5;">
        Declaro que as informações cadastrais e de contato acima prestadas são verdadeiras e me comprometo a mantê-las atualizadas junto à clínica veterinária {{ branding_clinic_name() }}. Autorizo a realização dos procedimentos clínicos, exames e tratamentos recomendados pela equipe médica-veterinária quando formalmente acordados.
    </div>
</div>

<div class="signature-area">
    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <div class="signature-line"></div>
                <div class="signature-name">{{ $tutor->name }}</div>
                <div class="signature-sub">Tutor / Responsável Legal</div>
            </td>
            <td style="width: 50%;">
                <div class="signature-line"></div>
                <div class="signature-name">{{ branding_clinic_name() }}</div>
                <div class="signature-sub">Atendimento / Recepção</div>
            </td>
        </tr>
    </table>
</div>
@endsection
