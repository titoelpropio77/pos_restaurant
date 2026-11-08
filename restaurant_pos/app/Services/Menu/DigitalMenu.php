<?php

namespace App\Services\Menu;

use App\Http\Controllers\Admin\ProductController;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Menú digital de una sucursal: los productos que vende, agrupados por categoría en el orden del POS,
 * con el precio de la sucursal y el horario en que se venden.
 *
 * Debe llamarse con el restaurante de la sucursal fijado en Tenant: las consultas pasan por el scope.
 */
class DigitalMenu
{
    /** Palabras que van en minúscula al convertir los nombres en MAYÚSCULAS del POS. */
    private const SMALL_WORDS = ['a', 'al', 'con', 'de', 'del', 'el', 'en', 'la', 'las', 'lo', 'los', 'o', 'para', 'por', 'sin', 'y', 'u', 'e'];

    private const DAY_NAMES = ['mon' => 'Lun', 'tue' => 'Mar', 'wed' => 'Mié', 'thu' => 'Jue', 'fri' => 'Vie', 'sat' => 'Sáb', 'sun' => 'Dom'];

    /**
     * @return list<array{id: int, name: string, items: list<array{id: int, name: string, description: string, price: float,
     *                                                                 image: ?string, schedule: ?string, available_now: bool}>}>
     */
    public static function for(Branch $branch, ?CarbonInterface $now = null): array
    {
        $now ??= now();

        $products = Product::forPos($branch->id)
            ->where('products.status', 'ACTIVE')
            ->where('ps.expires_on', '>=', $now->toDateString())
            // Los productos cobrados por tiempo (mesas de billar) no son platos de la carta
            ->where(fn ($q) => $q->whereNull('products.timed')->orWhere('products.timed', '<>', 'T'))
            ->orderBy('products.id')
            ->get()
            ->map(fn (Product $product) => self::item($product, $now))
            // Sin ningún día marcado el POS no lo vende nunca
            ->filter()
            ->groupBy('category_id');

        return Category::query()
            ->whereIn('id', $products->keys())
            ->where('status', 'ACTIVE')
            ->orderBy('order_number')
            ->orderBy('group_name')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => self::title($category->group_name),
                'items' => $products[$category->id]->map(fn ($item) => collect($item)->except('category_id')->all())->values()->all(),
            ])
            ->all();
    }

    /** @return array<string, mixed>|null */
    private static function item(Product $product, CarbonInterface $now): ?array
    {
        $days = collect(array_keys(ProductController::DAYS))->filter(fn ($day) => filled($product->{$day}))->values();

        if ($days->isEmpty()) {
            return null;
        }

        $start = $product->start_time ?: '00:00';
        $end = $product->end_time ?: '00:00';

        return [
            'category_id' => $product->branch_category_id ?: $product->category_group,
            'id' => $product->id,
            'name' => self::title($product->product_name),
            'description' => trim((string) $product->description),
            'price' => (float) $product->sale_price,
            'image' => self::image($product->image_path),
            'schedule' => self::schedule($days, $start, $end),
            'available_now' => self::availableAt($days, $start, $end, $now),
        ];
    }

    /**
     * Texto del horario, o null si se vende todos los días a toda hora. P. ej. «Sáb y Dom · 18:00 a 23:00».
     *
     * @param  Collection<int, string>  $days
     */
    public static function schedule(Collection $days, string $start, string $end): ?string
    {
        $parts = [];

        if ($days->count() < 7) {
            $parts[] = self::daysText($days);
        }
        if ($start !== $end) {
            $parts[] = "{$start} a {$end}";
        }

        return $parts ? implode(' · ', $parts) : null;
    }

    /**
     * Misma regla que productEnabled() del POS: inicio igual a fin es todo el día, y un inicio mayor
     * que el fin cruza la medianoche (la madrugada pertenece al día anterior).
     *
     * @param  Collection<int, string>  $days
     */
    public static function availableAt(Collection $days, string $start, string $end, CarbonInterface $now): bool
    {
        $today = strtolower($now->format('D'));
        $yesterday = strtolower($now->copy()->subDay()->format('D'));
        $time = $now->format('H:i');

        if ($start === $end) {
            return $days->contains($today);
        }
        if ($start < $end) {
            return $days->contains($today) && $time >= $start && $time <= $end;
        }

        return ($days->contains($today) && $time >= $start) || ($days->contains($yesterday) && $time <= $end);
    }

    /** @param  Collection<int, string>  $days  en orden de lunes a domingo */
    private static function daysText(Collection $days): string
    {
        $keys = array_keys(self::DAY_NAMES);
        $runs = [];

        foreach ($days as $day) {
            $index = array_search($day, $keys, true);
            $last = array_key_last($runs);

            if ($last !== null && end($runs[$last]) === $index - 1) {
                $runs[$last][] = $index;
            } else {
                $runs[] = [$index];
            }
        }

        // Tres o más días seguidos se resumen: «Lun a Vie»
        $texts = [];
        foreach ($runs as $run) {
            if (count($run) >= 3) {
                $texts[] = self::DAY_NAMES[$keys[$run[0]]].' a '.self::DAY_NAMES[$keys[end($run)]];
            } else {
                foreach ($run as $index) {
                    $texts[] = self::DAY_NAMES[$keys[$index]];
                }
            }
        }

        $lastText = array_pop($texts);

        return $texts ? implode(', ', $texts).' y '.$lastText : $lastText;
    }

    /** El POS sólo muestra imágenes subidas al sistema (rutas «legacy/...»). */
    private static function image(?string $path): ?string
    {
        return $path && str_starts_with($path, 'legacy/') ? asset($path) : null;
    }

    /**
     * «PIQUE MACHO DE LA CASA» → «Pique Macho de la Casa». Las palabras con números (2LT, 500ML) se dejan como están.
     */
    public static function title(string $text): string
    {
        $words = preg_split('/\s+/u', trim($text)) ?: [];

        return collect($words)->map(function (string $word, int $i) {
            if (preg_match('/\d/u', $word)) {
                return $word;
            }

            $lower = mb_strtolower($word);

            return $i > 0 && in_array($lower, self::SMALL_WORDS, true) ? $lower : mb_convert_case($lower, MB_CASE_TITLE);
        })->implode(' ');
    }
}
