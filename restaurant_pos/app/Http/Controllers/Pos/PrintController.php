<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\DiningTable;
use App\Models\Sale;
use App\Services\Printing\OrderTickets;
use App\Support\PosContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tickets para la ticketera USB de la caja. Responden HTML (no el contrato JSON del POS): el POS los carga
 * en un iframe oculto y los imprime con el diálogo del navegador, o directo con Chrome --kiosk-printing.
 */
class PrintController extends Controller
{
    public function __construct(private PosContext $pos, private OrderTickets $tickets) {}

    /** Comanda de los productos que se acaban de enviar a una mesa. */
    public function tableOrder(Request $request): View
    {
        $data = $request->validate(['table' => 'required|integer', 'ticket' => 'required|integer', 'date' => 'required|date']);
        $table = DiningTable::where('branch_id', $this->pos->branchId())->findOrFail($data['table']);

        return $this->view('pos.tickets.order', $this->tickets->tableOrder($table, (int) $data['ticket'], $data['date']));
    }

    /** Comanda de una venta directa. */
    public function saleOrder(Request $request): View
    {
        $data = $request->validate(['sale' => 'required|integer']);
        $sale = Sale::where('branch_id', $this->pos->branchId())->findOrFail($data['sale']);

        return $this->view('pos.tickets.order', $this->tickets->saleOrder($sale));
    }

    /** Nota de venta o factura; copy=1 al reimprimir desde el historial. */
    public function receipt(Request $request): View
    {
        $data = $request->validate(['sale' => 'required|integer', 'copy' => 'nullable|boolean']);
        $sale = Sale::where('branch_id', $this->pos->branchId())->findOrFail($data['sale']);

        return $this->view('pos.tickets.receipt', $this->tickets->receipt($sale) + ['copy' => (bool) ($data['copy'] ?? false)]);
    }

    /** Precuenta de una mesa abierta. */
    public function preBill(Request $request): View
    {
        $data = $request->validate(['table' => 'required|integer', 'date' => 'required|date']);
        $table = DiningTable::where('branch_id', $this->pos->branchId())->findOrFail($data['table']);

        return $this->view('pos.tickets.pre-bill', $this->tickets->preBill($table, $data['date']));
    }

    private function view(string $name, array $data): View
    {
        $width = (int) (Branch::whereKey($this->pos->branchId())->value('paper_width') ?: 80);

        return view($name, $data + ['width' => $width === 58 ? 58 : 80, 'printedAt' => now()]);
    }
}
