@extends('layouts.adminlte', ['title' => 'PDV — Ponto de Venda'])

@section('content')
<div class="row">
    {{-- Coluna esquerda: catálogo --}}
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cash-register mr-2"></i>Ponto de Venda</h3>
                <div class="card-tools">
                    <input type="text" id="pdv-search-tutor" class="form-control form-control-sm" placeholder="Buscar tutor / CPF / telefone…" autocomplete="off">
                </div>
            </div>
            <div class="card-body p-2">
                {{-- Resultado busca tutor --}}
                <div id="tutor-results" class="mb-2" style="display:none">
                    <div class="list-group" id="tutor-list"></div>
                </div>
                {{-- Tutor selecionado --}}
                <div id="tutor-selected" class="alert alert-info py-1 px-2 d-none">
                    <span id="tutor-name"></span>
                    &nbsp;|&nbsp; Pet:
                    <select id="pet-select" class="form-control form-control-sm d-inline-block w-auto"></select>
                    <button class="btn btn-xs btn-outline-secondary ml-2" id="clear-tutor">✕</button>
                    <input type="hidden" id="tutor-id">
                </div>

                {{-- Tabs Serviços / Produtos --}}
                <ul class="nav nav-tabs mt-2" id="catalogTab">
                    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-services">Serviços</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-products">Produtos</a></li>
                </ul>
                <div class="tab-content border border-top-0 p-2">
                    <div class="tab-pane fade show active" id="tab-services">
                        <div class="row row-cols-2 row-cols-md-3 g-2 mt-1">
                            @foreach($services as $svc)
                            <div class="col mb-2">
                                <div class="card card-sm h-100 cursor-pointer add-item"
                                    data-type="service"
                                    data-id="{{ $svc->id }}"
                                    data-name="{{ $svc->name }}"
                                    data-price="{{ $svc->price }}"
                                    data-tiers-url="{{ route('pdv.service-tiers', $svc) }}"
                                    style="cursor:pointer">
                                    <div class="card-body p-2 text-center">
                                        <i class="fas fa-paw fa-lg text-primary"></i>
                                        <p class="mb-0 mt-1 small font-weight-bold">{{ $svc->name }}</p>
                                        <span class="badge badge-success">R$ {{ number_format($svc->price, 2, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tab-products">
                        <div class="row row-cols-2 row-cols-md-3 g-2 mt-1">
                            @foreach($products as $prod)
                            <div class="col mb-2">
                                <div class="card card-sm h-100 add-item"
                                    data-type="product"
                                    data-id="{{ $prod->id }}"
                                    data-name="{{ $prod->name }}"
                                    data-price="{{ $prod->sale_price ?? $prod->cost_price ?? 0 }}"
                                    style="cursor:pointer">
                                    <div class="card-body p-2 text-center">
                                        <i class="fas fa-box fa-lg text-warning"></i>
                                        <p class="mb-0 mt-1 small font-weight-bold">{{ $prod->name }}</p>
                                        <span class="badge badge-warning">R$ {{ number_format($prod->sale_price ?? $prod->cost_price ?? 0, 2, ',', '.') }}</span>
                                        @if($prod->stock_quantity !== null)
                                        <br><small class="text-muted">Estoque: {{ $prod->stock_quantity }}</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Coluna direita: carrinho --}}
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h3 class="card-title"><i class="fas fa-shopping-cart mr-2"></i>Carrinho</h3>
            </div>
            <div class="card-body p-2">
                <table class="table table-sm table-hover" id="cart-table">
                    <thead class="thead-light">
                        <tr>
                            <th>Item</th>
                            <th class="text-center" style="width:60px">Qtd</th>
                            <th class="text-right" style="width:90px">Preço</th>
                            <th style="width:30px"></th>
                        </tr>
                    </thead>
                    <tbody id="cart-body">
                        <tr id="cart-empty-row"><td colspan="4" class="text-center text-muted py-3">Nenhum item</td></tr>
                    </tbody>
                </table>

                <hr>
                <div class="d-flex justify-content-between">
                    <span>Subtotal</span>
                    <strong id="subtotal">R$ 0,00</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <label class="mb-0">Desconto (R$)</label>
                    <input type="number" id="discount" min="0" step="0.01" value="0" class="form-control form-control-sm text-right" style="width:100px">
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <span class="font-weight-bold">Total</span>
                    <strong class="text-success" id="total">R$ 0,00</strong>
                </div>
            </div>
            <div class="card-footer">
                <div class="form-group mb-2">
                    <label>Forma de Pagamento</label>
                    <select id="payment-method" class="form-control">
                        <option value="cash">Dinheiro</option>
                        <option value="pix">PIX</option>
                        <option value="card">Cartão (manual)</option>
                        @foreach($gateways as $gw)
                        <option value="gateway" data-gateway="{{ $gw->id }}">{{ $gw->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-success btn-block btn-lg" id="checkout-btn">
                    <i class="fas fa-check-circle mr-1"></i> Finalizar Venda
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal serviço com tiers --}}
<div class="modal fade" id="tierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Selecionar Variação</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="tier-body"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ---- Estado ----
let cart = [];
let tutorData = null;
let searchTimer = null;

// ---- Busca Tutor ----
document.getElementById('pdv-search-tutor').addEventListener('input', function() {
    clearTimeout(searchTimer);
    const q = this.value.trim();
    if (q.length < 2) { document.getElementById('tutor-results').style.display='none'; return; }
    searchTimer = setTimeout(() => {
        fetch(`{{ route('pdv.search') }}?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(data => {
                const list = document.getElementById('tutor-list');
                list.innerHTML = '';
                data.forEach(t => {
                    const a = document.createElement('a');
                    a.href = '#';
                    a.className = 'list-group-item list-group-item-action py-1';
                    a.textContent = `${t.name} — ${t.phone ?? ''}`;
                    a.addEventListener('click', e => { e.preventDefault(); selectTutor(t); });
                    list.appendChild(a);
                });
                document.getElementById('tutor-results').style.display = data.length ? 'block' : 'none';
            });
    }, 300);
});

function selectTutor(t) {
    tutorData = t;
    document.getElementById('tutor-id').value = t.id;
    document.getElementById('tutor-name').textContent = t.name;
    const sel = document.getElementById('pet-select');
    sel.innerHTML = '<option value="">— sem pet —</option>';
    (t.pets || []).forEach(p => { sel.innerHTML += `<option value="${p.id}">${p.name} (${p.species})</option>`; });
    document.getElementById('tutor-selected').classList.remove('d-none');
    document.getElementById('tutor-results').style.display = 'none';
    document.getElementById('pdv-search-tutor').value = '';
}

document.getElementById('clear-tutor').addEventListener('click', () => {
    tutorData = null;
    document.getElementById('tutor-id').value = '';
    document.getElementById('tutor-selected').classList.add('d-none');
});

// ---- Adicionar item ao carrinho ----
document.querySelectorAll('.add-item').forEach(card => {
    card.addEventListener('click', function() {
        const type = this.dataset.type;
        const id = this.dataset.id;
        const name = this.dataset.name;
        const basePrice = parseFloat(this.dataset.price);
        const tiersUrl = this.dataset.tiersUrl;

        if (type === 'service' && tiersUrl) {
            fetch(tiersUrl).then(r => r.json()).then(data => {
                if (data.tiers && data.tiers.length) {
                    const petSel = document.getElementById('pet-select');
                    const selOption = petSel.options[petSel.selectedIndex];
                    // Try to find matching tier
                    let matchedPrice = data.base_price;
                    // Show tier modal
                    let html = `<p class="mb-2">Preço base: <strong>R$ ${parseFloat(data.base_price).toFixed(2).replace('.',',')}</strong></p><div class="list-group">`;
                    data.tiers.forEach(tier => {
                        html += `<button class="list-group-item list-group-item-action tier-select"
                            data-price="${tier.price}" data-name="${name} (${tier.species ?? ''} ${tier.size ?? ''})">
                            ${tier.species ?? ''} ${tier.size ?? ''} — R$ ${parseFloat(tier.price).toFixed(2).replace('.',',')}
                        </button>`;
                    });
                    html += `<button class="list-group-item list-group-item-action tier-select mt-1" data-price="${data.base_price}" data-name="${name}">Preço base — R$ ${parseFloat(data.base_price).toFixed(2).replace('.',',')}</button></div>`;
                    document.getElementById('tier-body').innerHTML = html;
                    document.querySelectorAll('.tier-select').forEach(btn => {
                        btn.addEventListener('click', () => {
                            addToCart(type, id, btn.dataset.name, parseFloat(btn.dataset.price));
                            $('#tierModal').modal('hide');
                        });
                    });
                    $('#tierModal').modal('show');
                } else {
                    addToCart(type, id, name, data.base_price);
                }
            });
        } else {
            addToCart(type, id, name, basePrice);
        }
    });
});

function addToCart(type, id, name, price) {
    const existing = cart.find(i => i.type === type && i.id == id && i.name === name);
    if (existing) { existing.qty++; }
    else { cart.push({ type, id, name, price, qty: 1 }); }
    renderCart();
}

function renderCart() {
    const tbody = document.getElementById('cart-body');
    if (cart.length === 0) {
        tbody.innerHTML = '<tr id="cart-empty-row"><td colspan="4" class="text-center text-muted py-3">Nenhum item</td></tr>';
    } else {
        tbody.innerHTML = cart.map((item, idx) => `
            <tr>
                <td class="small">${item.name}</td>
                <td class="text-center">
                    <input type="number" min="1" value="${item.qty}" class="form-control form-control-sm text-center qty-input" data-idx="${idx}" style="width:55px">
                </td>
                <td class="text-right small">R$ ${(item.price * item.qty).toFixed(2).replace('.',',')}</td>
                <td><button class="btn btn-xs btn-outline-danger remove-item" data-idx="${idx}">&times;</button></td>
            </tr>
        `).join('');
        tbody.querySelectorAll('.qty-input').forEach(input => {
            input.addEventListener('change', function() {
                cart[this.dataset.idx].qty = parseInt(this.value) || 1;
                renderCart();
            });
        });
        tbody.querySelectorAll('.remove-item').forEach(btn => {
            btn.addEventListener('click', function() {
                cart.splice(this.dataset.idx, 1);
                renderCart();
            });
        });
    }
    updateTotals();
}

function updateTotals() {
    const subtotal = cart.reduce((s, i) => s + i.price * i.qty, 0);
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    const total = Math.max(0, subtotal - discount);
    document.getElementById('subtotal').textContent = 'R$ ' + subtotal.toFixed(2).replace('.',',');
    document.getElementById('total').textContent = 'R$ ' + total.toFixed(2).replace('.',',');
}
document.getElementById('discount').addEventListener('input', updateTotals);

// ---- Checkout ----
document.getElementById('checkout-btn').addEventListener('click', function() {
    if (cart.length === 0) { alert('Adicione itens ao carrinho.'); return; }
    const paymentSel = document.getElementById('payment-method');
    const paymentMethod = paymentSel.value;
    const gatewayId = paymentSel.options[paymentSel.selectedIndex]?.dataset?.gateway ?? null;

    const payload = {
        items: cart.map(i => ({ type: i.type, id: i.id, name: i.name, qty: i.qty, price: i.price })),
        payment_method: paymentMethod === 'gateway' ? 'gateway' : paymentMethod,
        gateway_id: gatewayId,
        tutor_id: document.getElementById('tutor-id').value || null,
        pet_id: document.getElementById('pet-select')?.value || null,
        discount: parseFloat(document.getElementById('discount').value) || 0,
        _token: document.querySelector('meta[name=csrf-token]').content,
    };

    this.disabled = true;
    this.textContent = 'Processando…';

    fetch('{{ route("pdv.checkout") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': payload._token },
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (confirm(`Venda registrada! Total: R$ ${data.total}\n\nAbrir fatura?`)) {
                window.location.href = data.invoice_url;
            } else {
                cart = [];
                renderCart();
                document.getElementById('discount').value = 0;
            }
        } else {
            alert('Erro: ' + (data.message ?? 'Tente novamente.'));
        }
    })
    .catch(() => alert('Erro de comunicação.'))
    .finally(() => {
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Finalizar Venda';
    });
});
</script>
@endpush
