<?php

namespace App\Forms\RolledСurtains;

use App\Models\Calculator\BlindComponentVariant;
use App\Models\Calculator\BlindSystem;
use Illuminate\Support\Collection;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;

abstract class Form
{
    public static string $prefix = 'rol_';
    public static string $typeName = 'type_rolled_curtains';

    public static function all(Get $get): array
    {
        $systemCode = (string) $get('type_rolled_curtains');
        if ($systemCode === '') {
            return [];
        }

        return [
            Select::make('rol_price_tier')
                ->label('Тип цены')
                ->options([
                    'opt' => 'ОПТ',
                    'opt1' => 'ОПТ 1',
                    'opt2' => 'ОПТ 2',
                    'opt3' => 'ОПТ 3',
                    'opt4' => 'ОПТ 4',
                    'vip' => 'ВИП',
                ])
                ->default('opt')
                ->reactive(),

            Grid::make(1)
                ->schema(static::componentFields($systemCode)),
        ];
    }

    public static function getForm(): array
    {
        return [
            Select::make('type_rolled_curtains')
                ->label('Тип')
                ->options(
                    BlindSystem::query()
                        ->where('category', 'roller')
                        ->orderBy('name')
                        ->pluck('name', 'code')
                )
                ->searchable()
                ->reactive(),
        ];
    }

    private static function componentFields(string $systemCode): array
    {
        $system = BlindSystem::query()
            ->where('code', $systemCode)
            ->with([
                'components' => fn ($query) => $query->orderBy('blind_component_system.position'),
                'components.variants',
            ])
            ->first();

        if (!$system) {
            return [];
        }

        return $system->components
            ->map(function ($component) {
                $variants = $component->variants->isNotEmpty()
                    ? $component->variants
                    : static::guessVariantsForComponent((string) $component->name);

                $componentNameNormalized = static::normalizeForCompare((string) $component->name);

                // If variant name is identical to component name, it is not a real choice for UI.
                $variants = $variants
                    ->filter(fn ($variant) => static::normalizeForCompare((string) $variant->name) !== $componentNameNormalized)
                    ->values();

                $variantOptions = $variants
                    ->mapWithKeys(fn ($variant) => [$variant->id => $variant->name])
                    ->toArray();

                $defaultVariantId = $variants
                    ->firstWhere('is_default', true)?->id
                    ?? $variants->first()?->id;

                $isNumericQty = static::needsNumericQty((string) $component->name);
                $fields = [];

                if ($isNumericQty) {
                    $fields[] = TextInput::make("components_qty.{$component->id}")
                        ->label($component->name)
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->reactive();
                }

                if (!$isNumericQty) {
                    $fields[] = Toggle::make("components_qty.{$component->id}")
                        ->label($component->name)
                        ->default(false)
                        ->reactive();

                    if (count($variantOptions) > 1) {
                        $fields[] = Select::make("components_variant.{$component->id}")
                            ->label('Вариант')
                            ->options($variantOptions)
                            ->default($defaultVariantId)
                            ->searchable()
                            ->reactive();
                    }
                }

                if (count($variantOptions) === 1) {
                    $fields[] = Hidden::make("components_variant.{$component->id}")
                        ->default($defaultVariantId)
                        ->dehydrated(true);
                }

                return Group::make($fields)->columns(2);
            })
            ->toArray();
    }

    private static function guessVariantsForComponent(string $componentName): Collection
    {
        static $allVariants = null;
        static $byComponentId = [];

        if ($allVariants === null) {
            $allVariants = BlindComponentVariant::query()
                ->select(['id', 'blind_component_id', 'name', 'is_default'])
                ->get();
        }

        $targetTokens = static::tokens($componentName);
        if (empty($targetTokens)) {
            return collect();
        }

        $scores = [];
        foreach ($allVariants as $variant) {
            $variantTokens = static::tokens((string) $variant->name);
            $overlap = count(array_intersect($targetTokens, $variantTokens));
            if ($overlap <= 0) {
                continue;
            }

            $componentId = (int) $variant->blind_component_id;
            if (!isset($scores[$componentId]) || $overlap > $scores[$componentId]) {
                $scores[$componentId] = $overlap;
            }
        }

        if (empty($scores)) {
            return collect();
        }

        arsort($scores);
        $bestComponentId = (int) array_key_first($scores);
        $bestScore = (int) ($scores[$bestComponentId] ?? 0);

        // 2+ shared tokens protects from noisy matches and keeps "Вариант" useful.
        if ($bestScore < 2) {
            return collect();
        }

        if (!isset($byComponentId[$bestComponentId])) {
            $byComponentId[$bestComponentId] = $allVariants
                ->where('blind_component_id', $bestComponentId)
                ->values();
        }

        return $byComponentId[$bestComponentId];
    }

    private static function tokens(string $value): array
    {
        $value = mb_strtolower($value);
        $value = str_replace(['управления', 'управление'], 'упр', $value);
        $value = str_replace(['×', 'x'], ' ', $value);
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
            if (mb_strlen($token) < 2) {
                return false;
            }

            return !in_array($token, $stopWords, true);
        });

        return array_values(array_unique($parts));
    }

    private static function normalizeForCompare(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(['ё', '×', 'х'], ['е', 'x', 'x'], $value);
        $value = preg_replace('/[^a-z0-9а-яx]+/u', ' ', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return trim($value);
    }

    private static function needsNumericQty(string $name): bool
    {
        $name = mb_strtolower($name);

        // В количестве считаем только саморезы.
        // Все остальные позиции — выбор (toggle + вариант справа).
        return str_contains($name, 'саморез');
    }
}
