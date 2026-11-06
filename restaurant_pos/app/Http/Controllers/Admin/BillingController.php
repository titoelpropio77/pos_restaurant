<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingAuthorization;
use App\Models\Branch;
use App\Models\InvoiceSequence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Datos fiscales, dosificación SIN y numeración de facturas por sucursal.
 * Legacy: frmDocificacion.php / docificacionController.
 */
class BillingController extends Controller
{
    public function index(): View
    {
        return view('admin.billing.index', [
            'branches' => Branch::orderBy('name')->get()->map(fn (Branch $s) => [
                'branch' => $s,
                'authorization' => $d = BillingAuthorization::currentFor($s->id),
                'days' => $d?->daysUntilExpiry(today()),
                'sequence' => InvoiceSequence::forSales($s->id),
            ]),
        ]);
    }

    public function edit(Branch $branch): View
    {
        return view('admin.billing.form', [
            'branch' => $branch,
            'authorization' => BillingAuthorization::currentFor($branch->id) ?? new BillingAuthorization(['morning_start' => '06:00', 'morning_end' => '16:00', 'branch' => $branch->name]),
            'sequence' => InvoiceSequence::forSales($branch->id) ?? new InvoiceSequence,
            'history' => BillingAuthorization::where('branch_id', $branch->id)->latest('id')->get(),
        ]);
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:100'],
            'tax_id' => ['required', 'regex:/^[0-9]+$/', 'max:20'],
            'owner_name' => ['nullable', 'string', 'max:60'],
            'branch' => ['nullable', 'string', 'max:60'],
            'business_address' => ['nullable', 'string', 'max:150'],
            'business_phone' => ['nullable', 'string', 'max:20'],
            'city_country' => ['nullable', 'string', 'max:60'],
            'authorization_number' => ['required', 'regex:/^[0-9]+$/', 'max:20'],
            'auth_key' => ['required', 'string', 'max:500', 'regex:/^[A-Za-z0-9=#()*+\-_@\[\]{}%$]+$/'],
            'authorization_date' => ['nullable', 'date'],
            'limit_date' => ['required', 'date'],
            'first_invoice_number' => ['nullable', 'integer', 'min:0'],
            'morning_start' => ['required', 'date_format:H:i'],
            'morning_end' => ['required', 'date_format:H:i', 'after:morning_start'],
            'legend_1' => ['nullable', 'string', 'max:300'],
            'legend_2' => ['nullable', 'string', 'max:300'],
            'new' => ['boolean'],
            'folio' => ['required', 'integer', 'min:0'],
            'real_folio' => ['required', 'integer', 'min:0'],
            'receipt_multiplier' => ['nullable', 'integer', 'min:0'],
        ], [
            'auth_key.regex' => 'La llave de dosificación contiene caracteres no permitidos.',
            'morning_end.after' => 'La hora de fin del turno mañana debe ser posterior a la de inicio.',
        ], [
            'business_name' => 'razón social', 'tax_id' => 'NIT', 'authorization_number' => 'número de autorización',
            'auth_key' => 'llave de dosificación', 'limit_date' => 'fecha límite de emisión',
            'morning_start' => 'inicio del turno mañana', 'morning_end' => 'fin del turno mañana',
        ]);

        DB::transaction(function () use ($request, $branch, $data) {
            $fields = collect($data)->except(['new', 'folio', 'real_folio', 'receipt_multiplier'])->all();
            $current = BillingAuthorization::currentFor($branch->id);

            // Una nueva dosificación se registra aparte para conservar el historial
            if (! $current || $request->boolean('new')) {
                BillingAuthorization::create($fields + ['branch_id' => $branch->id, 'current_invoice' => $data['folio'], 'current_real_invoice' => $data['real_folio']]);
            } else {
                $current->update($fields);
            }

            InvoiceSequence::updateOrCreate(
                ['table_name' => config('pos.sales_sequence'), 'branch_id' => $branch->id],
                ['folio' => $data['folio'], 'real_folio' => $data['real_folio'], 'receipt_multiplier' => ($data['receipt_multiplier'] ?? null) ?: null],
            );
        });

        return redirect()->route('admin.billing.index')->with('ok', "Facturación de {$branch->name} actualizada.");
    }
}
