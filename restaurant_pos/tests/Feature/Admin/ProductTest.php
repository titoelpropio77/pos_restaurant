<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ReportGroup;
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
            'cost_price' => 20.5,
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
        $this->assertEquals(20.5, (float) $product->cost_price);
        $this->assertSame(['CON MOTE', 'SIN LLAJUA'], $product->options()->pluck('option_name')->all());
        $this->assertEquals(48, $config->sale_price);
        $this->assertSame(['x', '', 'x'], [$config->sat, $config->mon, $config->print_kitchen]);
        $this->assertSame('11:00', $config->start_time);
        $this->assertCount(1, $product->recipe);
    }

    public function test_the_form_uses_the_names_the_controller_validates(): void
    {
        $this->post(route('admin.products.store'), $this->data());
        $product = Product::where('product_name', 'CHICHARRÓN')->firstOrFail();
        $branch = Branch::first()->id;

        $html = $this->get(route('admin.products.edit', $product))->assertOk()->getContent();

        foreach (['sale_price', 'wholesale_price', 'start_time', 'end_time', 'expires_on', 'enabled'] as $field) {
            $this->assertStringContainsString("name=\"branches[{$branch}][{$field}]\"", $html);
        }

        foreach (['days', 'printers'] as $field) {
            $this->assertStringContainsString("name=\"branches[{$branch}][{$field}][]\"", $html);
        }

        foreach (['recipe[${i}][supply_id]', 'recipe[${i}][quantity]', 'report_groups[${i}][report_group_id]', 'report_groups[${i}][factor]'] as $field) {
            $this->assertStringContainsString($field, $html);
        }
    }

    public function test_the_report_groups_are_validated_and_saved(): void
    {
        $group = ReportGroup::first() ?? ReportGroup::create(['group_name' => 'BEBIDAS', 'order_number' => 1]);

        $this->post(route('admin.products.store'), $this->data(['report_groups' => [['report_group_id' => 999999, 'factor' => 1]]]))
            ->assertSessionHasErrors('report_groups.0.report_group_id');

        $this->post(route('admin.products.store'), $this->data(['report_groups' => [['report_group_id' => $group->id, 'factor' => 2]]]))
            ->assertSessionHasNoErrors();

        $this->assertEquals(2, Product::where('product_name', 'CHICHARRÓN')->firstOrFail()->groups()->value('factor'));
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
        $this->assertStringStartsWith('legacy/images/products/', $product->image_path);
        $this->assertFileExists(public_path($product->image_path));
        @unlink(public_path($product->image_path));
    }

    public function test_deactivate_not_deletes_the_product(): void
    {
        $product = Product::first();

        $this->delete(route('admin.products.destroy', $product));

        $this->assertSame('INACTIVE', $product->fresh()->status);
    }

    public function test_editing_keeps_the_hidden_family(): void
    {
        $product = Product::firstOrFail();
        $product->update(['family' => 'CARNES']);

        $this->put(route('admin.products.update', $product), $this->data(['product_name' => $product->product_name]))
            ->assertSessionHasNoErrors();

        $this->assertSame('CARNES', $product->fresh()->family);
        $this->get(route('admin.products.edit', $product))->assertDontSee('name="family"', false);
    }
}
