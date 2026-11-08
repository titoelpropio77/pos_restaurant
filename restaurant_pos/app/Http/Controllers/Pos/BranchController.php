<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\BillingAuthorization;
use App\Models\Branch;
use App\Models\Staff;
use App\Services\CashRegister\CashSessions;
use App\Support\PosContext;
use App\Support\PosResponse;
use App\Support\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Datos de la sucursal de trabajo: cambio de sucursal, impresora, dosificación y meseros.
 * Port de sucursalController, printersettingController, docificacionController y meseroController.
 */
class BranchController extends Controller
{
    public function __construct(private PosContext $pos) {}

    public function index(): JsonResponse
    {
        return PosResponse::ok(PosResponse::rows(Branch::where('status', 'ACTIVE')->orderBy('name')->get()));
    }

    public function change(Request $request, CashSessions $cashSessions): JsonResponse
    {
        if (! $this->pos->multiBranchSales()) {
            return PosResponse::error('EL USUARIO NO PUEDE VENDER EN OTRAS SUCURSALES');
        }

        $branch = Branch::findOrFail($request->integer('idSuc'));

        // Con la caja abierta no se cambia de sucursal: sus movimientos deben quedar en una sola
        $session = $cashSessions->enabled() ? $cashSessions->current($request->user()) : null;

        if ($session && $session->branch_id !== $branch->id) {
            return PosResponse::error('CIERRE LA CAJA ANTES DE CAMBIAR DE SUCURSAL');
        }

        $this->pos->changeBranch($branch);

        return PosResponse::ok($this->pos->dailyOrder());
    }

    /**
     * IP del agente local de impresión de la sucursal.
     */
    public function print(): JsonResponse
    {
        $branch = Branch::findOrFail($this->pos->branchId());

        return PosResponse::ok([[
            'nameBranch' => $branch->name,
            'ipPrint' => $branch->print_ip ?? '',
        ]]);
    }

    public function authorization(): JsonResponse
    {
        $authorization = BillingAuthorization::currentFor($this->pos->branchId());

        return PosResponse::ok($authorization ? [PosResponse::row($authorization)] : '');
    }

    /**
     * Aviso de vencimiento de la dosificación (7 días antes) o de dosificación vencida.
     */
    public function verifyAuthorization(Request $request): JsonResponse
    {
        if (! Tenant::option('billing')) {
            return PosResponse::ok('');
        }

        $date = Carbon::parse($request->input('date', today()));
        $authorization = BillingAuthorization::currentFor($this->pos->branchId());
        // Desactivada no se factura: su vencimiento no importa
        $days = $authorization?->isActive() ? $authorization->daysUntilExpiry($date) : null;

        if ($days === null) {
            return PosResponse::ok('');
        }

        $limit = $authorization->limit_date->format('d/m/Y');
        $warning = match (true) {
            $days < 0 => 'SU DOSIFICACIÓN YA SE VENCIÓ, NO PODRÁ VENDER',
            $days > 0 && $days <= config('pos.days_warning_authorization') => "LE QUEDAN {$days} DÍAS PARA QUE SU DOSIFICACIÓN SE VENZA, SE RECOMIENDA DOSIFICAR ANTES DE LA FECHA: {$limit}",
            default => '',
        };

        return PosResponse::ok($warning);
    }

    public function waiters(): JsonResponse
    {
        $waiters = Staff::where(fn ($q) => $q->where('branch_id', $this->pos->branchId())->orWhereNull('branch_id'))
            ->orderBy('staff_name')
            ->get()
            ->makeHidden('code');

        return PosResponse::ok(PosResponse::rows($waiters, ['branch_id' => 'idBranch']));
    }
}
