@extends('layouts.adminlte', ['title' => 'Produtos'])

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Produtos ({{ $products->total() }})</h3>
        <div class="card-tools">
            <a href="{{ route('products.inventory.print', request()->query()) }}" target="_blank" class="btn btn-secondary btn-sm">
                <i class="fas fa-print"></i> Imprimir Inventário
            </a>
            <a href="{{ route('products.inventory.pdf', request()->query()) }}" class="btn btn-success btn-sm">
                <i class="fas fa-file-pdf"></i> Baixar PDF
            </a>
            <button onclick="openCreateModal()" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Novo
            </button>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('products.index') }}" class="mb-3">
            <div class="row">
                <div class="col-md-5 mb-2">
                    <input type="text" name="search" class="form-control" placeholder="Buscar por produto, SKU ou código de barras..." value="{{ request('search') }}">
                </div>
                <div class="col-md-4 mb-2">
                    <select name="category_id" class="form-control">
                        <option value="">Todas as Categorias</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2 d-flex">
                    <button type="submit" class="btn btn-primary mr-1"><i class="fas fa-search"></i> Filtrar</button>
                    @if(request('search') || request('category_id'))
                        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary"><i class="fas fa-times"></i> Limpar</a>
                    @endif
                </div>
            </div>
        </form>

        @if($products->count() > 0)
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Produto</th>
                    <th>Lote</th>
                    <th>Estoque</th>
                    <th>Preço</th>
                    <th>Validade</th>
                    <th>Status</th>
                    <th style="width: 120px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $prod)
                <tr>
                    <td><code>{{ $prod->sku }}</code></td>
                    <td>
                        <strong>{{ $prod->name }}</strong>
                        @if($prod->isLowStock)
                            <span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Baixo</span>
                        @endif
                    </td>
                    <td>{{ $prod->batch_number ?? $prod->lot_number ?? '-' }}</td>
                    <td class="{{ $prod->isLowStock ? 'text-danger font-weight-bold' : '' }}">{{ $prod->stock }}</td>
                    <td>R$ {{ number_format($prod->sale_price, 2, ',', '.') }}</td>
                    <td data-order="{{ $prod->expiration_date?->format('Y-m-d') }}">
                        @if($prod->expiration_date)
                            @php $diff = now()->diffInDays($prod->expiration_date, false); @endphp
                            <span class="badge {{ $diff <= 0 ? 'badge-danger' : ($diff <= 30 ? 'badge-warning' : 'badge-secondary') }}">
                                {{ $prod->expiration_date->format('d/m/Y') }}
                                @if($diff <= 0) <i class="fas fa-times-circle"></i> Vencido
                                @elseif($diff <= 30) <i class="fas fa-clock"></i> {{ $diff }}d
                                @endif
                            </span>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($prod->is_active)
                            <span class="badge badge-success">Ativo</span>
                        @else
                            <span class="badge badge-danger">Inativo</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('products.show', $prod) }}" class="btn btn-action btn-info" title="Visualizar">
                            <i class="fas fa-eye"></i>
                        </a>
                        <button onclick="openEditModal({{ $prod->id }})" class="btn btn-action btn-primary" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="text-center text-muted">Nenhum registro encontrado.</p>
        @endif
    </div>
    @if($products->hasPages())
    <div class="card-footer clearfix">
        {{ $products->withQueryString()->links() }}
    </div>
    @endif
</div>

<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productModalTitle">Novo Produto</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                @livewire('product-form', key('product-form'))
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', function() {
        Livewire.on('close-modal', function() { $('#productModal').modal('hide'); });
        Livewire.on('product-saved', function() { location.reload(); });
    });
    function openCreateModal() {
        Livewire.dispatch('resetForm');
        document.getElementById('productModalTitle').textContent = 'Novo Produto';
        $('#productModal').modal('show');
    }
    function openEditModal(id) {
        Livewire.dispatch('editProduct', { id: id });
        document.getElementById('productModalTitle').textContent = 'Editar Produto';
        $('#productModal').modal('show');
    }
</script>
@endpush
