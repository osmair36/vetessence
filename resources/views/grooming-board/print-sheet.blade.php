@extends('layouts.print', [
    'title' => 'Programação do Dia — Banho & Tosa (' . \Carbon\Carbon::parse($date)->format('d/m/Y') . ')',
    'subtitle' => 'Data: ' . \Carbon\Carbon::parse($date)->format('d/m/Y') . ' — Total de ' . $appointments->flatten()->count() . ' agendamento(s)',
    'docTitle' => 'Programacao Banho e Tosa ' . \Carbon\Carbon::parse($date)->format('d-m-Y'),
    'pageOrientation' => 'landscape',
    'pdfUrl' => $pdfUrl ?? route('grooming-board.pdf', ['date' => $date]),
])

@section('content')
<table class="table-doc">
    <thead>
        <tr>
            <th style="width: 7%; text-align: center;">Horário</th>
            <th style="width: 15%;">Paciente / Pet</th>
            <th style="width: 15%;">Espécie / Raça</th>
            <th style="width: 18%;">Tutor & Contato</th>
            <th style="width: 15%;">Serviços / Procedimento</th>
            <th style="width: 12%;">Tosador / Profissional</th>
            <th style="width: 9%; text-align: center;">Status</th>
            <th style="width: 9%; text-align: center;">Conferência</th>
        </tr>
    </thead>
    <tbody>
        @forelse($appointmentsList as $appt)
        <tr>
            <td style="text-align: center;" class="font-bold">
                {{ \Carbon\Carbon::parse($appt->time)->format('H:i') }}
            </td>
            <td>
                <span class="font-bold" style="font-size: 12px;">{{ $appt->pet->name ?? '-' }}</span>
                @if($appt->pet && $appt->pet->allergies)
                    <br><span class="badge badge-danger" style="font-size: 8px;">ALÉRGICO</span>
                @endif
            </td>
            <td>{{ $appt->pet->species ?? '-' }} / {{ $appt->pet->breed ?? '-' }}</td>
            <td>
                <strong>{{ $appt->pet->tutors->first()->name ?? '-' }}</strong><br>
                <small>{{ $appt->pet->tutors->first()->phone ?? '-' }}</small>
            </td>
            <td>
                @if($appt->services && $appt->services->count() > 0)
                    {{ $appt->services->map(fn($s) => $s->service->name ?? $s->name)->implode(', ') }}
                @else
                    {{ $appt->reason ?? 'Banho e Tosa' }}
                @endif
                @if($appt->notes)
                    <br><small class="text-muted"><em>Obs: {{ Str::limit($appt->notes, 40) }}</em></small>
                @endif
            </td>
            <td>{{ $appt->groomer->name ?? $appt->vet->name ?? 'A definir' }}</td>
            <td style="text-align: center;">
                @php
                    $statusLabels = [
                        'scheduled' => 'Agendado',
                        'checked_in' => 'Chegou',
                        'in_progress' => 'Em Atend.',
                        'completed' => 'Pronto',
                        'delivered' => 'Entregue',
                        'cancelled' => 'Cancelado',
                    ];
                @endphp
                <span class="badge badge-info">{{ $statusLabels[$appt->status] ?? $appt->status }}</span>
            </td>
            <td style="text-align: center;">
                [ &nbsp; ]
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" style="text-align: center; padding: 25px; color: #666;">
                Nenhum agendamento de Banho & Tosa para a data selecionada ({{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}).
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top: 20px; display: flex; justify-content: space-between; font-size: 10px; color: #666;">
    <div>Documento gerado em: {{ now()->format('d/m/Y H:i') }} | Sistema Amigo Bicho</div>
    <div>Visto do Responsável pelo Setor: ___________________________________</div>
</div>
@endsection
