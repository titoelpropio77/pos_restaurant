<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProductImportTest extends AdminTestCase
{
    private function excel(array $rows): UploadedFile
    {
        $book = new Spreadsheet;
        $book->getActiveSheet()->fromArray($rows, null, 'A1', true);
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        (new Xlsx($book))->save($path);

        return new UploadedFile($path, 'productos.xlsx', null, null, true);
    }

    public function test_imports_products_with_their_branches(): void
    {
        $main = Branch::where('name', 'CASA MATRIZ')->firstOrFail();
        $north = Branch::create(['name' => 'NORTE', 'status' => 'ACTIVE']);

        $this->get(route('admin.products.index'))->assertSee('Carga masiva');
        $this->post(route('admin.products.import'), ['file' => $this->excel([
            ['Nombre', 'Categoría', 'Precio', 'Precio de costo', 'Código de barras', 'Familia', 'Cantidad', 'Color', 'Estado', 'Sucursales', 'Imprime en'],
            ['Chairo', 'platos', 25.5, 12.25, '7771234567890', 'SOPAS', null, '#ff0000', 'Inactivo', 'norte', 'Cocina, Punto 1'],
            ['Mocochinchi', 'BEBIDAS', 6, null, null, null, null, null, null, null, null],
        ])])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('ok', 'Carga masiva completada: 2 productos creados.');

        $chairo = Product::where('product_name', 'CHAIRO')->firstOrFail();
        $this->assertSame(Category::where('group_name', 'PLATOS')->value('id'), $chairo->category_id);
        $this->assertEquals(25.5, (float) $chairo->sale_price);
        $this->assertEquals(12.25, (float) $chairo->cost_price);
        $this->assertSame('7771234567890', $chairo->barcode);
        $this->assertSame('INACTIVE', $chairo->status);
        $this->assertSame((string) $chairo->id, (string) $chairo->product_code);
        $this->assertSame([$north->id], $chairo->branches()->pluck('branch_id')->all());
        $config = $chairo->branches()->first();
        $this->assertSame('x', $config->print_kitchen);
        $this->assertSame('x', $config->print_station_1);
        $this->assertSame('', $config->print_station_2);
        $this->assertSame('x', $config->sun);
        $this->assertEquals(25.5, (float) $config->sale_price);

        $mocochinchi = Product::where('product_name', 'MOCOCHINCHI')->firstOrFail();
        $this->assertSame('ACTIVE', $mocochinchi->status);
        $this->assertEquals(0, (float) $mocochinchi->cost_price);
        $this->assertEquals(1, (float) $mocochinchi->quantity);
        $this->assertSame('#337ab7', $mocochinchi->color);
        $this->assertEqualsCanonicalizing([$main->id, $north->id], $mocochinchi->branches()->pluck('branch_id')->all());
    }

    public function test_saves_nothing_when_a_row_fails(): void
    {
        $existing = Product::firstOrFail();
        $existing->update(['barcode' => '123']);
        $before = Product::count();

        $this->post(route('admin.products.import'), ['file' => $this->excel([
            ['nombre', 'categoria', 'precio', 'codigo_de_barras', 'sucursales', 'imprime_en', 'precio_de_costo'],
            ['BUENO', 'PLATOS', 10, null, null, null],
            [mb_strtolower($existing->product_name), 'PLATOS', 10, null, null, null],
            ['SIN CATEGORIA', 'NO EXISTE', 10, null, null, null],
            ['SIN PRECIO', 'PLATOS', null, null, null, null],
            ['COSTO MALO', 'PLATOS', 10, null, null, null, 'abc'],
            ['CODIGO REPETIDO', 'PLATOS', 10, '123', null, null],
            ['bueno', 'PLATOS', 10, null, null, null],
            ['OTRA SUCURSAL', 'PLATOS', 10, null, 'LUNA', 'Horno'],
        ])])->assertSessionHasErrors('file');

        $errors = implode("\n", session('errors')->get('file'));
        $this->assertStringNotContainsString('Fila 2:', $errors);
        $this->assertStringContainsString('Fila 3: el producto', $errors);
        $this->assertStringContainsString('Fila 4: la categoría «NO EXISTE»', $errors);
        $this->assertStringContainsString('Fila 5:', $errors);
        $this->assertStringContainsString('Fila 6: El campo precio de costo', $errors);
        $this->assertStringContainsString('Fila 7: el código de barras 123', $errors);
        $this->assertStringContainsString('Fila 8: el producto «BUENO» está repetido en la fila 2', $errors);
        $this->assertStringContainsString('Fila 9: la sucursal «LUNA»', $errors);
        $this->assertStringContainsString('Fila 9: la impresora «Horno»', $errors);
        $this->assertSame($before, Product::count());
    }

    public function test_accepts_csv_with_decimal_comma(): void
    {
        $csv = "\xEF\xBB\xBFnombre;categoria;precio\nTÉ;BEBIDAS;\"4,50\"\n";

        $this->post(route('admin.products.import'), ['file' => UploadedFile::fake()->createWithContent('p.csv', $csv)])
            ->assertSessionHasNoErrors();

        $this->assertEquals(4.5, (float) Product::where('product_name', 'TÉ')->firstOrFail()->sale_price);
    }

    public function test_the_template_lists_the_active_categories(): void
    {
        $response = $this->get(route('admin.products.import.template'))->assertOk()->assertDownload('plantilla-productos.xlsx');

        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $response->streamedContent());
        $book = IOFactory::createReader('Xlsx')->load($path);

        $this->assertSame('nombre', $book->getSheet(0)->getCell('A1')->getValue());
        $this->assertSame('categoria', $book->getSheet(0)->getCell('B1')->getValue());
        $this->assertContains('PLATOS', array_column($book->getSheetByName('Listas')->toArray(), 0));
        $this->assertStringStartsWith('Listas!', $book->getSheet(0)->getCell('B2')->getDataValidation()->getFormula1());
    }
}
