@extends('layouts.print')

@section('content')
<div class="section-box">
    <div class="section-title">Ficha de Hospedagem #{{ $boarding->id }}</div>
    <div class="section-body">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-cell" style="width:50%">
                    <span class="info-label">Animal:</span><br>
                    <span class="info-value">{{ $boarding->pet->name ?? '-' }}</span>
                </div>
                <div class="info-cell" style="width:50%">
                    <span class="info-label">Espécie / Raça:</span><br>
                    <span class="info-value">{{ $boarding->pet->species ?? '-' }} / {{ $boarding->pet->breed ?? '-' }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-cell" style="width:50%">
                    <span class="info-label">Tutor:</span><br>
                    <span class="info-value">{{ $boarding->pet->tutor->name ?? '-' }}</span>
                </div>
                <div class="info-cell" style="width:50%">
                    <span class="info-label">Contato para Busca:</span><br>
                    <span class="info-value">{{ $boarding->pickup_contact ?? '-' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="section-box">
    <div class="section-title">Período de Hospedagem</div>
    <div class="section-body">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Tipo:</span><br>
                    <span class="info-value">
                        @if($boarding->type === 'boarding') Hospedagem
                        @elseif($boarding->type === 'grooming') Banho/Tosa
                        @else Hospedagem + Banho/Tosa @endif
                    </span>
                </div>
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Check-in:</span><br>
                    <span class="info-value">{{ $boarding->check_in_at ? \Carbon\Carbon::parse($boarding->check_in_at)->format('d/m/Y H:i') : '-' }}</span>
                </div>
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Check-out Previsto:</span><br>
                    <span class="info-value">{{ $boarding->expected_check_out ? \Carbon\Carbon::parse($boarding->expected_check_out)->format('d/m/Y H:i') : '-' }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Status:</span><br>
                    <span class="info-value">
                        @if($boarding->status === 'checked_in') <span class="badge badge-success">Hospedado</span>
                        @elseif($boarding->status === 'checked_out') <span class="badge badge-info">Saiu</span>
                        @else <span class="badge badge-warning">{{ $boarding->status }}</span> @endif
                    </span>
                </div>
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Diária:</span><br>
                    <span class="info-value">R$ {{ number_format($boarding->daily_rate ?? 0, 2, ',', '.') }}</span>
                </div>
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Total:</span><br>
                    <span class="info-value font-bold">R$ {{ number_format($boarding->total_amount ?? 0, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

@if($boarding->reason || $boarding->feeding_instructions || $boarding->medication_instructions)
<div class="section-box">
    <div class="section-title">Instruções</div>
    <div class="section-body">
        @if($boarding->reason)
        <p><strong>Motivo da Hospedagem:</strong><br>{{ $boarding->reason }}</p>
        @endif
        @if($boarding->feeding_instructions)
        <p><strong>Alimentação:</strong><br>{{ $boarding->feeding_instructions }}</p>
        @endif
        @if($boarding->medication_instructions)
        <p><strong>Medicação:</strong><br>{{ $boarding->medication_instructions }}</p>
        @endif
    </div>
</div>
@endif

@if($boarding->dailyTasks && $boarding->dailyTasks->count())
<div class="section-box">
    <div class="section-title">Tarefas Diárias</div>
    <div class="section-body">
        <table class="table-doc">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Tarefa</th>
                    <th>Status</th>
                    <th>Responsável</th>
                    <th>Observações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($boarding->dailyTasks as $task)
                <tr>
                    <td>{{ $task->task_date ? \Carbon\Carbon::parse($task->task_date)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $task->task_type ?? '-' }}</td>
                    <td>
                        @if($task->completed_at) <span class="badge badge-success">Concluída</span>
                        @else <span class="badge badge-warning">Pendente</span> @endif
                    </td>
                    <td>{{ $task->completedBy->name ?? '-' }}</td>
                    <td>{{ $task->notes ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if($boarding->notes)
<div class="section-box">
    <div class="section-title">Observações Gerais</div>
    <div class="section-body"><p>{{ $boarding->notes }}</p></div>
</div>
@endif

<div class="signature-area">
    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-line"></div>
                <div class="signature-name">Responsável pelo Animal</div>
                <div class="signature-sub">Tutor / Responsável</div>
            </td>
            <td>
                <div class="signature-line"></div>
                <div class="signature-name">{{ $boarding->createdBy->name ?? 'Atendente' }}</div>
                <div class="signature-sub">Responsável pelo Atendimento</div>
            </td>
        </tr>
    </table>
</div>
@endsection
