<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\TableOrder;
use App\Services\Tables\SplitBill;
use App\Services\Tables\TableOrders;
use App\Support\PosContext;
use App\Support\PosResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Mesas del salón y sus pedidos. Port de mesasController y productoMesaController.
 */
class TableController extends Controller
{
    private const ALIAS_ORDER = ['dining_table_id' => 'table_number', 'branch_id' => 'idBranch'];

    public function __construct(private PosContext $pos, private TableOrders $orders) {}

    public function index(): JsonResponse
    {
        $diningTables = DiningTable::where('branch_id', $this->pos->branchId())
            ->where('status', '<>', 'DELETED')
            ->orderBy('order_number')
            ->orderBy('id')
            ->get();

        return PosResponse::ok(PosResponse::rows($diningTables, ['branch_id' => 'idBranch']));
    }

    /**
     * Bloquea la mesa mientras se cobra en caja.
     */
    public function processing(Request $request): JsonResponse
    {
        $diningTable = $this->diningTable($request->integer('idTable'));
        $diningTable->update(['processing' => 'S']);
        $this->pos->setTableProcessing($diningTable->id);

        return PosResponse::ok(true);
    }

    public function releaseProcessing(Request $request): JsonResponse
    {
        $this->diningTable($request->integer('idTable'))->update(['processing' => '']);
        $this->pos->setTableProcessing(null);

        return PosResponse::ok();
    }

    public function open(Request $request): JsonResponse
    {
        $data = $request->validate([
            'idTable' => ['required', 'integer'],
            'idWaiter' => ['required', 'integer'],
            'waiterCode' => ['nullable', 'string', 'max:30'],
            'idProduct' => ['nullable', 'array'],
            'idProduct.*' => ['integer'],
            'quantity' => ['required_with:idProduct', 'array'],
            'quantity.*' => ['numeric', 'gt:0'],
            'saleDate' => ['required', 'date'],
            'shift' => ['nullable', 'string'],
            'note' => ['nullable', 'array'],
            'subtotal' => ['nullable', 'array'],
            'qtyCustomer' => ['nullable', 'string', 'max:60'],
            'dining_room' => ['nullable', 'string'],
            'waiters_no_code' => ['nullable', 'string'],
            'billiards' => ['nullable', 'string'],
        ]);
        $data['saleDate'] = Carbon::parse($data['saleDate'])->toDateString();

        return PosResponse::ok($this->orders->open($this->diningTable($data['idTable']), $data, $request->user()));
    }

    public function add(Request $request): JsonResponse
    {
        $data = $request->validate([
            'idTable' => ['required', 'integer'],
            'idProduct' => ['required', 'array', 'min:1'],
            'idProduct.*' => ['integer'],
            'quantity' => ['required', 'array'],
            'quantity.*' => ['numeric', 'gt:0'],
            'saleDate' => ['required', 'date'],
            'numberTable' => ['nullable'],
            'noteProduct' => ['nullable', 'array'],
            'typeProduct' => ['nullable', 'string'],
        ]);
        $data['saleDate'] = Carbon::parse($data['saleDate'])->toDateString();

        return PosResponse::ok($this->orders->add($this->diningTable($data['idTable']), $data, $request->user()));
    }

    public function detail(Request $request): JsonResponse
    {
        $date = Carbon::parse($request->input('saleDate', today()))->toDateString();
        $items = $this->orders->detail($this->diningTable($request->integer('idTable')), $date);

        return PosResponse::ok($items->isEmpty() ? '' : PosResponse::rows($items, self::ALIAS_ORDER));
    }

    /**
     * Después de cobrar: opción 1 descuenta los ítems pagados, opción 2 cierra la mesa.
     */
    public function updateAfterCheckout(Request $request): JsonResponse
    {
        $this->orders->updateAfterCheckout(
            $this->diningTable($request->integer('idTable')),
            $request->integer('optionItemTable'),
            (array) $request->input('idDetail', []),
            (array) $request->input('quantity', []),
        );

        return PosResponse::ok();
    }

    public function transfer(Request $request): JsonResponse
    {
        $data = $request->validate([
            'idTableDecrease' => ['required', 'integer'],
            'idTableTransfer' => ['required', 'integer', 'different:idMesaDisminuir'],
            'idItem' => ['required', 'array'],
            'quantity' => ['required', 'array'],
            'date' => ['required', 'date'],
            'numberTableTransfer' => ['nullable', 'string'],
            'enableTable' => ['nullable'],
            'statusTable' => ['nullable', 'string'],
            'dining_room' => ['nullable', 'string'],
        ], ['idTableTransfer.different' => 'NO PUEDE TRASPASAR A LA MISMA MESA']);
        $data['date'] = Carbon::parse($data['date'])->toDateString();

        $this->orders->transfer(
            $this->diningTable($data['idTableDecrease']),
            $this->diningTable($data['idTableTransfer']),
            $data,
            $request->user(),
        );

        return PosResponse::ok();
    }

    public function void(Request $request): JsonResponse
    {
        $this->orders->void(
            $this->diningTable($request->integer('idTable')),
            Carbon::parse($request->input('saleDate', today()))->toDateString(),
        );

        return PosResponse::ok();
    }

    public function deleteItem(Request $request): JsonResponse
    {
        $diningTable = $this->diningTable($request->integer('idTable'));
        $item = TableOrder::where('dining_table_id', $diningTable->id)->findOrFail($request->integer('idDetail'));

        $this->orders->deleteItem($diningTable, $item, $request->input('totalDetail') === 'open_table');

        return PosResponse::ok();
    }

    public function deleteQuantities(Request $request): JsonResponse
    {
        $this->orders->deleteQuantities(
            $this->diningTable($request->integer('idTable')),
            (array) $request->input('idDetail', []),
            (array) $request->input('quantity', []),
        );

        return PosResponse::ok();
    }

    /**
     * Detiene el cronómetro de un producto cobrado por tiempo (billar).
     */
    public function stopTime(Request $request): JsonResponse
    {
        TableOrder::whereKey($request->integer('idDetail'))
            ->where('branch_id', $this->pos->branchId())
            ->update(['time_end' => (string) $request->input('timeA', now()->format('G:i'))]);

        return PosResponse::ok();
    }

    /**
     * Ítems eliminados de mesas en la fecha, agrupados por producto y mesa (impresión del cierre).
     */
    public function itemsDeleted(Request $request): JsonResponse
    {
        $date = Carbon::parse($request->input('date', today()))->toDateString();

        $items = TableOrder::query()
            ->join('dining_tables', 'dining_tables.id', '=', 'table_orders.dining_table_id')
            ->where('table_orders.branch_id', $this->pos->branchId())
            ->where('table_orders.sale_date', $date)
            ->whereRaw("coalesce(table_orders.item_deleted, '') <> ''")
            ->groupBy('table_orders.product_name', 'dining_tables.table_number')
            ->selectRaw('table_orders.product_name, dining_tables.table_number, sum(table_orders.quantity) as quantity, max(table_orders.sale_price) as sale_price, max(table_orders.staff_name) as staff_name, max(table_orders.login) as login')
            ->get();

        return PosResponse::ok($items->isEmpty() ? '' : PosResponse::rows($items));
    }

    /**
     * Cuenta compartida: ítems y total de la próxima parte a cobrar.
     */
    public function splitPreview(Request $request, SplitBill $split): JsonResponse
    {
        $data = $this->splitData($request);

        return PosResponse::ok($split->preview($this->diningTable($data['idTable']), $data));
    }

    /**
     * Cuenta compartida: cobra una parte como venta propia (nombre, NIT y forma de pago).
     */
    public function splitCharge(Request $request, SplitBill $split): JsonResponse
    {
        $data = $this->splitData($request, charge: true);

        return PosResponse::ok($split->charge($this->diningTable($data['idTable']), $data, $request->user()));
    }

    private function splitData(Request $request, bool $charge = false): array
    {
        $rules = [
            'idTable' => ['required', 'integer'],
            'saleDate' => ['required', 'date'],
            'mode' => ['required', 'in:equal,items'],
            'parts' => ['required_if:mode,equal', 'nullable', 'integer', 'min:1', 'max:50'],
            'items' => ['required_if:mode,items', 'nullable', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
        ];

        if ($charge) {
            $rules += [
                'nameCustomer' => ['nullable', 'string', 'max:100'],
                'customerTaxId' => ['nullable', 'regex:/^[0-9]*$/', 'max:20'],
                'paymentMethod' => ['required', 'in:CONTADO,QR,CREDITO,CONSUMO'],
                'received' => ['nullable', 'numeric', 'min:0'],
            ];
        }

        $data = $request->validate($rules, [
            'customerTaxId.regex' => 'EL NIT SÓLO DEBE CONTENER NÚMEROS',
            'paymentMethod.in' => 'FORMA DE PAGO NO VÁLIDA',
        ]);
        $data['saleDate'] = Carbon::parse($data['saleDate'])->toDateString();
        $data['nameCustomer'] = trim((string) ($data['nameCustomer'] ?? ''));
        $data['customerTaxId'] = trim((string) ($data['customerTaxId'] ?? ''));

        return $data;
    }

    private function diningTable(int $id): DiningTable
    {
        return DiningTable::where('branch_id', $this->pos->branchId())->findOrFail($id);
    }
}
