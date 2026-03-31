<?php

namespace App\Livewire;

use App\Models\Calculator\BlindComponent;
use App\Models\Calculator\BlindSystem;
use App\Services\Calculator\CalculatorPricingService;
use Illuminate\Support\Collection;
use Livewire\Component;

class Calculator extends Component
{
    public array $data = [
        'type_rolled_curtains' => '',
        'rol_price_tier' => 'opt',
        'calculation_date' => '',
        'components_qty' => [],
        'components_variant' => [],
    ];

    public array $systems = [];
    public array $components = [];
    private ?Collection $variantComponentsCache = null;

    public float $myPrice = 0.0;
    public float $retailPrice = 0.0;
    public float $usdRate = 0.0;
    public string $priceBreakdown = '';

    public function mount(): void
    {
        $this->data['calculation_date'] = now()->toDateString();

        $this->systems = BlindSystem::query()
            ->where('category', 'roller')
            ->orderBy('name')
            ->get(['code', 'name'])
            ->map(fn (BlindSystem $system) => [
                'code' => (string) $system->code,
                'name' => (string) $system->name,
            ])
            ->values()
            ->toArray();

        if (!empty($this->systems)) {
            $this->data['type_rolled_curtains'] = (string) $this->systems[0]['code'];
        }

        $this->loadComponents((string) $this->data['type_rolled_curtains']);
    }

    public function updatedDataTypeRolledCurtains($systemCode): void
    {
        $this->loadComponents((string) $systemCode);
        $this->resetTotals();
    }

    public function calculateAction(?array $payload = null): void
    {
        if (is_array($payload) && !empty($payload)) {
            $this->data = array_replace_recursive($this->data, $payload);
        }

        $totals = app(CalculatorPricingService::class)->calculateTotals($this->data);

        $this->myPrice = (float) ($totals['cost_total'] ?? 0);
        $this->retailPrice = (float) ($totals['retail_total'] ?? 0);
        $this->usdRate = (float) data_get($totals, 'debug.usd_rate', 0);
        $this->priceBreakdown = $this->formatBreakdown(
            $totals['breakdown'] ?? [],
            $this->myPrice,
            $this->retailPrice,
            (string) data_get($totals, 'debug.calculation_date', $this->data['calculation_date']),
            $this->usdRate
        );
    }

    public function render()
    {
        return view('livewire.calculator');
    }

    private function loadComponents(string $systemCode): void
    {
        $this->components = [];
        $this->data['components_qty'] = [];
        $this->data['components_variant'] = [];

        if ($systemCode === '') {
            return;
        }

        $system = BlindSystem::query()
            ->where('code', $systemCode)
            ->with([
                'components' => fn ($query) => $query->orderBy('blind_component_system.position'),
                'components.variants',
            ])
            ->first();

        if (!$system) {
            return;
        }

        foreach ($system->components as $component) {
            $variantsSource = $component->variants->isNotEmpty()
                ? $component->variants
                : $this->guessVariantsForComponent((string) $component->name);

            $variants = $variantsSource
                ->map(fn ($variant) => [
                    'id' => (int) $variant->id,
                    'name' => (string) $variant->name,
                ])
                ->values()
                ->toArray();

            $this->components[] = [
                'id' => (int) $component->id,
                'name' => (string) $component->name,
                'variants' => $variants,
            ];

            $this->data['components_qty'][(int) $component->id] = false;

            if (count($variants) === 1) {
                $this->data['components_variant'][(int) $component->id] = (int) $variants[0]['id'];
            }
        }
    }

    private function guessVariantsForComponent(string $componentName): Collection
    {
        if ($this->variantComponentsCache === null) {
            $this->variantComponentsCache = BlindComponent::query()
                ->whereHas('variants')
                ->with('variants')
                ->get(['id', 'name']);
        }

        $targetTokens = $this->tokens($componentName);
        if (empty($targetTokens)) {
            return collect();
        }

        $best = null;
        $bestScore = -INF;

        foreach ($this->variantComponentsCache as $component) {
            $candidateTokens = $this->tokens((string) $component->name);
            $overlap = count(array_intersect($targetTokens, $candidateTokens));
            if ($overlap < 2) {
                continue;
            }

            $ratio = $overlap / max(1, count($targetTokens));
            if ($ratio < 0.6) {
                continue;
            }

            $score = ($ratio * 100)
                + ($overlap * 10)
                + min($component->variants->count(), 10);

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $component;
            }
        }

        return $best?->variants ?? collect();
    }

    private function tokens(string $value): array
    {
        $value = mb_strtolower($value);
        $value = str_replace(['управления', 'управление'], 'упр', $value);
        $value = str_replace(['×', 'x', 'х'], ' ', $value);
        $value = preg_replace('/[^a-zа-я0-9]+/iu', ' ', $value) ?? '';
        $parts = preg_split('/\s+/u', trim($value)) ?: [];

        $stopWords = [
            'для',
            'под',
            'или',
            'комплект',
            'комп',
            'пара',
            'мм',
            'амг',
            'ск',
            'rus',
            'универсальный',
            'универсальная',
            'универс',
            'с',
        ];

        $parts = array_filter($parts, function (string $token) use ($stopWords) {
            if (mb_strlen($token) < 2 && !is_numeric($token)) {
                return false;
            }

            return !in_array($token, $stopWords, true);
        });

        return array_values(array_unique($parts));
    }

    private function formatBreakdown(
        array $breakdown,
        float $costTotal,
        float $retailTotal,
        string $calculationDate,
        float $usdRate
    ): string
    {
        if (empty($breakdown)) {
            return 'Нет выбранных позиций для расчета';
        }

        $lines = [
            sprintf(
                'Курс USD на %s: %s',
                $calculationDate,
                number_format($usdRate, 4, '.', '')
            ),
            '---',
        ];

        foreach ($breakdown as $line) {
            $qty = (float) ($line['qty'] ?? 0);
            $unitRetail = (float) ($line['unit_retail'] ?? 0);
            $totalRetail = (float) ($line['total_retail'] ?? 0);
            $label = (string) ($line['label'] ?? '');

            $lines[] = sprintf(
                '%s | %s x %s = %s',
                $label,
                rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.'),
                number_format($unitRetail, 2, '.', ''),
                number_format($totalRetail, 2, '.', '')
            );
        }

        $lines[] = sprintf('Итого: %s', number_format($retailTotal, 2, '.', ''));

        return implode("\n", $lines);
    }

    private function resetTotals(): void
    {
        $this->myPrice = 0.0;
        $this->retailPrice = 0.0;
        $this->usdRate = 0.0;
        $this->priceBreakdown = '';
    }
}
