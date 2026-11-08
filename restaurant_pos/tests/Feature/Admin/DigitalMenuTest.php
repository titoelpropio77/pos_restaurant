<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use App\Services\Menu\DigitalMenu;
use App\Services\Menu\MenuTheme;
use App\Support\Tenant;
use Carbon\CarbonImmutable;

class DigitalMenuTest extends AdminTestCase
{
    private function product(array $extra = []): void
    {
        $branch = Branch::first();

        $this->post(route('admin.products.store'), array_replace_recursive([
            'product_name' => 'Chicharrón de cerdo',
            'description' => 'Con mote y llajua de la casa',
            'category_id' => Category::first()->id,
            'sale_price' => 45,
            'status' => 'ACTIVE',
            'branches' => [$branch->id => [
                'enabled' => 1, 'sale_price' => 48, 'start_time' => '11:00', 'end_time' => '15:00', 'days' => ['sat', 'sun'],
            ]],
        ], $extra))->assertSessionHasNoErrors();
    }

    private function menuUrl(?Branch $branch = null): string
    {
        return route('menu.show', ($branch ?? Branch::first())->menuToken());
    }

    public function test_shows_the_menu_of_the_branch_with_its_price_description_and_schedule(): void
    {
        $this->product();

        $this->assertSame('Con mote y llajua de la casa', Product::where('product_name', 'CHICHARRÓN DE CERDO')->value('description'));

        $this->get($this->menuUrl())
            ->assertOk()
            ->assertHeaderMissing('Set-Cookie')
            ->assertSeeInOrder(['Chicharrón de Cerdo', 'Con mote y llajua de la casa', '48', 'Sáb y Dom · 11:00 a 15:00'])
            ->assertSee('Silpancho');
    }

    public function test_hides_what_the_branch_does_not_sell(): void
    {
        $branch = Branch::first()->id;
        $this->product(['product_name' => 'Plato inactivo', 'status' => 'INACTIVE']);
        $this->product(['product_name' => 'Plato sin dias', 'branches' => [$branch => ['days' => null]]]);
        $this->product(['product_name' => 'Mesa de billar', 'por_time' => 1]);
        $this->post(route('admin.products.store'), [
            'product_name' => 'Plato de otra sucursal', 'category_id' => Category::first()->id, 'sale_price' => 10, 'status' => 'ACTIVE',
        ])->assertSessionHasNoErrors();

        $this->get($this->menuUrl())
            ->assertOk()
            ->assertDontSee('Plato Inactivo')
            ->assertDontSee('Plato sin Dias')
            ->assertDontSee('Mesa de Billar')
            ->assertDontSee('Plato de Otra Sucursal');
    }

    public function test_each_restaurant_sees_only_its_own_menu(): void
    {
        $other = Restaurant::create(['name' => 'OTRO RESTAURANTE']);
        Tenant::use($other->id);
        $branchOther = Branch::create(['name' => 'MATRIZ OTRO', 'status' => 'ACTIVE']);
        $category = Category::create(['group_name' => 'CATEGORIA OTRO']);
        $product = Product::create(['product_name' => 'PLATO SECRETO OTRO', 'category_id' => $category->id, 'sale_price' => 10]);
        BranchProduct::create(['product_id' => $product->id, 'branch_id' => $branchOther->id, 'category_id' => $category->id, 'sat' => 'x', 'start_time' => '00:00', 'end_time' => '00:00']);
        Tenant::use(null);

        $this->get($this->menuUrl())->assertOk()->assertDontSee('Plato Secreto');
        $this->get($this->menuUrl($branchOther))->assertOk()->assertSee('Plato Secreto Otro')->assertDontSee('Silpancho');
    }

    public function test_an_unknown_link_or_an_inactive_branch_or_restaurant_gives_not_found(): void
    {
        $branch = Branch::first();
        $url = $this->menuUrl($branch);

        $this->get(route('menu.show', 'noexiste1234'))->assertNotFound();

        $branch->update(['status' => 'INACTIVE']);
        $this->get($url)->assertNotFound();

        $branch->update(['status' => 'ACTIVE']);
        Restaurant::whereKey($branch->restaurant_id)->update(['status' => 'INACTIVE']);
        $this->get($url)->assertNotFound();
    }

    public function test_the_panel_shows_the_link_the_qr_and_the_poster(): void
    {
        $branch = Branch::first();
        $url = $this->menuUrl($branch);

        $this->get(route('admin.menu.index'))->assertOk()->assertSee($url, false)->assertSee('<svg', false);

        $qr = $this->get(route('admin.menu.qr', $branch))->assertOk()->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString('attachment', $qr->headers->get('Content-Disposition'));
        $this->assertStringContainsString('<svg', $qr->getContent());

        $this->get(route('admin.menu.poster', $branch))->assertOk()->assertSee('Nuestro menú')->assertSee($url, false);
    }

    public function test_schedule_texts(): void
    {
        $this->assertNull(DigitalMenu::schedule(collect(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']), '00:00', '00:00'));
        $this->assertSame('Lun a Vie', DigitalMenu::schedule(collect(['mon', 'tue', 'wed', 'thu', 'fri']), '00:00', '00:00'));
        $this->assertSame('Sáb y Dom', DigitalMenu::schedule(collect(['sat', 'sun']), '00:00', '00:00'));
        $this->assertSame('Lun, Mié y Vie · 18:00 a 23:00', DigitalMenu::schedule(collect(['mon', 'wed', 'fri']), '18:00', '23:00'));
        $this->assertSame('18:00 a 02:00', DigitalMenu::schedule(collect(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']), '18:00', '02:00'));
    }

    public function test_availability_follows_the_pos_rule_even_after_midnight(): void
    {
        $friday = collect(['fri']);
        $saturday = CarbonImmutable::parse('2026-11-07'); // sábado

        // Viernes de 22:00 a 02:00: la madrugada del sábado todavía es del viernes
        $this->assertTrue(DigitalMenu::availableAt($friday, '22:00', '02:00', $saturday->setTime(1, 0)));
        $this->assertFalse(DigitalMenu::availableAt($friday, '22:00', '02:00', $saturday->setTime(23, 0)));
        $this->assertTrue(DigitalMenu::availableAt($friday, '22:00', '02:00', $saturday->subDay()->setTime(23, 0)));
        $this->assertFalse(DigitalMenu::availableAt($friday, '22:00', '02:00', $saturday->subDay()->setTime(21, 0)));

        $this->assertTrue(DigitalMenu::availableAt(collect(['sat']), '00:00', '00:00', $saturday->setTime(15, 0)));
        $this->assertFalse(DigitalMenu::availableAt(collect(['sat']), '11:00', '15:00', $saturday->setTime(16, 0)));
    }

    public function test_names_in_capitals_are_shown_in_title_case(): void
    {
        $this->assertSame('Pique Macho de la Casa', DigitalMenu::title('PIQUE MACHO DE LA CASA'));
        $this->assertSame('Agua Vital 2LT', DigitalMenu::title('AGUA  VITAL 2LT'));
        $this->assertSame('Ñandú y Ají', DigitalMenu::title('ÑANDÚ Y AJÍ'));
    }

    public function test_the_text_color_contrasts_with_the_store_background(): void
    {
        $this->assertTrue(MenuTheme::from('#14213D')->dark);
        $this->assertTrue(MenuTheme::from('#8C2F39')->dark);
        $this->assertFalse(MenuTheme::from('#FAF7F2')->dark);
        // Sobre ámbar el texto va oscuro, nunca blanco
        $this->assertFalse(MenuTheme::from('#E8961C')->dark);
        $this->assertSame(MenuTheme::DEFAULT_BACKGROUND, MenuTheme::from(null)->background);
        $this->assertSame(MenuTheme::DEFAULT_BACKGROUND, MenuTheme::from('rojo')->background);
    }
}
