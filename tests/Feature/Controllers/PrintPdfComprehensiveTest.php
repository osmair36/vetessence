<?php

namespace Tests\Feature\Controllers;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Category;
use App\Models\ConsentForm;
use App\Models\ConsentTemplate;
use App\Models\ControlledSubstance;
use App\Models\ControlledSubstanceLog;
use App\Models\HealthCertificate;
use App\Models\Hospitalization;
use App\Models\ImagingExam;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LaboratoryOrder;
use App\Models\LaboratoryTest;
use App\Models\MedicalRecord;
use App\Models\ParasiteControl;
use App\Models\Pet;
use App\Models\PetShopPackage;
use App\Models\PetShopSubscription;
use App\Models\PetTransport;
use App\Models\Prescription;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Surgery;
use App\Models\Tutor;
use App\Models\User;
use App\Models\Vaccination;
use Tests\ModuleTestCase;

class PrintPdfComprehensiveTest extends ModuleTestCase
{
    protected User $user;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->loginAs('super-admin');
        $this->branch = Branch::first() ?? Branch::factory()->create();
    }

    public function test_pet_print_and_pdf()
    {
        $pet = Pet::first() ?? Pet::factory()->create();

        $resPrint = $this->get(route('pets.print', $pet));
        $resPrint->assertOk();
        $this->assertStringContainsString('Dados Cadastrais do Animal', $resPrint->getContent());

        $resPdf = $this->get(route('pets.pdf', $pet));
        $resPdf->assertOk();
        $this->assertEquals('application/pdf', $resPdf->headers->get('content-type'));
    }

    public function test_tutor_print_and_pdf()
    {
        $tutor = Tutor::first() ?? Tutor::factory()->create();

        $resPrint = $this->get(route('tutors.print', $tutor));
        $resPrint->assertOk();
        $this->assertStringContainsString('Ficha Cadastral do Tutor', $resPrint->getContent());

        $resPdf = $this->get(route('tutors.pdf', $tutor));
        $resPdf->assertOk();
        $this->assertEquals('application/pdf', $resPdf->headers->get('content-type'));
    }

    public function test_medical_record_print_and_pdf()
    {
        $pet = Pet::first() ?? Pet::factory()->create();
        $mr = MedicalRecord::where('pet_id', $pet->id)->first() ?? MedicalRecord::create([
            'pet_id' => $pet->id,
            'user_id' => $this->user->id,
            'date' => now(),
            'type' => 'consulta',
            'chief_complaint' => 'Consulta de rotina',
        ]);

        $resPrint = $this->get(route('medical-records.print', $mr));
        $resPrint->assertOk();

        $resPdf = $this->get(route('medical-records.pdf', $mr));
        $resPdf->assertOk();
        $this->assertEquals('application/pdf', $resPdf->headers->get('content-type'));

        $resHistoryPrint = $this->get(route('pets.medical-records.print', $pet));
        $resHistoryPrint->assertOk();

        $resHistoryPdf = $this->get(route('pets.medical-records.pdf', $pet));
        $resHistoryPdf->assertOk();
        $this->assertEquals('application/pdf', $resHistoryPdf->headers->get('content-type'));
    }

    public function test_vaccination_print_and_pdf()
    {
        $pet = Pet::first() ?? Pet::factory()->create();

        $resCardPrint = $this->get(route('pets.vaccinations.card.print', $pet));
        $resCardPrint->assertOk();

        $resCardPdf = $this->get(route('pets.vaccinations.card.pdf', $pet));
        $resCardPdf->assertOk();
        $this->assertEquals('application/pdf', $resCardPdf->headers->get('content-type'));

        $resReportPrint = $this->get(route('vaccinations.report.print'));
        $resReportPrint->assertOk();

        $resReportPdf = $this->get(route('vaccinations.report.pdf'));
        $resReportPdf->assertOk();
        $this->assertEquals('application/pdf', $resReportPdf->headers->get('content-type'));
    }

    public function test_parasite_control_print_and_pdf()
    {
        $pet = Pet::first() ?? Pet::factory()->create();

        $resPrint = $this->get(route('pets.parasite-controls.print', $pet));
        $resPrint->assertOk();

        $resPdf = $this->get(route('pets.parasite-controls.pdf', $pet));
        $resPdf->assertOk();
        $this->assertEquals('application/pdf', $resPdf->headers->get('content-type'));

        $resReportPrint = $this->get(route('parasite-controls.report.print'));
        $resReportPrint->assertOk();

        $resReportPdf = $this->get(route('parasite-controls.report.pdf'));
        $resReportPdf->assertOk();
        $this->assertEquals('application/pdf', $resReportPdf->headers->get('content-type'));
    }

    public function test_lab_order_print_and_pdf()
    {
        $pet = Pet::first() ?? Pet::factory()->create();
        $order = LaboratoryOrder::create([
            'pet_id' => $pet->id,
            'requester_id' => $this->user->id,
            'order_number' => 'LAB-TEST-' . rand(100, 999),
            'status' => 'completed',
            'order_date' => now(),
        ]);
        LaboratoryTest::create([
            'laboratory_order_id' => $order->id,
            'test_name' => 'Hemograma Completo',
            'status' => 'completed',
        ]);

        $resPrint = $this->get(route('laboratory-orders.print', $order));
        $resPrint->assertOk();

        $resPdf = $this->get(route('laboratory-orders.pdf', $order));
        $resPdf->assertOk();
        $this->assertEquals('application/pdf', $resPdf->headers->get('content-type'));
    }

    public function test_imaging_exam_print_and_pdf()
    {
        $pet = Pet::first() ?? Pet::factory()->create();
        $exam = ImagingExam::create([
            'pet_id' => $pet->id,
            'vet_id' => $this->user->id,
            'exam_number' => 'IMG-TEST-' . rand(100, 999),
            'exam_type' => 'radiografia',
            'region' => 'Torax',
            'status' => 'reported',
            'exam_date' => now(),
            'findings' => 'Sem achados patológicos',
        ]);

        $resPrint = $this->get(route('imaging-exams.print', $exam));
        $resPrint->assertOk();

        $resPdf = $this->get(route('imaging-exams.pdf', $exam));
        $resPdf->assertOk();
        $this->assertEquals('application/pdf', $resPdf->headers->get('content-type'));
    }

    public function test_surgery_print_and_pdf()
    {
        $pet = Pet::first() ?? Pet::factory()->create();
        $surgery = Surgery::create([
            'pet_id' => $pet->id,
            'surgeon_id' => $this->user->id,
            'surgery_type' => 'eletiva',
            'scheduled_date' => now(),
            'status' => 'completed',
        ]);

        $resPrint = $this->get(route('surgeries.print', $surgery));
        $resPrint->assertOk();

        $resPdf = $this->get(route('surgeries.pdf', $surgery));
        $resPdf->assertOk();
        $this->assertEquals('application/pdf', $resPdf->headers->get('content-type'));
    }

    public function test_hospitalization_print_and_pdf()
    {
        $pet = Pet::first() ?? Pet::factory()->create();
        $hosp = Hospitalization::create([
            'pet_id' => $pet->id,
            'vet_id' => $this->user->id,
            'admission_date' => now(),
            'status' => 'active',
            'admission_reason' => 'Observação clínica pós-cirúrgica',
        ]);

        $resPrint = $this->get(route('hospitalizations.print', $hosp));
        $resPrint->assertOk();

        $resPdf = $this->get(route('hospitalizations.pdf', $hosp));
        $resPdf->assertOk();
        $this->assertEquals('application/pdf', $resPdf->headers->get('content-type'));
    }

    public function test_consent_form_print_and_pdf()
    {
        $pet = Pet::first() ?? Pet::factory()->create();
        $tutor = Tutor::first() ?? Tutor::factory()->create();
        $template = ConsentTemplate::first() ?? ConsentTemplate::create([
            'name' => 'Termo Anestésico Teste',
            'category' => 'cirurgia',
            'content' => '<p>Conteúdo do termo</p>',
        ]);

        $cf = ConsentForm::create([
            'consent_number' => ConsentForm::generateNumber(),
            'pet_id' => $pet->id,
            'tutor_id' => $tutor->id,
            'client_name' => $tutor->name,
            'consent_template_id' => $template->id,
            'veterinarian_id' => $this->user->id,
            'status' => 'signed',
            'signed_at' => now(),
        ]);

        $resPrint = $this->get(route('consent-forms.print', $cf));
        $resPrint->assertOk();

        $resPdf = $this->get(route('consent-forms.pdf', $cf));
        $resPdf->assertOk();
        $this->assertEquals('application/pdf', $resPdf->headers->get('content-type'));
    }

    public function test_grooming_print_and_pdf()
    {
        $pet = Pet::first() ?? Pet::factory()->create();
        $appt = Appointment::create([
            'pet_id' => $pet->id,
            'vet_id' => $this->user->id,
            'type' => 'grooming',
            'date' => today(),
            'time' => '10:00',
            'status' => 'scheduled',
            'reason' => 'Banho e Tosa Completa',
            'created_by' => $this->user->id,
        ]);

        $resOrderPrint = $this->get(route('grooming.order.print', $appt));
        $resOrderPrint->assertOk();

        $resOrderPdf = $this->get(route('grooming.order.pdf', $appt));
        $resOrderPdf->assertOk();
        $this->assertEquals('application/pdf', $resOrderPdf->headers->get('content-type'));

        $resBoardPrint = $this->get(route('grooming-board.print', ['date' => today()->toDateString()]));
        $resBoardPrint->assertOk();

        $resBoardPdf = $this->get(route('grooming-board.pdf', ['date' => today()->toDateString()]));
        $resBoardPdf->assertOk();
        $this->assertEquals('application/pdf', $resBoardPdf->headers->get('content-type'));
    }

    public function test_pet_shop_subscription_print_and_pdf()
    {
        $pet = Pet::first() ?? Pet::factory()->create();
        $package = PetShopPackage::first() ?? PetShopPackage::create([
            'name' => 'Pacote 4 Banhos Mensal',
            'type' => 'grooming',
            'services' => [],
            'total_price' => 200.00,
            'original_price' => 240.00,
            'validity_days' => 30,
            'max_uses' => 4,
            'branch_id' => $this->branch->id,
        ]);

        $sub = PetShopSubscription::create([
            'pet_id' => $pet->id,
            'package_id' => $package->id,
            'branch_id' => $this->branch->id,
            'start_date' => today(),
            'end_date' => today()->addDays(30),
            'remaining_uses' => 4,
            'total_uses' => 4,
            'status' => 'active',
        ]);

        $resPrint = $this->get(route('pet-shop-subscriptions.print', $sub));
        $resPrint->assertOk();

        $resPdf = $this->get(route('pet-shop-subscriptions.pdf', $sub));
        $resPdf->assertOk();
        $this->assertEquals('application/pdf', $resPdf->headers->get('content-type'));
    }

    public function test_pet_transport_print_and_pdf()
    {
        $pet = Pet::first() ?? Pet::factory()->create();
        $tutor = Tutor::first() ?? Tutor::factory()->create();
        $transport = PetTransport::create([
            'pet_id' => $pet->id,
            'tutor_id' => $tutor->id,
            'branch_id' => $this->branch->id,
            'driver_id' => $this->user->id,
            'pickup_at' => now()->addHour(),
            'origin_address' => 'Rua das Flores, 100',
            'destination_address' => 'Rua Rio de Janeiro, 178 - Amigo Bicho',
            'status' => 'scheduled',
            'price' => 35.00,
        ]);

        $resPrint = $this->get(route('pet-transports.print', $transport));
        $resPrint->assertOk();

        $resPdf = $this->get(route('pet-transports.pdf', $transport));
        $resPdf->assertOk();
        $this->assertEquals('application/pdf', $resPdf->headers->get('content-type'));
    }

    public function test_product_inventory_and_stock_print_and_pdf()
    {
        $resInvPrint = $this->get(route('products.inventory.print'));
        $resInvPrint->assertOk();

        $resInvPdf = $this->get(route('products.inventory.pdf'));
        $resInvPdf->assertOk();
        $this->assertEquals('application/pdf', $resInvPdf->headers->get('content-type'));

        $resMovPrint = $this->get(route('stock.movements.print'));
        $resMovPrint->assertOk();

        $resMovPdf = $this->get(route('stock.movements.pdf'));
        $resMovPdf->assertOk();
        $this->assertEquals('application/pdf', $resMovPdf->headers->get('content-type'));

        $resExpPrint = $this->get(route('stock.expiring.print'));
        $resExpPrint->assertOk();

        $resExpPdf = $this->get(route('stock.expiring.pdf'));
        $resExpPdf->assertOk();
        $this->assertEquals('application/pdf', $resExpPdf->headers->get('content-type'));
    }

    public function test_purchase_order_print_and_pdf()
    {
        $supplier = Supplier::first() ?? Supplier::create([
            'name' => 'Distribuidora Pet Ltda',
            'document' => '12.345.678/0001-90',
        ]);

        $po = PurchaseOrder::create([
            'order_number' => PurchaseOrder::generateNumber(),
            'supplier_id' => $supplier->id,
            'status' => 'draft',
            'requested_by' => $this->user->id,
            'total' => 500.00,
        ]);

        $resPrint = $this->get(route('purchase-orders.print', $po));
        $resPrint->assertOk();

        $resPdf = $this->get(route('purchase-orders.pdf', $po));
        $resPdf->assertOk();
        $this->assertEquals('application/pdf', $resPdf->headers->get('content-type'));
    }

    public function test_invoice_print_and_pdf()
    {
        $pet = Pet::first() ?? Pet::factory()->create();
        $tutor = Tutor::first() ?? Tutor::factory()->create();
        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-' . rand(1000, 9999),
            'tutor_id' => $tutor->id,
            'pet_id' => $pet->id,
            'status' => 'paid',
            'subtotal' => 150.00,
            'total' => 150.00,
            'due_date' => today(),
            'paid_at' => now(),
            'payment_method' => 'pix',
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_type' => 'service',
            'description' => 'Consulta Veterinária Geral',
            'quantity' => 1,
            'unit_price' => 150.00,
            'total' => 150.00,
        ]);

        $resPrint = $this->get(route('invoices.print', $invoice));
        $resPrint->assertOk();

        $resPdf = $this->get(route('invoices.pdf', $invoice));
        $resPdf->assertOk();
        $this->assertEquals('application/pdf', $resPdf->headers->get('content-type'));
    }

    public function test_financial_reports_export_print_and_pdf()
    {
        $resPrint = $this->get(route('reports.export-print'));
        $resPrint->assertOk();

        $resPdf = $this->get(route('reports.export-pdf'));
        $resPdf->assertOk();
        $this->assertEquals('application/pdf', $resPdf->headers->get('content-type'));
    }

    public function test_controlled_substances_monthly_report_print_and_pdf()
    {
        $resPrint = $this->get(route('controlled-substances.reports.monthly.print', ['month' => date('Y-m')]));
        $resPrint->assertOk();

        $resPdf = $this->get(route('controlled-substances.reports.monthly.pdf', ['month' => date('Y-m')]));
        $resPdf->assertOk();
        $this->assertEquals('application/pdf', $resPdf->headers->get('content-type'));
    }
}
