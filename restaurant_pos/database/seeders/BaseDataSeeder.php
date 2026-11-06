<?php

namespace Database\Seeders;

use App\Models\BillingAuthorization;
use App\Models\Branch;
use App\Models\City;
use App\Models\Country;
use App\Models\InvoiceSequence;
use App\Models\MovementReason;
use App\Models\PointOfSale;
use App\Models\Restaurant;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserLogin;
use App\Models\Warehouse;
use App\Support\Tenant;
use Illuminate\Database\Seeder;

/**
 * Datos mínimos para operar el POS: restaurante, casa matriz con dosificación y correlativo,
 * almacén, catálogos básicos y un usuario dueño. Los demás restaurantes se crean desde la
 * app administrador.
 */
class BaseDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $bolivia = Country::firstOrCreate(['name' => 'BOLIVIA']);
        $city = City::firstOrCreate(['name' => 'LA PAZ', 'country_id' => $bolivia->id]);

        $restaurant = Restaurant::orderBy('id')->first()
            ?? Restaurant::create(['name' => env('POS_RESTAURANT', 'MESATEK'), 'options' => config('pos.options')]);

        Tenant::use($restaurant->id);

        $branch = Branch::firstOrCreate(
            ['name' => 'CASA MATRIZ'],
            ['city_id' => $city->id, 'address' => 'S/D', 'print_ip' => 'localhost'],
        );
        $branch->forceFill(['is_main' => true])->save();

        BillingAuthorization::firstOrCreate(['branch_id' => $branch->id], [
            'business_name' => $restaurant->name,
            'owner_name' => 'PROPIETARIO',
            'city_country' => 'LA PAZ - BOLIVIA',
            'tax_id' => '1234567',
            'branch' => 'CASA MATRIZ',
            'business_address' => 'S/D',
            'business_phone' => '0',
            'first_invoice_number' => 1,
            'current_invoice' => 0,
            'current_real_invoice' => 0,
            'morning_start' => '06:00',
            'morning_end' => '16:00',
            // Dosificación de prueba: reemplazar por la otorgada por Impuestos Nacionales
            'authorization_number' => '29040011007',
            'auth_key' => '9rCB7Sv4X29d)5k7N%3ab89p-3(5[A',
            'limit_date' => now()->addYear()->toDateString(),
            'legend_1' => 'ESTA FACTURA CONTRIBUYE AL DESARROLLO DEL PAÍS. EL USO ILÍCITO DE ÉSTA SERÁ SANCIONADO DE ACUERDO A LEY',
        ]);

        InvoiceSequence::firstOrCreate(
            ['table_name' => config('pos.sales_sequence'), 'branch_id' => $branch->id],
            ['folio' => 0, 'real_folio' => 0, 'daily_order' => 0],
        );

        $warehouse = Warehouse::firstOrCreate(['name' => 'ALMACÉN PRINCIPAL', 'branch_id' => $branch->id]);

        foreach (['VENTA', 'COMPRA', 'TRASPASO', 'AJUSTE', 'MERMA'] as $reason) {
            MovementReason::firstOrCreate(['name' => $reason]);
        }

        foreach ([['UNIDAD', 'UND'], ['KILOGRAMO', 'KG'], ['GRAMO', 'GR'], ['LITRO', 'LT'], ['MILILITRO', 'ML']] as [$name, $abbreviation]) {
            Unit::firstOrCreate(['name' => $name], ['abbreviation' => $abbreviation]);
        }

        $admin = User::firstOrCreate(['email' => env('POS_ADMIN_EMAIL', 'admin@mesatek.local')], [
            'username' => 'admin',
            'name' => 'ADMINISTRADOR',
            'position' => 'ADMINISTRADOR',
            'password' => env('POS_ADMIN_PASSWORD', 'admin'),
        ]);
        $admin->syncRoles('partner');

        PointOfSale::firstOrCreate(['user_id' => $admin->id], [
            'branch_id' => $branch->id,
            'default_warehouse_id' => $warehouse->id,
            'multi_branch_sales' => true,
            'start_date' => today(),
        ]);

        if (! UserLogin::exists()) {
            UserLogin::create([
                'login_count' => 0,
                'user_id' => $admin->id,
                'username' => $admin->username,
                'login_date' => today(),
                'time' => now()->format('G:i'),
                'exchange_rate' => 6.96,
            ]);
        }
    }
}
