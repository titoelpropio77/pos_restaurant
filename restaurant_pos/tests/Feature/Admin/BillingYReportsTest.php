<?php

namespace Tests\Feature\Admin;

use App\Admin\Reports\Filters;
use App\Admin\Reports\Registry;
use App\Models\BillingAuthorization;
use App\Models\Branch;
use App\Models\InvoiceSequence;
use App\Models\Product;
use App\Models\Sale;
use App\Services\Sales\RegisterSale;
use App\Support\PosContext;

class BillingYReportsTest extends AdminTestCase
{
    private function sell(string $tax_id = ''): Sale
    {
        $pos = app(PosContext::class);
        $pos->start($this->admin->pointOfSale()->with('branch')->first(), 6.96);
        $product = Product::where('product_name', 'SILPANCHO')->first();

        return app(RegisterSale::class)->run([
            'idProduct' => [$product->id], 'quantity' => [2], 'subTotal' => [70], 'nameProduct' => ['SILPANCHO'],
            'nameCustomer' => $tax_id ? 'JUAN' : 'SIN NOMBRE', 'customerTaxId' => $tax_id, 'totalBs' => 70,
            'paymentMethod' => 'CONTADO', 'shift' => 'NOCHE', 'saleDate' => today()->toDateString(),
        ], $this->admin)['sale'];
    }

    public function test_updates_the_authorization_and_keeps_the_history(): void
    {
        $branch = Branch::first();
        $data = [
            'business_name' => 'EL PORTAL SRL', 'tax_id' => '1020304', 'authorization_number' => '79040011859',
            'auth_key' => 'A3Fs4s$)2cvD(eY667A5C4A2rsdf53kw9654E2B23s24df35F5', 'limit_date' => today()->addMonths(6)->toDateString(),
            'morning_start' => '07:00', 'morning_end' => '15:00', 'folio' => 500, 'real_folio' => 500, 'new' => 1,
        ];

        $this->put(route('admin.billing.update', $branch), $data)->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame(2, BillingAuthorization::where('branch_id', $branch->id)->count());
        $this->assertSame('79040011859', BillingAuthorization::currentFor($branch->id)->authorization_number);
        $this->assertSame(500, InvoiceSequence::forSales($branch->id)->folio);

        // La siguiente factura continúa la numeración configurada
        $this->assertSame(501, $this->sell('4189179011')->invoice_number);
    }

    public function test_rejects_a_key_with_characters_invalid(): void
    {
        $this->put(route('admin.billing.update', Branch::first()), [
            'business_name' => 'X', 'tax_id' => '1', 'authorization_number' => '1', 'auth_key' => 'llave con espacios', 'limit_date' => '2030-01-01',
            'morning_start' => '07:00', 'morning_end' => '15:00', 'folio' => 0, 'real_folio' => 0,
        ])->assertSessionHasErrors('auth_key');
    }

    public function test_all_the_reports_generate_and_export(): void
    {
        $this->sell();
        $this->sell('4189179011');

        foreach (array_keys(Registry::REPORTS) as $slug) {
            $this->get(route('admin.reports.show', $slug))->assertOk();
            $this->get(route('admin.reports.show', [$slug, 'export' => 'csv']))->assertOk();
        }
    }

    public function test_the_book_of_sales_includes_only_invoices(): void
    {
        $this->sell();
        $invoice_ref = $this->sell('4189179011');

        $rows = collect(Registry::get('book-sales')->rows(Filters::from(request())));

        $this->assertCount(1, $rows);
        $this->assertSame($invoice_ref->invoice_number, $rows->first()['invoice_ref']);
        $this->assertEquals(9.1, $rows->first()['debit']);
    }

    public function test_the_cash_count_of_panel_matches_with_the_closing_of_pos(): void
    {
        $this->sell();
        $this->sell('4189179011');

        $row = collect(Registry::get('cash_register')->rows(Filters::from(request())))->first();

        $this->assertEquals(140, $row['invoiced']);
        $this->assertEquals(140, $row['balance']);
    }

    public function test_void_a_sale_from_the_panel(): void
    {
        $sale = $this->sell();

        $this->post(route('admin.sales.void', $sale));

        $this->assertSame('INACTIVE', $sale->fresh()->status);
        $this->assertSame('admin', $sale->fresh()->voided_by);
    }
}
