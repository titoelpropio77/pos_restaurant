<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Datos enviados por el POS al cobrar una venta (legacy: facturarController "guardar").
 */
class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idProduct' => ['required', 'array', 'min:1'],
            'idProduct.*' => ['required', 'integer'],
            'quantity' => ['required', 'array'],
            'quantity.*' => ['required', 'numeric', 'gt:0'],
            'price' => ['nullable', 'array'],
            'subTotal' => ['nullable', 'array'],
            'note' => ['nullable', 'array'],
            'qr' => ['nullable', 'array'],
            'nameProduct' => ['nullable', 'array'],
            'nameCustomer' => ['nullable', 'string', 'max:100'],
            'customerTaxId' => ['nullable', 'regex:/^[0-9]*$/', 'max:20'],
            'phoneCustomer' => ['nullable', 'string', 'max:20'],
            'addressCustomer' => ['nullable', 'string'],
            'totalBs' => ['required', 'numeric', 'gt:0'],
            'totalUsd' => ['nullable', 'numeric'],
            'totalInvoiceBs' => ['nullable', 'numeric'],
            'totalInvoiceUs' => ['nullable', 'numeric'],
            'changeBs' => ['nullable', 'numeric'],
            'changeUsd' => ['nullable', 'numeric'],
            'paymentMethod' => ['required', 'string', 'max:10'],
            'seller_id' => ['nullable'],
            'shift' => ['nullable', 'string', 'max:6'],
            'exitProduct' => ['nullable', 'string', 'max:20'],
            'saleDate' => ['required', 'date'],
            'selectPrepaid' => ['nullable', 'string'],
            'billiards' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'idProduct.required' => 'DEBE SELECCIONAR AL MENOS UN ITEM',
            'customerTaxId.regex' => 'EL NIT SÓLO DEBE CONTENER NÚMEROS',
            'totalBs.gt' => 'EL TOTAL DE LA VENTA DEBE SER MAYOR A CERO',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'customerTaxId' => trim((string) $this->input('customerTaxId')),
            'nameCustomer' => trim((string) $this->input('nameCustomer')) ?: 'SIN NOMBRE',
            'addressCustomer' => trim((string) $this->input('addressCustomer')),
        ]);
    }
}
