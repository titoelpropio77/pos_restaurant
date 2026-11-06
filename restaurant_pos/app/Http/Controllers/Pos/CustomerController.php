<?php

namespace App\Http\Controllers\Pos;

use App\Exceptions\PosException;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Support\PosResponse;
use App\Support\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Búsqueda y mantenimiento rápido de clientes desde el POS. Port de clienteController y auxController.
 */
class CustomerController extends Controller
{
    /**
     * Sugerencias para el autocompletado por nombre (formato del plugin jquery.autocomplete).
     */
    public function suggestionsName(Request $request): JsonResponse
    {
        $suggestions = $this->searchPor('customer_name', (string) $request->query('term'), 50)
            ->map(fn (Customer $c) => [
                'id' => $c->id,
                'label' => $c->customer_name,
                'value' => $c->customer_name.'-'.$c->customer_tax_id,
                'number' => $c->customer_tax_id ?? '',
                'customer_mobile' => $c->customer_mobile ?? '',
            ]);

        return response()->json(['suggestions' => $suggestions]);
    }

    public function suggestionsTaxId(Request $request): JsonResponse
    {
        $suggestions = $this->searchPor('customer_tax_id', (string) $request->query('term'), 50)
            ->map(fn (Customer $c) => [
                'id' => $c->id,
                'label' => $c->customer_tax_id,
                'value' => $c->customer_tax_id.'-'.$c->customer_name,
                'customer' => $c->customer_name,
                'customer_mobile' => $c->customer_mobile ?? '',
            ]);

        return response()->json(['suggestions' => $suggestions]);
    }

    /**
     * Sugerencias para el envío a domicilio, por teléfono, nombre o NIT.
     */
    public function suggestionsDelivery(Request $request): JsonResponse
    {
        $column = match ($request->query('field')) {
            'name' => 'customer_name',
            'tax_id' => 'customer_tax_id',
            default => 'customer_phone',
        };

        $suggestions = $this->searchPor($column, (string) $request->query('term'), 50)
            ->map(fn (Customer $c) => [
                'id' => $c->id,
                'label' => match ($column) {
                    'customer_phone' => $c->customer_phone,
                    'customer_tax_id' => $c->customer_name.' - '.$c->customer_tax_id,
                    default => $c->customer_name,
                },
                'value' => $column === 'customer_tax_id' ? $c->customer_tax_id : $c->customer_name.'-'.$c->customer_phone,
                'customerName' => $c->customer_name,
                'customerTaxId' => $c->customer_tax_id ?? '',
                'customer_phone' => $c->customer_phone ?? '',
                'DIRECCION' => $c->customer_address ?? '',
            ]);

        return response()->json(['suggestions' => $suggestions]);
    }

    public function searchName(Request $request): JsonResponse
    {
        return PosResponse::ok(PosResponse::rows($this->searchPor('customer_name', (string) $request->input('name'))));
    }

    public function searchTaxId(Request $request): JsonResponse
    {
        return PosResponse::ok(PosResponse::rows($this->searchPor('customer_tax_id', (string) $request->input('tax_id'))));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $exists = Customer::whereRaw('lower(customer_name) = ?', [mb_strtolower($data['customer_name'])])
            ->where('customer_tax_id', $data['customer_tax_id'])
            ->exists();

        if ($exists) {
            throw new PosException('YA EXISTE UN CLIENTE CON ESE NOMBRE Y NIT');
        }

        return PosResponse::ok(Customer::create($data + ['customer_code' => '0'])->id);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate(['idCustomer' => ['required', 'integer', Tenant::exists('customers')]]);

        Customer::whereKey($request->integer('idCustomer'))->update($this->validated($request));

        return PosResponse::ok();
    }

    public function destroy(Request $request): JsonResponse
    {
        $customer = Customer::findOrFail($request->integer('idCustomer'));

        if ($customer->sales()->exists()) {
            return PosResponse::error('EL CLIENTE TIENE VENTAS REGISTRADAS, NO SE PUEDE ELIMINAR');
        }

        $customer->delete();

        return PosResponse::ok();
    }

    /**
     * Datos del formulario de cliente. Sin NIT se guarda '0', igual que al facturar: así la venta
     * reconoce al cliente y no crea otro.
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'customerName' => ['required', 'string', 'max:100'],
            'customerTaxId' => ['nullable', 'string', 'max:20'],
            'customerIdNumber' => ['nullable', 'string', 'max:15'],
            'customerAddressA' => ['nullable', 'string', 'max:500'],
            'customerPhone' => ['nullable', 'string', 'max:20'],
            'customerMobile' => ['nullable', 'string', 'max:20'],
            'customerEmail' => ['nullable', 'email', 'max:100'],
            'birthDate' => ['nullable', 'date', 'before_or_equal:today'],
        ], [], [
            'customerName' => 'nombre',
            'customerTaxId' => 'NIT',
            'customerIdNumber' => 'CI',
            'customerAddressA' => 'dirección',
            'customerPhone' => 'teléfono',
            'customerMobile' => 'celular',
            'customerEmail' => 'correo',
            'birthDate' => 'fecha de nacimiento',
        ]);

        return [
            'customer_name' => $data['customerName'],
            'customer_tax_id' => $data['customerTaxId'] ?? '0',
            'customer_id_number' => $data['customerIdNumber'] ?? '',
            'customer_address' => $data['customerAddressA'] ?? '',
            'customer_phone' => $data['customerPhone'] ?? '',
            'customer_mobile' => $data['customerMobile'] ?? '',
            'customer_email' => $data['customerEmail'] ?? '',
            'birth_date' => $data['birthDate'] ?? null,
        ];
    }

    private function searchPor(string $column, string $text, int $limit = 1000)
    {
        return Customer::where($column, 'ilike', '%'.trim($text).'%')
            ->orderBy('customer_name')
            ->limit($limit)
            ->get();
    }
}
