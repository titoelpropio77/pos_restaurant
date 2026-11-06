<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supply;
use Illuminate\Http\UploadedFile;

class ProductTest extends AdminTestCase
{
    private function data(array $extra = []): array
    {
        $branch = Branch::first();

        return array_merge([
            'product_name' => 'Chicharrón',
            'category_id' => Category::first()->id,
            'sale_price' => 45,
            'status' => 'ACTIVE',
            'asks_options' => 1,
            'options' => "con mote\nsin llajua\n",
            'branches' => [$branch->id => [
                'enabled' => 1, 'sale_price' => 48, 'start_time' => '11:00', 'end_time' => '15:00',
                'days' => ['sat', 'sun'], 'printers' => ['print_kitchen'],
            ]],
            'recipe' => [['supply_id' => Supply::first()->id, 'quantity' => 0.5]],
        ], $extra);
    }

    public function test_creates_a_product_with_its_configuration_complete(): void
    {
        $this->post(route('admin.products.store'), $this->data())->assertSessionHasNoErrors()->assertRedirect();

        $product = Product::where('product_name', 'CHICHARRÓN')->firstOrFail();
        $config = $product->branches()->first();

        $this->assertSame('OPCIONES', $product->options_mode);
        $this->assertSame(['CON MOTE', 'SIN LLAJUA'], $product->options()->pluck('option_name')->all());
        $this->assertEquals(48, $config->sale_price);
        $this->assertSame(['x', '', 'x'], [$config->sat, $config->mon, $config->print_kitchen]);
        $this->assertSame('11:00', $config->start_time);
        $this->assertCount(1, $product->recipe);
    }

    public function test_the_pos_charges_the_price_of_the_branch(): void
    {
        $this->post(route('admin.products.store'), $this->data());
        $product = Product::where('product_name', 'CHICHARRÓN')->firstOrFail();

        $this->assertEquals(48, Product::forPos(Branch::first()->id)->where('products.id', $product->id)->value('sale_price'));
    }

    public function test_disable_a_branch_removes_the_product_of_that_branch(): void
    {
        $this->post(route('admin.products.store'), $this->data());
        $product = Product::where('product_name', 'CHICHARRÓN')->firstOrFail();

        $this->put(route('admin.products.update', $product), $this->data([
            'branches' => [Branch::first()->id => ['enabled' => 0]],
            'recipe' => [],
        ]))->assertSessionHasNoErrors();

        $this->assertSame(0, $product->branches()->count());
        $this->assertSame(0, $product->recipe()->count());
    }

    public function test_not_allows_names_duplicates(): void
    {
        $this->post(route('admin.products.store'), $this->data(['product_name' => 'SILPANCHO']))->assertSessionHasErrors('product_name');
    }

    public function test_saves_the_image_in_the_folder_that_reads_the_pos(): void
    {
        $this->post(route('admin.products.store'), $this->data(['image' => UploadedFile::fake()->image('dish.png')]));

        $product = Product::where('product_name', 'CHICHARRÓN')->firstOrFail();
        $this->assertStringStartsWith('legacy/images/buttons/', $product->image_path);
        $this->assertFileExists(public_path($product->image_path));
        @unlink(public_path($product->image_path));
    }

    public function test_deactivate_not_deletes_the_product(): void
    {
        $product = Product::first();

        $this->delete(route('admin.products.destroy', $product));

        $this->assertSame('INACTIVE', $product->fresh()->status);
    }
}
