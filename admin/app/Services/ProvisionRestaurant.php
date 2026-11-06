<?php

namespace App\Services;

use App\Models\BillingAuthorization;
use App\Models\Branch;
use App\Models\InvoiceSequence;
use App\Models\PointOfSale;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

/**
 * Deja un restaurante nuevo listo para ingresar: casa matriz, almacén, numeración,
 * catálogos base y sus partners.
 *
 * La dosificación nace vencida a propósito: el POS no permite facturar con ella hasta que el
 * dueño cargue en Facturación SIN la autorización real de Impuestos Nacionales.
 */
class ProvisionRestaurant
{
    private const SALES_SEQUENCE = 'sales';

    private const REASONS = ['VENTA', 'COMPRA', 'TRASPASO', 'AJUSTE', 'MERMA'];

    private const UNITS = [['UNIDAD', 'UND'], ['KILOGRAMO', 'KG'], ['GRAMO', 'GR'], ['LITRO', 'LT'], ['MILILITRO', 'ML']];

    /**
     * @param  array  $restaurant  datos de restaurants
     * @param  array  $main  datos de branches de la casa matriz
     * @param  list<int>  $partnerIds  partners existentes
     * @param  array|null  $owner  name, username, email, password de un partner nuevo
     */
    public function create(array $restaurant, array $main, array $partnerIds, ?array $owner): Restaurant
    {
        return DB::transaction(function () use ($restaurant, $main, $partnerIds, $owner) {
            $created = Restaurant::create($restaurant + ['options' => Restaurant::optionsInitial()]);

            $this->catalogsBase($created);
            $this->addBranch($created, $main, true);

            if ($owner) {
                $partner = new User([
                    'name' => mb_strtoupper($owner['name']),
                    'username' => $owner['username'],
                    'email' => $owner['email'],
                    'password' => $owner['password'],
                    'position' => 'PARTNER',
                    'status' => 'ACTIVE',
                ]);
                $partner->save();
                $partner->assignRole(Role::PARTNER);
                $partnerIds[] = $partner->id;
            }

            $this->syncPartners($created, $partnerIds);

            return $created;
        });
    }

    /**
     * Deja exactamente esos partners en el restaurante. Los que llegan reciben su punto de venta
     * en la casa matriz; los cajeros del restaurante no se tocan.
     */
    public function syncPartners(Restaurant $restaurant, array $partnerIds): void
    {
        DB::transaction(function () use ($restaurant, $partnerIds) {
            $current = $restaurant->partners()->pluck('users.id')->all();

            $restaurant->users()->detach(array_diff($current, $partnerIds));
            $restaurant->users()->attach(array_diff($partnerIds, $current));

            $main = $restaurant->main()->first();

            foreach (User::whereIn('id', array_diff($partnerIds, $current))->get() as $partner) {
                if (! $partner->pointOfSaleIn($restaurant->id)) {
                    $this->assignPointOfSale($partner, $restaurant->id, [
                        'branch_id' => $main->id,
                        'default_warehouse_id' => $main->warehouses()->value('id'),
                        'multi_branch_sales' => true,
                        'can_sell' => true,
                    ]);
                }
            }
        });
    }

    public function addBranch(Restaurant $restaurant, array $data, bool $main = false): Branch
    {
        return DB::transaction(function () use ($restaurant, $data, $main) {
            $branch = Branch::create($data + [
                'restaurant_id' => $restaurant->id,
                'is_main' => $main,
                'status' => 'ACTIVE',
                'print_ip' => 'localhost',
            ]);

            Warehouse::create(['name' => 'ALMACÉN PRINCIPAL', 'branch_id' => $branch->id, 'restaurant_id' => $restaurant->id]);

            InvoiceSequence::create([
                'table_name' => self::SALES_SEQUENCE, 'branch_id' => $branch->id, 'restaurant_id' => $restaurant->id,
                'folio' => 0, 'real_folio' => 0, 'daily_order' => 0,
            ]);

            BillingAuthorization::create([
                'branch_id' => $branch->id,
                'restaurant_id' => $restaurant->id,
                'business_name' => $restaurant->name,
                'tax_id' => $restaurant->tax_id,
                'branch' => $branch->name,
                'business_address' => $branch->address ?: 'S/D',
                'business_phone' => $branch->phone ?: '0',
                'first_invoice_number' => 1,
                'current_invoice' => 0,
                'current_real_invoice' => 0,
                'morning_start' => '06:00',
                'morning_end' => '16:00',
                'limit_date' => today()->subDay()->toDateString(),
            ]);

            return $branch;
        });
    }

    public function assignPointOfSale(User $user, int $restaurantId, array $data): void
    {
        PointOfSale::updateOrCreate(['user_id' => $user->id, 'restaurant_id' => $restaurantId], $data + [
            'start_date' => today(),
        ]);
    }

    private function catalogsBase(Restaurant $restaurant): void
    {
        $base = ['restaurant_id' => $restaurant->id, 'created_at' => now(), 'updated_at' => now()];

        DB::table('movement_reasons')->insert(array_map(fn ($n) => $base + ['name' => $n], self::REASONS));
        DB::table('units')->insert(array_map(fn ($u) => $base + ['name' => $u[0], 'abbreviation' => $u[1]], self::UNITS));
    }
}
