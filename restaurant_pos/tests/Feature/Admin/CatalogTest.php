<?php

namespace Tests\Feature\Admin;

use App\Admin\Catalogs;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DiningTable;
use App\Models\InvoiceSequence;

class CatalogTest extends AdminTestCase
{
    public function test_creates_and_edits_a_category(): void
    {
        $this->post(route('admin.catalogs.store', 'categories'), ['group_name' => 'SOPAS', 'color' => '#ff0000', 'order_number' => 5, 'status' => 'ACTIVE'])
            ->assertRedirect(route('admin.catalogs.index', 'categories'));

        $category = Category::where('group_name', 'SOPAS')->firstOrFail();
        $this->assertSame((string) $category->id, $category->group_code);

        $this->put(route('admin.catalogs.update', ['categories', $category->id]), ['group_name' => 'SOPAS Y CALDOS', 'order_number' => 1, 'status' => 'ACTIVE']);
        $this->assertSame('SOPAS Y CALDOS', $category->fresh()->group_name);
    }

    public function test_validates_the_fields(): void
    {
        $this->post(route('admin.catalogs.store', 'dining_tables'), ['table_number' => '', 'status' => 'ACTIVE'])
            ->assertSessionHasErrors(['table_number', 'branch_id']);
    }

    public function test_give_of_deactivation_a_table_the_hides_of_pos(): void
    {
        $diningTable = DiningTable::first();

        $this->delete(route('admin.catalogs.destroy', ['dining_tables', $diningTable->id]));

        $this->assertSame('DELETED', $diningTable->fresh()->status);
        $this->get(route('admin.catalogs.index', 'dining_tables'))->assertDontSee('DELETED');
    }

    public function test_a_branch_new_receives_its_sequence(): void
    {
        $this->post(route('admin.catalogs.store', 'branches'), ['name' => 'SUCURSAL NORTE', 'status' => 'ACTIVE']);

        $branch = Branch::where('name', 'SUCURSAL NORTE')->firstOrFail();
        $this->assertNotNull(InvoiceSequence::forSales($branch->id));
    }

    public function test_creates_a_customer(): void
    {
        $this->post(route('admin.catalogs.store', 'customers'), ['customer_name' => 'ANA ROJAS', 'customer_tax_id' => '123456'])
            ->assertRedirect(route('admin.catalogs.index', 'customers'))
            ->assertSessionHasNoErrors();

        $this->assertSame('123456', Customer::where('customer_name', 'ANA ROJAS')->firstOrFail()->customer_tax_id);
    }

    public function test_texts_agree_with_the_gender_of_the_catalog(): void
    {
        $this->get(route('admin.catalogs.index', 'customers'))->assertSee('Nuevo cliente');
        $this->get(route('admin.catalogs.create', 'customers'))->assertSee('Nuevo cliente');
        $this->get(route('admin.catalogs.index', 'dining_tables'))->assertSee('Nueva mesa');
        $this->get(route('admin.catalogs.create', 'dining_tables'))->assertSee('Nueva mesa');

        $this->post(route('admin.catalogs.store', 'branches'), ['name' => 'SUCURSAL SUR', 'status' => 'ACTIVE'])
            ->assertSessionHas('ok', 'Sucursal registrada correctamente.');
        $this->post(route('admin.catalogs.store', 'customers'), ['customer_name' => 'ANA ROJAS'])
            ->assertSessionHas('ok', 'Cliente registrado correctamente.');
    }

    public function test_all_catalogs_render_their_screens(): void
    {
        foreach (array_keys(Catalogs::all()) as $slug) {
            $this->get(route('admin.catalogs.index', $slug))->assertOk();
            $this->get(route('admin.catalogs.create', $slug))->assertOk();

            $def = Catalogs::get($slug);
            $record = $def['model']::query()->first();
            if ($record) {
                $this->get(route('admin.catalogs.edit', [$slug, $record->id]))->assertOk();
            }
        }
    }

    public function test_catalog_missing_gives_404(): void
    {
        $this->get('/admin/catalogos/no-existe')->assertNotFound();
    }
}
