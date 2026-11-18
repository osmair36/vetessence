@extends('layouts.print')

@section('content')
<div class="section-box">
    <div class="section-title">Relatório de Solicitações de Folga e Afastamentos</div>
    <div class="section-body">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Total de Solicitações:</span><br>
                    <span class="info-value font-bold">{{ $timeOffs->count() }}</span>
                </div>
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Aprovadas:</span><br>
                    <span class="info-value font-bold" style="color:#1a7f3c;">{{ $timeOffs->where('status', 'approved')->count() }}</span>
                </div>
                <div class="info-cell" style="width:33%">
                    <span class="info-label">Pendentes:</span><br>
                    <span class="info-value font-bold" style="color:#b27b00;">{{ $timeOffs->where('status', 'pending')->count() }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="section-box">
    <div class="section-title">Detalhamento das Solicitações</div>
    <div class="section-body">
        @if($timeOffs->count() > 0)
        <table class="table-doc">
            <thead>
                <tr>
                    <th>Funcionário</th>
                    <th>Início</th>
                    <th>Término</th>
                    <th>Tipo</th>
                    <th>Status</th>
                    <th>Aprovado Por</th>
                    <th>Motivo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($timeOffs as $timeOff)
                <tr>
                    <td><strong>{{ $timeOff->user->name ?? '-' }}</strong></td>
                    <td>{{ $timeOff->start_date ? $timeOff->start_date->format('d/m/Y') : '-' }}</td>
                    <td>{{ $timeOff->end_date ? $timeOff->end_date->format('d/m/Y') : '-' }}</td>
                    <td>
                        @if($timeOff->type === 'vacation') Férias
                        @elseif($timeOff->type === 'sick') Licença Médica
                        @elseif($timeOff->type === 'personal') Folga Pessoal
                        @else {{ ucfirst($timeOff->type) }} @endif
                    </td>
                    <td>
                        @if($timeOff->status === 'approved') <span class="badge badge-success">Aprovado</span>
                        @elseif($timeOff->status === 'pending') <span class="badge badge-warning">Pendente</span>
                        @else <span class="badge badge-danger">Rejeitado</span> @endif
                    </td>
                    <td>{{ $timeOff->approvedBy->name ?? '—' }}</td>
                    <td>{{ $timeOff->reason ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="text-align:center; color:#888;">Nenhuma solicitação encontrada.</p>
        @endif
    </div>
</div>
@endsection
