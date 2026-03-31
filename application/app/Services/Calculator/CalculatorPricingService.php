<?php

namespace App\Services\Calculator;

use App\Models\Calculator\BlindComponentVariant;
use App\Models\Calculator\BlindSystem;
use Illuminate\Support\Arr;

class CalculatorPricingService
{
    private const DEFAULT_BAR_WEIGHT = 0.2;
    private const BNT_WEIGHT_THRESHOLD = 8.0;
    private const BNT_WIDTH_THRESHOLD = 2000;

    public function calculateTotals(array $state): array
    {
        $systemCode = (string) Arr::get($state, 'type_rolled_curtains', '');
        if ($systemCode === '') {
            return $this->emptyResult();
        }

        $system = BlindSystem::query()
            ->where('code', $systemCode)
            ->with('components')
            ->first();

        if (!$system) {
            return $this->emptyResult($systemCode, true);
        }

        $componentQty = Arr::get($state, 'components_qty', []);
        $priceTierRaw = (string) Arr::get($state, 'rol_price_tier', '');
        $priceTier = in_array($priceTierRaw, ['opt', 'opt1', 'opt2', 'opt3', 'opt4', 'vip'], true)
            ? $priceTierRaw
            : 'opt';
        $calculationDate = (string) Arr::get($state, 'calculation_date', now()->toDateString());
        $usdRate = app(ExchangeRateService::class)->usdToRubForDate($calculationDate);
        $usdRate = $usdRate > 0 ? $usdRate : 1.0;

        $cost = 0.0;
        $retail = 0.0;
        $breakdown = [];

        foreach ($system->components as $component) {
            $selectedVariantId = Arr::get($state, "components_variant.{$component->id}");
            $qtyPath = "components_qty.{$component->id}";
            $qty = $this->normalizeQty(Arr::get($state, $qtyPath, null));

            if ($qty <= 0) {
                continue;
            }

            $variant = $this->resolveVariant(
                $selectedVariantId
            );

            $unitCostUsd = $variant
                ? ($this->variantPrice($variant, $priceTier) ?? 0.0)
                : ($this->componentPrice($component, $priceTier) ?? (float) $component->cost_price);
            $unitRetailUsd = $variant
                ? ($this->variantPrice($variant, $priceTier) ?? 0.0)
                : ($this->componentPrice($component, $priceTier) ?? (float) $component->retail_price);
            $unitCost = $unitCostUsd * $usdRate;
            $unitRetail = $unitRetailUsd * $usdRate;

            $cost += $qty * $unitCost;
            $retail += $qty * $unitRetail;

            $this->addLine($breakdown, [
                'label' => $variant
                    ? "Компонент: {$component->name} ({$variant->name})"
                    : "Компонент: {$component->name}",
                'qty' => $qty,
                'unit_cost' => $unitCost,
                'unit_retail' => $unitRetail,
                'unit_cost_usd' => $unitCostUsd,
                'unit_retail_usd' => $unitRetailUsd,
            ]);
        }

        return [
            'cost_total' => round($cost, 2),
            'retail_total' => round($retail, 2),
            'breakdown' => $breakdown,
            'debug' => [
                'system_code' => $systemCode,
                'calculation_date' => $calculationDate,
                'usd_rate' => $usdRate,
            ],
        ];
    }

    public function recommendBntPipe(array $state): string
    {
        $width = (float) Arr::get($state, 'bnt_width', 0);
        $height = (float) Arr::get($state, 'bnt_height', 0);
        $fabricWeight = (float) Arr::get($state, 'bnt_fabric_weight', 0);
        $area = max(0, $width) * max(0, $height) / 1000000;
        $totalWeight = $area * $fabricWeight + self::DEFAULT_BAR_WEIGHT;

        if ($totalWeight > self::BNT_WEIGHT_THRESHOLD || $width > self::BNT_WIDTH_THRESHOLD) {
            return '44';
        }

        return '29';
    }

    private function componentPrice($component, string $tier): ?float
    {
        $map = [
            'opt' => 'price_opt',
            'opt1' => 'price_opt1',
            'opt2' => 'price_opt2',
            'opt3' => 'price_opt3',
            'opt4' => 'price_opt4',
            'vip' => 'price_vip',
        ];

        $field = $map[$tier] ?? null;
        if (!$field) {
            return null;
        }

        $value = data_get($component, $field);
        return $value !== null ? (float) $value : null;
    }

    private function variantPrice(BlindComponentVariant $variant, string $tier): ?float
    {
        $map = [
            'opt' => 'price_opt',
            'opt1' => 'price_opt1',
            'opt2' => 'price_opt2',
            'opt3' => 'price_opt3',
            'opt4' => 'price_opt4',
            'vip' => 'price_vip',
        ];

        $field = $map[$tier] ?? null;
        if (!$field) {
            return null;
        }

        $value = data_get($variant, $field);
        return $value !== null ? (float) $value : null;
    }

    private function resolveVariant(mixed $selectedVariantId): ?BlindComponentVariant
    {
        if ($selectedVariantId) {
            return BlindComponentVariant::query()
                ->where('id', (int) $selectedVariantId)
                ->first();
        }

        return null;
    }

    private function addLine(array &$breakdown, array $line): void
    {
        $qty = (float) ($line['qty'] ?? 0);
        $unitCost = (float) ($line['unit_cost'] ?? 0);
        $unitRetail = (float) ($line['unit_retail'] ?? 0);

        $breakdown[] = [
            'label' => (string) ($line['label'] ?? ''),
            'qty' => $qty,
            'unit_cost' => round($unitCost, 2),
            'unit_retail' => round($unitRetail, 2),
            'unit_cost_usd' => round((float) ($line['unit_cost_usd'] ?? 0), 4),
            'unit_retail_usd' => round((float) ($line['unit_retail_usd'] ?? 0), 4),
            'total_cost' => round($qty * $unitCost, 2),
            'total_retail' => round($qty * $unitRetail, 2),
        ];
    }

    private function normalizeQty(mixed $rawQty): float
    {
        if (is_bool($rawQty)) {
            return $rawQty ? 1.0 : 0.0;
        }

        if (is_numeric($rawQty)) {
            return (float) $rawQty;
        }

        if (is_string($rawQty)) {
            $value = mb_strtolower(trim($rawQty));

            if (in_array($value, ['1', 'true', 'on', 'yes'], true)) {
                return 1.0;
            }

            if (in_array($value, ['0', 'false', 'off', 'no', ''], true)) {
                return 0.0;
            }
        }

        return 0.0;
    }

    private function emptyResult(?string $systemCode = null, bool $missing = false): array
    {
        return [
            'cost_total' => 0,
            'retail_total' => 0,
            'breakdown' => [],
            'debug' => [
                'system_code' => $systemCode,
                'system_missing' => $missing,
            ],
        ];
    }
}
