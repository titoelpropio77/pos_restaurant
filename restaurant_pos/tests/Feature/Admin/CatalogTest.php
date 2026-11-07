<?php

namespace Tests\Feature\Admin;

use App\Admin\Catalogs;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DiningTable;
use App\Models\InvoiceSequence;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

    public function test_bulk_imports_categories_from_csv(): void
    {
        $csv = "\xEF\xBB\xBFnombre;color;orden;estado\nSOPAS;#ff0000;7;Activo\n\nJUGOS;;;inactivo\n";

        $this->get(route('admin.catalogs.index', 'categories'))->assertSee('Carga masiva');
        $this->post(route('admin.catalogs.import', 'categories'), ['file' => UploadedFile::fake()->createWithContent('categorias.csv', $csv)])
            ->assertRedirect(route('admin.catalogs.index', 'categories'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('ok', 'Carga masiva completada: 2 registros creados.');

        $sopas = Category::where('group_name', 'SOPAS')->firstOrFail();
        $this->assertSame('#ff0000', $sopas->color);
        $this->assertSame(7, (int) $sopas->order_number);
        $this->assertSame((string) $sopas->id, $sopas->group_code);
        $jugos = Category::where('group_name', 'JUGOS')->firstOrFail();
        $this->assertSame('INACTIVE', $jugos->status);
        $this->assertSame(8, (int) $jugos->order_number);
    }

    public function test_bulk_import_saves_nothing_when_a_row_fails(): void
    {
        $existing = Category::firstOrFail()->group_name;
        $before = Category::count();
        $csv = "nombre,color,orden,estado\nSOPAS,,1,Activo\n,,2,Activo\n{$existing},,,\nPASTAS,,abc,\nSOPAS,,,\n";

        $response = $this->post(route('admin.catalogs.import', 'categories'), ['file' => UploadedFile::fake()->createWithContent('c.csv', $csv)])
            ->assertSessionHasErrors('file');

        $errors = implode("\n", session('errors')->get('file'));
        $this->assertStringContainsString('Fila 3:', $errors);
        $this->assertStringContainsString('Fila 4:', $errors);
        $this->assertStringContainsString('Fila 5:', $errors);
        $this->assertStringContainsString('Fila 6:', $errors);
        $this->assertStringNotContainsString('Fila 2:', $errors);
        $this->assertSame($before, Category::count());
    }

    public function test_bulk_imports_categories_from_excel(): void
    {
        $book = new Spreadsheet;
        $book->getActiveSheet()->fromArray([
            ['Nombre', 'Color', 'Orden', 'Estado'],
            ['ENSALADAS', '#00ff00', 9, 'Inactivo'],
            ['CAFÉS', null, null, null],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        (new Xlsx($book))->save($path);

        $this->post(route('admin.catalogs.import', 'categories'), ['file' => new UploadedFile($path, 'categorias.xlsx', null, null, true)])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('ok', 'Carga masiva completada: 2 registros creados.');

        $ensaladas = Category::where('group_name', 'ENSALADAS')->firstOrFail();
        $this->assertSame(9, (int) $ensaladas->order_number);
        $this->assertSame('INACTIVE', $ensaladas->status);
        $cafes = Category::where('group_name', 'CAFÉS')->firstOrFail();
        $this->assertSame(10, (int) $cafes->order_number);
        $this->assertSame('ACTIVE', $cafes->status);
    }

    public function test_the_template_is_an_excel_with_the_import_columns(): void
    {
        $response = $this->get(route('admin.catalogs.import.template', 'categories'))
            ->assertOk()
            ->assertDownload('plantilla-categories.xlsx');

        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $response->streamedContent());
        $sheet = IOFactory::createReader('Xlsx')->load($path)->getSheet(0);

        $this->assertSame(['nombre', 'color', 'orden', 'estado'], $sheet->rangeToArray('A1:D1')[0]);
        $this->assertSame(DataValidation::TYPE_LIST, $sheet->getCell('D2')->getDataValidation()->getType());

        $this->get(route('admin.catalogs.import.template', 'customers'))->assertNotFound();
    }

    public function test_bulk_import_rejects_missing_columns_and_unreadable_files(): void
    {
        $this->post(route('admin.catalogs.import', 'categories'), ['file' => UploadedFile::fake()->createWithContent('c.csv', "nombre;color\nSOPAS;\n")])
            ->assertSessionHasErrors('file');
        $this->post(route('admin.catalogs.import', 'categories'), ['file' => UploadedFile::fake()->createWithContent('c.xlsx', 'no es un excel')])
            ->assertSessionHasErrors('file');
        $this->post(route('admin.catalogs.import', 'categories'), ['file' => UploadedFile::fake()->createWithContent('c.pdf', 'x')])
            ->assertSessionHasErrors('file');
    }

    public function test_the_order_is_optional(): void
    {
        $last = (int) Category::max('order_number');

        $this->post(route('admin.catalogs.store', 'categories'), ['group_name' => 'SOPAS', 'order_number' => '', 'status' => 'ACTIVE'])
            ->assertSessionHasNoErrors();
        $category = Category::where('group_name', 'SOPAS')->firstOrFail();
        $this->assertSame($last + 1, (int) $category->order_number);

        $this->put(route('admin.catalogs.update', ['categories', $category->id]), ['group_name' => 'SOPAS', 'order_number' => '', 'status' => 'ACTIVE'])
            ->assertSessionHasNoErrors();
        $this->assertSame($last + 1, (int) $category->fresh()->order_number);

        $branch = Branch::firstOrFail();
        $lastTable = (int) DiningTable::where('branch_id', $branch->id)->max('order_number');
        $this->post(route('admin.catalogs.store', 'dining_tables'), ['table_number' => 'T99', 'branch_id' => $branch->id, 'status' => 'ACTIVE'])
            ->assertSessionHasNoErrors();
        $this->assertSame($lastTable + 1, (int) DiningTable::where('table_number', 'T99')->firstOrFail()->order_number);

        $this->post(route('admin.catalogs.store', 'report_groups'), ['group_name' => 'PRESAS'])->assertSessionHasNoErrors();
    }

    public function test_catalog_missing_gives_404(): void
    {
        $this->get('/admin/catalogos/no-existe')->assertNotFound();
    }
}
