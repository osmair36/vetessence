<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Pet;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServicePriceTier;
use App\Models\Tutor;
use App\Models\PaymentGateway;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PdvController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:financeiro');
    }

    public function index()
    {
        $services = Service::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $gateways = PaymentGateway::where('is_active', true)
            ->where(fn($q) => $q->where('channel', 'pdv')->orWhere('channel', 'both'))
            ->get();

        return view('pdv.index', compact('services', 'products', 'gateways'));
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');

        $tutors = Tutor::where('name', 'like', "%{$query}%")
            ->orWhere('phone', 'like', "%{$query}%")
            ->orWhere('cpf', 'like', "%{$query}%")
            ->with('pets')
            ->limit(10)
            ->get()
            ->map(fn($t) => [
                'id'    => $t->id,
                'name'  => $t->name,
                'phone' => $t->phone,
                'pets'  => $t->pets->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'species' => $p->species, 'size' => $p->size ?? null]),
            ]);

        return response()->json($tutors);
    }

    public function serviceTiers(Request $request, Service $service)
    {
        $tiers = ServicePriceTier::where('service_id', $service->id)->get();

        return response()->json([
            'base_price' => $service->price,
            'tiers' => $tiers->map(fn($t) => [
                'species' => $t->species,
                'size'    => $t->size,
                'price'   => $t->price,
            ]),
        ]);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'items'          => 'required|array|min:1',
            'items.*.type'   => 'required|in:service,product',
            'items.*.id'     => 'required|integer',
            'items.*.qty'    => 'required|integer|min:1',
            'items.*.price'  => 'required|numeric|min:0',
            'items.*.name'   => 'required|string',
            'payment_method' => 'required|in:cash,card,pix,gateway',
            'tutor_id'       => 'nullable|exists:tutors,id',
            'pet_id'         => 'nullable|exists:pets,id',
            'gateway_id'     => 'nullable|exists:payment_gateways,id',
            'discount'       => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $tutor = $request->tutor_id ? Tutor::find($request->tutor_id) : null;

            $subtotal = collect($request->items)->sum(fn($i) => $i['price'] * $i['qty']);
            $discount = $request->discount ?? 0;
            $total    = max(0, $subtotal - $discount);

            // Cria a fatura
            $invoice = Invoice::create([
                'tutor_id'       => $tutor?->id,
                'pet_id'         => $request->pet_id,
                'status'         => 'pending',
                'discount'       => $discount,
                'total'          => $total,
                'notes'          => 'Venda PDV — ' . now()->format('d/m/Y H:i'),
                'due_date'       => today(),
                'branch_id'      => auth()->user()->branch_id ?? null,
                'created_by'     => auth()->id(),
            ]);

            // Adiciona os itens
            foreach ($request->items as $item) {
                InvoiceItem::create([
                    'invoice_id'  => $invoice->id,
                    'description' => $item['name'],
                    'quantity'    => $item['qty'],
                    'unit_price'  => $item['price'],
                    'total'       => $item['price'] * $item['qty'],
                    'type'        => $item['type'],
                    'reference_id' => $item['id'],
                ]);

                // Debita estoque para produtos
                if ($item['type'] === 'product') {
                    $product = Product::find($item['id']);
                    if ($product && $product->stock_quantity !== null) {
                        $product->decrement('stock_quantity', $item['qty']);
                    }
                }
            }

            // Pagamento em dinheiro ou PIX manual ? marca como pago
            if (in_array($request->payment_method, ['cash', 'pix'])) {
                $invoice->update(['status' => 'paid', 'paid_at' => now()]);
            }

            DB::commit();

            return response()->json([
                'success'    => true,
                'invoice_id' => $invoice->id,
                'invoice_url' => route('invoices.show', $invoice),
                'total'      => number_format($total, 2, ',', '.'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
