<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Datos enviados por el POS al cobrar una venta (legacy: facturarController "guardar").
 */
class FacturarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idProducto' => ['required', 'array', 'min:1'],
            'idProducto.*' => ['required', 'integer'],
            'cantidad' => ['required', 'array'],
            'cantidad.*' => ['required', 'numeric', 'gt:0'],
            'precio' => ['nullable', 'array'],
            'subTotal' => ['nullable', 'array'],
            'nota' => ['nullable', 'array'],
            'qr' => ['nullable', 'array'],
            'nombreProduc' => ['nullable', 'array'],
            'nombreCli' => ['nullable', 'string', 'max:100'],
            'nitCli' => ['nullable', 'regex:/^[0-9]*$/', 'max:20'],
            'telefonoCli' => ['nullable', 'string', 'max:20'],
            'direccionCli' => ['nullable', 'string'],
            'totalBs' => ['required', 'numeric', 'gt:0'],
            'totalUsd' => ['nullable', 'numeric'],
            'totalFacturaBs' => ['nullable', 'numeric'],
            'totalFacturaUs' => ['nullable', 'numeric'],
            'cambioBs' => ['nullable', 'numeric'],
            'cambioUsd' => ['nullable', 'numeric'],
            'formaPago' => ['required', 'string', 'max:10'],
            'vendedor' => ['nullable'],
            'turno' => ['nullable', 'string', 'max:6'],
            'salidaProducto' => ['nullable', 'string', 'max:20'],
            'fechaVenta' => ['required', 'date'],
            'selectPrepago' => ['nullable', 'string'],
            'billar' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'idProducto.required' => 'DEBE SELECCIONAR AL MENOS UN ITEM',
            'nitCli.regex' => 'EL NIT SÓLO DEBE CONTENER NÚMEROS',
            'totalBs.gt' => 'EL TOTAL DE LA VENTA DEBE SER MAYOR A CERO',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nitCli' => trim((string) $this->input('nitCli')),
            'nombreCli' => trim((string) $this->input('nombreCli')) ?: 'SIN NOMBRE',
            'direccionCli' => trim((string) $this->input('direccionCli')),
        ]);
    }
}
