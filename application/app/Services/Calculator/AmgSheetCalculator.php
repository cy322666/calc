<?php

namespace App\Services\Calculator;

use App\Models\Calculator\BlindComponent;

class AmgSheetCalculator
{
    public function calculate(string $sheetKey, array $inputs, array $context = []): array
    {
        $sheet = $this->sheet($sheetKey);
        if (!$sheet) {
            return [
                'lines' => [],
                'missing' => [],
            ];
        }

        $vars = $this->normalizeInputs($inputs);
        $lines = [];
        $missing = [];
        $qtyByCell = [];
        $priceTier = (string) ($context['price_tier'] ?? 'opt');
        $color = (string) ($context['color'] ?? 'white');

        // Iterate a few times to resolve forward references.
        for ($pass = 0; $pass < 3; $pass++) {
            foreach ($sheet as $row) {
                $qty = $this->evalQty($row['qty'], $vars);
                $qty = $this->roundQty($qty);
                if (!empty($row['cell'])) {
                    $qtyByCell[$row['cell']] = $qty;
                    $vars[$row['cell']] = $qty;
                }
            }
        }

        foreach ($sheet as $row) {
            $qty = !empty($row['cell']) ? ($qtyByCell[$row['cell']] ?? 0) : $this->evalQty($row['qty'], $vars);
            $qty = $this->roundQty($qty);

            $resolvedName = $this->resolveVariantName((string) $row['name'], $color);
            if (!$this->shouldIncludeRow($resolvedName, $qty, $context)) {
                continue;
            }

            $component = $this->findComponent($resolvedName);
            $cost = $component ? $this->componentPrice($component, $priceTier, true) : 0.0;
            $retail = $component ? $this->componentPrice($component, $priceTier, false) : 0.0;

            if (!$component) {
                $missing[] = $resolvedName;
            }

            $lines[] = [
                'label' => $resolvedName,
                'qty' => $qty,
                'unit_cost' => $cost,
                'unit_retail' => $retail,
            ];
        }

        return [
            'lines' => $lines,
            'missing' => array_values(array_unique($missing)),
        ];
    }

    private function normalizeInputs(array $inputs): array
    {
        $vars = [];
        foreach ($inputs as $key => $value) {
            $vars[$key] = is_numeric($value) ? (float) $value : $value;
        }

        return $vars;
    }

    private function evalQty($expr, array $vars): float
    {
        if (is_numeric($expr)) {
            return (float) $expr;
        }

        if (!is_string($expr) || $expr === '') {
            return 0.0;
        }

        $e = $expr;
        $e = str_replace('<>', '!=', $e);
        $e = preg_replace('/(?<![<>!])=(?!=)/', '==', $e);

        $e = preg_replace('/\\bIF\\s*\\(/', '__if(', $e);
        $e = preg_replace('/\\bAND\\s*\\(/', '__and(', $e);
        $e = preg_replace('/\\bOR\\s*\\(/', '__or(', $e);
        $e = preg_replace('/\\bROUNDUP\\s*\\(/', '__roundup(', $e);
        $e = preg_replace('/\\bSUM\\s*\\(/', '__sum(', $e);

        $e = preg_replace_callback('/\\b([A-Z]+\\d+)\\b/', function ($m) use ($vars) {
            $key = $m[1];
            $val = $vars[$key] ?? 0;
            return is_numeric($val) ? (string) $val : '0';
        }, $e);

        if (preg_match('/[^0-9\\s\\+\\-\\*\\/\\(\\)\\.,<>!=_a-zA-Z]/', $e)) {
            return 0.0;
        }

        $code = 'return ' . $e . ';';

        $result = 0.0;
        try {
            $result = eval($code);
        } catch (\Throwable $e) {
            $result = 0.0;
        }

        return is_numeric($result) ? (float) $result : 0.0;
    }

    private function roundQty(float $qty): float
    {
        return round($qty, 4);
    }

    private function findComponent(string $name): ?BlindComponent
    {
        $normalized = $this->normalizeName($name);

        $exact = BlindComponent::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
            ->first();
        if ($exact) {
            return $exact;
        }

        $parts = explode(' ', $normalized);
        $needle = $parts[0] ?? '';
        if ($needle === '') {
            return null;
        }

        $candidates = BlindComponent::query()
            ->whereRaw('LOWER(name) LIKE ?', ['%' . $needle . '%'])
            ->limit(50)
            ->get();

        foreach ($candidates as $candidate) {
            if ($this->normalizeName((string) $candidate->name) === $normalized) {
                return $candidate;
            }
        }

        return null;
    }

    private function normalizeName(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(['ё', '×', 'х'], ['е', 'x', 'x'], $value);
        $value = preg_replace('/[^a-z0-9а-яx]+/u', ' ', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';
        return trim($value);
    }

    private function componentPrice(BlindComponent $component, string $tier, bool $fallbackToCost): float
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
        if ($field && $component->{$field} !== null) {
            return (float) $component->{$field};
        }

        return $fallbackToCost ? (float) $component->cost_price : (float) $component->retail_price;
    }

    private function resolveVariantName(string $name, string $color): string
    {
        $color = mb_strtolower(trim($color));
        $isWhite = $color === 'white' || $color === 'белый';

        // Map generic formula labels to concrete priced component names in DB.
        $common = [
            'Механизм упр. цепь 45 для монтажного профиля' => 'МЕХАНИЗМ УПР. 45 ДЛЯ МОНТАЖНОГО ПРОФИЛЯ (КОМПЛЕКТ) СК',
            'Цепь управления сплошная, пластик (LVT)' => 'ЦЕПЬ УПРАВЛЕНИЯ СПЛОШНАЯ, ПЛАСТИК (LVT), СЕРАЯ',
            'Цепь управления сплошная, металлическая, AMILUX' => 'ЦЕПЬ УПРАВЛЕНИЯ СПЛОШНАЯ, МЕТАЛЛИЧЕСКАЯ, ЗОЛОТО, МЕДЬ,  ЧЕРН. НИКЕЛЬ , ЧЕРНЫЙ',
        ];

        if (isset($common[$name])) {
            $name = $common[$name];
        }

        if ($isWhite) {
            $white = [
                'Механизм упр. цепь 32 (комплект), универсальный СК' => 'МЕХАНИЗМ УПР. ЦЕПЬ 32 (КОМПЛЕКТ), УНИВ. СК, БЕЛЫЙ',
                'Механизм упр. цепь 45 (комплект) СК' => 'МЕХАНИЗМ УПР. ЦЕПЬ 45 (КОМПЛЕКТ), СК, БЕЛЫЙ',
                'Ограничитель цепи управления, белый' => 'ОГРАНИЧИТЕЛЬ ЦЕПИ УПРАВЛЕНИЯ 4,5*6ММ ПРОЗРАЧНЫЙ СК',
            ];
            return $white[$name] ?? $name;
        }

        $nonWhite = [
            'Крышка кронштейна 32 (пара), белая СК' => 'Крышка кронштейна 32 (пара), антрацит, св. серая, черная СК',
            'Крышка кронштейна 45 (пара), белая СК' => 'Крышка кронштейна 45 (пара), антрацит, св. серая, черная СК',
            'Крышка кронштейна 32, белая' => 'Крышка кронштейна 32, антрацит',
            'Крышка кронштейна 45, белая' => 'Крышка кронштейна 45, антрацит',
            'Заглушка нижней рейки, белая СК' => 'Заглушка нижней рейки, св. серая, черная СК',
            'Ограничитель цепи управления, белый' => 'Ограничитель цепи управления, т.серый',
            'Механизм упр. цепь 32 (комплект), универсальный СК' => 'МЕХАНИЗМ УПР. ЦЕПЬ 32 (КОМПЛЕКТ), УНИВ. СК, АНТРАЦИТ, СВ. СЕРЫЙ, ЧЕРНЫЙ',
            'Механизм упр. цепь 45 (комплект) СК' => 'МЕХАНИЗМ УПР. ЦЕПЬ 45 (КОМПЛЕКТ), СК, АНТРАЦИТ, СВ. СЕРЫЙ, ЧЕРНЫЙ',
        ];

        return $nonWhite[$name] ?? $name;
    }

    private function shouldIncludeRow(string $name, float $qty, array $context): bool
    {
        if ($qty > 0) {
            return true;
        }

        $chainColor = (string) ($context['chain_color'] ?? '');
        if ($chainColor === '') {
            return false;
        }

        $normalized = $this->normalizeName($name);

        $isChainPlastic = str_contains($normalized, 'цепь управления сплошная пластик');
        $isChainMetal = str_contains($normalized, 'цепь управления сплошная металлическая');
        $isLockPlastic = str_contains($normalized, 'замок цепи управления пластиковый');
        $isLockMetal = str_contains($normalized, 'замок цепи управления металлический');

        if ($chainColor === 'plastic' && ($isChainPlastic || $isLockPlastic)) {
            return true;
        }

        if ($chainColor === 'metal' && ($isChainMetal || $isLockMetal)) {
            return true;
        }

        return false;
    }

    private function sheet(string $key): ?array
    {
        $sheets = $this->sheets();
        return $sheets[$key] ?? null;
    }

    private function sheets(): array
    {
        return [
            'amg_classic' => [
                ['cell' => 'B14', 'name' => 'Заглушка нижней рейки, белая СК', 'qty' => 'IF(B8+B9==0,2*(B4+B5+B6+B7),2*((B4+B5+B6+B7)-(B8+B9)))'],
                ['cell' => 'B15', 'name' => 'Заглушка нижней рейки, бок. фиксация', 'qty' => '2*(B8+B9)'],
                ['cell' => 'B16', 'name' => 'Замок цепи управления, металлический', 'qty' => 'IF(B10==1,(B4+B5+B6+B7),0)'],
                ['cell' => 'B17', 'name' => 'Замок цепи управления, пластиковый, односоставный', 'qty' => 'IF(B10==0,(B5+B6+B7+B4),0)'],
                ['cell' => 'B18', 'name' => 'Комплект потолочных кронштейнов, бок. фиксация', 'qty' => 'B9'],
                ['cell' => 'B19', 'name' => 'Комплект стеновых кронштейнов, бок. фиксация', 'qty' => 'B8'],
                ['cell' => 'B20', 'name' => 'Кольцо стопорное с винтом', 'qty' => 'B8*4+B9*4'],
                ['cell' => 'B21', 'name' => 'Кронштейн потолочный кассеты 32 СК', 'qty' => '(IF(B2<=1,2,IF(B2<=1.5,3,IF(B2<=2,4,IF(B2<=2.5,5,6)))))*(B6+B7)'],
                ['cell' => 'B22', 'name' => 'Крышка кронштейна 45, белая', 'qty' => '2*B5'],
                ['cell' => 'B23', 'name' => 'Крышка кронштейна 32 (пара), белая СК', 'qty' => 'B4'],
                ['cell' => 'B24', 'name' => 'Крышка удл. кронштейна 32 для монт.профиля,пара СК', 'qty' => 'B6'],
                ['cell' => 'B25', 'name' => 'Крышка удл. кронштейна 45 для монт.профиля СК', 'qty' => '2*B7'],
                ['cell' => 'B26', 'name' => 'Механизм упр. цепь 32 (комплект), универсальный СК', 'qty' => 'B4+B6'],
                ['cell' => 'B27', 'name' => 'Механизм упр. цепь 45 (комплект) СК', 'qty' => 'B5'],
                ['cell' => 'B28', 'name' => 'Механизм упр. цепь 45 для монтажного профиля', 'qty' => 'B7'],
                ['cell' => 'B29', 'name' => 'Натяжитель цепи (LVT)', 'qty' => 'B11'],
                ['cell' => 'B30', 'name' => 'Ограничитель цепи управления, белый', 'qty' => '2*(B6+B7+B4+B5)'],
                ['cell' => 'B31', 'name' => 'Пластиковая полоса-фиксатор клейкая 7мм', 'qty' => 'B2*(B4+B5+B6+B7)'],
                ['cell' => 'B32', 'name' => 'Полоса-фиксатор 9мм', 'qty' => 'B2*(B4+B5+B6+B7)'],
                ['cell' => 'B33', 'name' => 'Профиль монтажный (AMG), универсальный', 'qty' => 'B2*(B6+B7)'],
                ['cell' => 'B34', 'name' => 'Рейка нижняя алюминий под полосу (AMG), белая', 'qty' => 'B2*(B4+B5+B6+B7)'],
                ['cell' => 'B35', 'name' => 'Саморез, 2.9x6,5 DIN 7981 остроконечный', 'qty' => '4*(B6+B7)'],
                ['cell' => 'B36', 'name' => 'Трос металлический', 'qty' => '(2*B3+0.2)*(B8+B9)'],
                ['cell' => 'B37', 'name' => 'Труба 32 мм с пазом (AMG)', 'qty' => 'B2*(B4+B6)'],
                ['cell' => 'B38', 'name' => 'Труба 45 мм с 3-мя пазами (AMG)', 'qty' => 'B2*(B5+B7)'],
                ['cell' => 'B39', 'name' => 'Цепь управления сплошная, пластик (LVT)', 'qty' => 'IF(B10==0,(B3*0.75*2)*(B4+B5+B6+B7),0)'],
                ['cell' => 'B40', 'name' => 'Цепь управления сплошная, металлическая, AMILUX', 'qty' => 'IF(B10==1,(B3*0.75*2)*(B4+B5+B6+B7),0)'],
            ],
            'amg_zebra_32' => [
                ['cell' => 'B11', 'name' => 'Заглушка для трубки нижней 12мм прозрачная,зебра', 'qty' => 'B5*B4*2'],
                ['cell' => 'B12', 'name' => 'Крышка боковая для отвеса двойного ЗЕБРА,к-кт', 'qty' => '2*B6*B4'],
                ['cell' => 'B13', 'name' => 'Замок цепи управления, пластиковый, односоставный', 'qty' => 'IF(AND(B7==0,B3>4),B4,0)'],
                ['cell' => 'B14', 'name' => 'Крышка удл. кронштейна 32 для монт.профиля,пара СК', 'qty' => 'B4'],
                ['cell' => 'B15', 'name' => 'Механизм упр. цепь 32 (комплект), универсальный СК', 'qty' => 'B4'],
                ['cell' => 'B16', 'name' => 'Натяжитель цепи (LVT)', 'qty' => 'B8*B4'],
                ['cell' => 'B17', 'name' => 'Пластиковая полоса-фиксатор клейкая 7мм', 'qty' => 'B2*B4'],
                ['cell' => 'B18', 'name' => 'Полоса-фиксатор 9мм', 'qty' => 'B2*B4'],
                ['cell' => 'B19', 'name' => 'Профиль монтажный (AMG), универсальный', 'qty' => 'B2*B4'],
                ['cell' => 'B20', 'name' => 'Саморез, 2.9x6,5 DIN 7981 остроконечный', 'qty' => '4*B4'],
                ['cell' => 'B21', 'name' => 'Труба 32 мм с пазом (AMG)', 'qty' => 'B2*B4'],
                ['cell' => 'B22', 'name' => 'Цепь петля MGS 50мм', 'qty' => 'IF(AND(B3>0.1,B3<1,B7==0),B4,0)'],
                ['cell' => 'B23', 'name' => 'Цепь петля MGS 80мм', 'qty' => 'IF(AND(B3>0.9,B3<1.2,B7==0),B4,0)'],
                ['cell' => 'B24', 'name' => 'Цепь петля MGS 100мм', 'qty' => 'IF(AND(B3>1.1,B3<1.4,B7==0),B4,0)'],
                ['cell' => 'B25', 'name' => 'Цепь петля MGS 130мм', 'qty' => 'IF(AND(B3>1.3,B3<1.6,B7==0),B4,0)'],
                ['cell' => 'B26', 'name' => 'Цепь петля MGS 150мм', 'qty' => 'IF(AND(B3>1.5,B3<2,B7==0),B4,0)'],
                ['cell' => 'B27', 'name' => 'Цепь петля MGS 180мм', 'qty' => 'IF(AND(B3>1.9,B3<2.3,B7==0),B4,0)'],
                ['cell' => 'B28', 'name' => 'Цепь петля MGS 200мм', 'qty' => 'IF(AND(B3>2.2,B3<2.6,B7==0),B4,0)'],
                ['cell' => 'B29', 'name' => 'Цепь петля MGS 230мм', 'qty' => 'IF(AND(B3>2.5,B3<2.8,B7==0),B4,0)'],
                ['cell' => 'B30', 'name' => 'Цепь петля MGS 250мм', 'qty' => 'IF(AND(B3>2.7,B3<3,B7==0),B4,0)'],
                ['cell' => 'B31', 'name' => 'Цепь петля MGS 280мм', 'qty' => 'IF(AND(B3>2.9,B3<4,B7==0),B4,0)'],
                ['cell' => 'B32', 'name' => 'Кронштейн потолочный кассеты 32 СК', 'qty' => '(IF(B2<=1,2,IF(B2<=1.5,3,IF(B2<=2,4,IF(B2<=2.5,5,6)))))*B4'],
                ['cell' => 'B33', 'name' => 'Замок цепи управления, пластиковый, односоставный', 'qty' => 'IF(B34>0,B4,0)'],
                ['cell' => 'B34', 'name' => 'Цепь управления сплошная, пластик (LVT)', 'qty' => 'IF(B7==0,(B3*1.15*2)*B4,0)'],
                ['cell' => 'B35', 'name' => 'Цепь управления сплошная, металлическая, AMILUX', 'qty' => 'IF(B7==1,(B3*0.75*2)*B4,0)'],
                ['cell' => 'B36', 'name' => 'Трубка нижняя белая 12 мм,зебра', 'qty' => 'B2*B4'],
                ['cell' => 'B37', 'name' => 'Рейка нижняя Зебра', 'qty' => 'B6*B4*B2'],
            ],
            'amg_cassette' => [
                ['cell' => 'B16', 'name' => 'Заглушка нижней рейки, белая СК', 'qty' => '2*(B4+B5+B8+B9)'],
                ['cell' => 'B17', 'name' => 'Замок цепи управления, металлический', 'qty' => 'IF(B12==1,B4+B5+B8+B9,0)'],
                ['cell' => 'B18', 'name' => 'Замок цепи управления, пластиковый, односоставный', 'qty' => 'IF(B12==0,(B4+B5+B8+B9),0)'],
                ['cell' => 'B19', 'name' => 'Кронштейн потолочный кассеты 32 СК', 'qty' => '(IF(B2<=1,2,IF(B2<=1.5,3,IF(B2<=2,4,IF(B2<=2.5,5,6)))))*(B4+B5)-B21'],
                ['cell' => 'B20', 'name' => 'Кронштейн потолочный кассеты 45 СК', 'qty' => '(IF(B2<=1,2,IF(B2<=1.5,3,IF(B2<=2,4,IF(B2<=2.5,5,6)))))*(B9+B8)-B22'],
                ['cell' => 'B21', 'name' => 'Кронштейн стеновой кассеты 32 СК', 'qty' => '(IF(B2<=1,2,IF(B2<=1.5,3,IF(B2<=2,4,IF(B2<=2.5,5,6)))))*(B7)'],
                ['cell' => 'B22', 'name' => 'Кронштейн стеновой кассеты 45 СК', 'qty' => '(IF(B2<=1,2,IF(B2<=1.5,3,IF(B2<=2,4,IF(B2<=2.5,5,6)))))*(B11)'],
                ['cell' => 'B23', 'name' => 'Лента клейкая д/трубы 12мм, м', 'qty' => 'B2*2*(B5+B9)'],
                ['cell' => 'B24', 'name' => 'Механизм упр. цепь кассеты 32+, левый (комп)', 'qty' => 'B6'],
                ['cell' => 'B25', 'name' => 'Механизм упр. цепь кассеты 32+, правый (комп)', 'qty' => '(B4+B5)-B24'],
                ['cell' => 'B26', 'name' => 'Механизм упр. цепь кассеты 45, левый (комплект)', 'qty' => 'B10'],
                ['cell' => 'B27', 'name' => 'Механизм упр. цепь кассеты 45, правый (комплект)', 'qty' => '(B8+B9)-B26'],
                ['cell' => 'B28', 'name' => 'Натяжитель цепи (LVT)', 'qty' => 'B13'],
                ['cell' => 'B29', 'name' => 'Пластиковая полоса-фиксатор клейкая 7мм', 'qty' => 'B2*(B4+B5+B8+B9)'],
                ['cell' => 'B30', 'name' => 'Полоса-фиксатор 9мм', 'qty' => 'B29'],
                ['cell' => 'B31', 'name' => 'Профиль лицевой кассеты 32, без паза', 'qty' => 'B2*B4'],
                ['cell' => 'B32', 'name' => 'Профиль лицевой кассеты 32, с пазом', 'qty' => 'B2*B5'],
                ['cell' => 'B33', 'name' => 'Профиль лицевой кассеты 45, без паза', 'qty' => 'B2*B8'],
                ['cell' => 'B34', 'name' => 'Профиль лицевой кассеты 45, с пазом', 'qty' => 'B2*B9'],
                ['cell' => 'B35', 'name' => 'Профиль соединительный кассеты 32', 'qty' => 'B2*(B4+B5)'],
                ['cell' => 'B36', 'name' => 'Профиль соединительный кассеты 45', 'qty' => 'B2*(B8+B9)'],
                ['cell' => 'B37', 'name' => 'Рейка нижняя алюминий под полосу (AMG), белая', 'qty' => 'B2*(B4+B5+B8+B9)'],
                ['cell' => 'B38', 'name' => 'Ограничитель цепи управления, белый', 'qty' => '2*(B4+B5+B8+B9)'],
                ['cell' => 'B39', 'name' => 'Труба 32 мм с пазом (AMG)', 'qty' => 'B2*(B4+B5)'],
                ['cell' => 'B40', 'name' => 'Труба 45 мм с 3-мя пазами (AMG)', 'qty' => 'B2*(B8+B9)'],
                ['cell' => 'B41', 'name' => 'Цепь управления сплошная, пластик (LVT)', 'qty' => 'IF(B12==0,B3*2*0.75*(B4+B5+B8+B9),0)'],
                ['cell' => 'B42', 'name' => 'Цепь управления сплошная, металлическая, AMILUX', 'qty' => 'IF(B12==1,B3*2*0.75*(B4+B5+B8+B9),0)'],
            ],
            'amg_cassette_guides' => [
                ['cell' => 'B16', 'name' => 'Замок цепи управления, металлический', 'qty' => 'IF(B12==1,B6+B7+B9+B10,0)'],
                ['cell' => 'B17', 'name' => 'Замок цепи управления, пластиковый, односоставный', 'qty' => 'IF(B12==2,(B6+B7+B9+B10),0)'],
                ['cell' => 'B18', 'name' => 'Кронштейн потолочный кассеты 32 СК', 'qty' => 'IF(B5==2,(IF(B2<=1,2,IF(B2<=1.5,3,IF(B2<=2,4,IF(B2<=2.5,5,6)))))*(B6+B7),0)'],
                ['cell' => 'B19', 'name' => 'Кронштейн потолочный кассеты 45 СК', 'qty' => 'IF(B5==2,(IF(B2<=1,2,IF(B2<=1.5,3,IF(B2<=2,4,IF(B2<=2.5,5,6)))))*(B10+B9),0)'],
                ['cell' => 'B20', 'name' => 'Лента клейкая д/трубы 12мм, м', 'qty' => 'B2*2*(B7+B10)'],
                ['cell' => 'B21', 'name' => 'Механизм упр. цепь кассеты 32+, левый (комп)', 'qty' => 'B8'],
                ['cell' => 'B22', 'name' => 'Механизм упр. цепь кассеты 32+, правый (комп)', 'qty' => '(B6+B7)-B21'],
                ['cell' => 'B23', 'name' => 'Механизм упр. цепь кассеты 45, левый (комплект)', 'qty' => 'B11'],
                ['cell' => 'B24', 'name' => 'Механизм упр. цепь кассеты 45, правый (комплект)', 'qty' => '(B9+B10)-B23'],
                ['cell' => 'B25', 'name' => 'Натяжитель цепи (LVT)', 'qty' => 'B13'],
                ['cell' => 'B26', 'name' => 'Пластиковая полоса-фиксатор клейкая 7мм', 'qty' => 'B2*(B6+B7+B9+B10)'],
                ['cell' => 'B27', 'name' => 'Заглушка нижней рейки, белая СК', 'qty' => '(B6+B7+B9+B10)*2'],
                ['cell' => 'B28', 'name' => 'Полоса-фиксатор 9мм', 'qty' => 'B27'],
                ['cell' => 'B29', 'name' => 'Рейка нижняя алюминий под полосу (AMG), белая', 'qty' => 'B2*(B6+B7+B9+B10)'],
                ['cell' => 'B30', 'name' => 'Профиль лицевой кассеты 32, без паза', 'qty' => 'B2*B6'],
                ['cell' => 'B31', 'name' => 'Профиль лицевой кассеты 32, с пазом', 'qty' => 'B2*B7'],
                ['cell' => 'B32', 'name' => 'Профиль лицевой кассеты 45, без паза', 'qty' => 'B2*B9'],
                ['cell' => 'B33', 'name' => 'Профиль лицевой кассеты 45, с пазом', 'qty' => 'B2*B10'],
                ['cell' => 'B34', 'name' => 'Профиль соединительный кассеты 32', 'qty' => 'B2*(B6+B7)'],
                ['cell' => 'B35', 'name' => 'Профиль соединительный кассеты 45', 'qty' => 'B2*(B9+B10)'],
                ['cell' => 'B36', 'name' => 'Ограничитель цепи управления, белый', 'qty' => '2*(B6+B7+B9+B10)'],
                ['cell' => 'B37', 'name' => 'Труба 32 мм с пазом (AMG)', 'qty' => 'B2*(B6+B7)'],
                ['cell' => 'B38', 'name' => 'Труба 45 мм с 3-мя пазами (AMG)', 'qty' => 'B2*(B9+B10)'],
                ['cell' => 'B39', 'name' => 'Соединитель кассеты и направляющей, пара (LVT)', 'qty' => 'B6+B7'],
                ['cell' => 'B40', 'name' => 'Заглушка для отверстия в направляющей (LVT)', 'qty' => 'IF(AND(B5==1,B4==1),(2+2*ROUNDUP(((B3-0.073-0.05)/0.4),0))*(B6+B7+B9+B10)+(2+ROUNDUP(((B2-0.073-0.05)/0.4),0))*(B6+B7+B9+B10),IF(AND(B5==1,B4==2),(2+2*ROUNDUP(((B3-0.073-0.05)/0.4),0))*(B6+B7+B9+B10),0))'],
                ['cell' => 'B41', 'name' => 'Направляющая (LVT)', 'qty' => 'IF(B4==1,B3*(B6+B7+B9+B10)*2,B3*(B6+B7+B9+B10)*2+B2*(B6+B7+B9+B10))'],
                ['cell' => 'B42', 'name' => 'Крышка для направляющей (LVT)', 'qty' => 'B20*2'],
                ['cell' => 'B43', 'name' => 'Шлегель для направляющей (LVT)', 'qty' => 'B3*(B6+B7+B9+B10)*4'],
                ['cell' => 'B44', 'name' => 'Цепь управления сплошная, пластик (LVT)', 'qty' => 'IF(B12==0,B3*2*1.4*(B6+B7+B9+B10),0)'],
                ['cell' => 'B45', 'name' => 'Цепь управления сплошная, металлическая, AMILUX', 'qty' => 'IF(B12==1,B3*2*1.4*(B6+B7+B9+B10),0)'],
            ],
            'amg_spring_32' => [
                ['cell' => 'B10', 'name' => 'Автостоп пружин. механизма 32 длинный', 'qty' => 'IF(B3>1.2,B4+B5,0)'],
                ['cell' => 'B11', 'name' => 'Автостоп пружин. механизма 32 короткий', 'qty' => 'IF(B3<=1.2,B4+B5,0)'],
                ['cell' => 'B12', 'name' => 'Рейка нижняя алюминий под полосу (AMG), белая', 'qty' => 'IF(B6+B7==0,2*(B4+B5),2*((B4+B5)-(B6+B7)))'],
                ['cell' => 'B13', 'name' => 'Заглушка нижней рейки, бок. фиксация', 'qty' => '2*(B6+B7)'],
                ['cell' => 'B14', 'name' => 'Комплект потолочных кронштейнов, бок. фиксация', 'qty' => 'B7'],
                ['cell' => 'B15', 'name' => 'Комплект стеновых кронштейнов, бок. фиксация', 'qty' => 'B6'],
                ['cell' => 'B16', 'name' => 'Кронштейн потолочный кассеты 32 СК', 'qty' => '(IF(B2<=1,2,IF(B2<=1.5,3,IF(B2<=2,4,IF(B2<=2.5,5,6)))))*(B5)'],
                ['cell' => 'B17', 'name' => 'Крышка кронштейна 32 (пара), белая СК', 'qty' => '2*B4'],
                ['cell' => 'B18', 'name' => 'Крышка удл. кронштейна 32 для монт.профиля,пара СК', 'qty' => '2*B5'],
                ['cell' => 'B19', 'name' => 'Механизм упр. со средней пружиной 32 (комплект)', 'qty' => 'B4'],
                ['cell' => 'B20', 'name' => 'Пластиковая полоса-фиксатор клейкая 7мм', 'qty' => 'B2*(B4+B5)'],
                ['cell' => 'B21', 'name' => 'Полоса-фиксатор 9мм', 'qty' => 'B20'],
                ['cell' => 'B22', 'name' => 'Профиль монтажный (AMG), универсальный', 'qty' => 'B2*B5'],
                ['cell' => 'B23', 'name' => 'Рейка нижняя алюминий под полосу (AMG), белая', 'qty' => 'B2*(B4+B5)'],
                ['cell' => 'B24', 'name' => 'Ручка управления нижней рейки', 'qty' => '1*(B4+B5)'],
                ['cell' => 'B25', 'name' => 'Саморез, 2.9x6,5 DIN 7981 остроконечный', 'qty' => '4*B5'],
                ['cell' => 'B26', 'name' => 'Трос металлический', 'qty' => '(2*B3+0.2)*(B6+B7)'],
                ['cell' => 'B27', 'name' => 'Труба 32 мм с пазом (AMG)', 'qty' => 'B2*(B4+B5)'],
                ['cell' => 'B28', 'name' => 'Кольцо стопорное с винтом', 'qty' => '4*(B6+B7)'],
            ],
            'amg_day_night_32' => [
                ['cell' => 'B9', 'name' => 'Заглушка нижней рейки, белая СК', 'qty' => '4*B4'],
                ['cell' => 'B10', 'name' => 'Заглушка д/трубы 32 для двойного кронштейна', 'qty' => '2*B4'],
                ['cell' => 'B11', 'name' => 'Замок цепи управления, металлический', 'qty' => 'IF(B5==1,B4,0)'],
                ['cell' => 'B12', 'name' => 'Замок цепи управления, пластиковый, односоставный', 'qty' => 'IF(B5==0,B4*2,0)'],
                ['cell' => 'B13', 'name' => 'Кронштейн двойной 32 (комплект)', 'qty' => 'B4'],
                ['cell' => 'B14', 'name' => 'Крышка двойного кронштейна 32 (комплект)', 'qty' => 'B13'],
                ['cell' => 'B15', 'name' => 'Механизм упр. цепь 32+ (комплект), белый', 'qty' => '2*B4'],
                ['cell' => 'B16', 'name' => 'Натяжитель цепи (LVT)', 'qty' => 'B6'],
                ['cell' => 'B17', 'name' => 'Ограничитель цепи управления, белый', 'qty' => '4*B4'],
                ['cell' => 'B18', 'name' => 'Пластиковая полоса-фиксатор клейкая 7мм', 'qty' => '2*B2*B4'],
                ['cell' => 'B19', 'name' => 'Полоса-фиксатор 9мм', 'qty' => 'B18'],
                ['cell' => 'B20', 'name' => 'Рейка нижняя алюминий под полосу (AMG), белая', 'qty' => '2*B2*B4'],
                ['cell' => 'B21', 'name' => 'Труба 32 мм с пазом (AMG)', 'qty' => 'B20'],
                ['cell' => 'B22', 'name' => 'Цепь управления сплошная, пластик (LVT)', 'qty' => 'IF(B5==0,B3*2*0.72*2*B4,0)'],
                ['cell' => 'B23', 'name' => 'Цепь управления сплошная, металлическая, AMILUX', 'qty' => 'IF(B5==1,B3*2*0.72*2*B4,0)'],
            ],
            'amg_day_night_45' => [
                ['cell' => 'B9', 'name' => 'Заглушка нижней рейки, белая СК', 'qty' => '4*B4'],
                ['cell' => 'B10', 'name' => 'Замок цепи управления, металлический', 'qty' => 'IF(B5==1,B4,0)'],
                ['cell' => 'B11', 'name' => 'Замок цепи управления, пластиковый, односоставный', 'qty' => 'IF(B5==0,B4*2,0)'],
                ['cell' => 'B12', 'name' => 'Кронштейн двойной 45', 'qty' => 'B4'],
                ['cell' => 'B13', 'name' => 'Крышка двойного кронштейна 45', 'qty' => 'B12'],
                ['cell' => 'B14', 'name' => 'Механизм упр. цепь 45 (комплект) СК', 'qty' => '2*B4'],
                ['cell' => 'B15', 'name' => 'Натяжитель цепи (LVT)', 'qty' => 'B6'],
                ['cell' => 'B16', 'name' => 'Ограничитель цепи управления, белый', 'qty' => '4*B4'],
                ['cell' => 'B17', 'name' => 'Пластиковая полоса-фиксатор клейкая 7мм', 'qty' => '2*B2*B4'],
                ['cell' => 'B18', 'name' => 'Полоса-фиксатор 9мм', 'qty' => 'B17'],
                ['cell' => 'B19', 'name' => 'Рейка нижняя алюминий под полосу (AMG), белая', 'qty' => '2*B2*B4'],
                ['cell' => 'B20', 'name' => 'Труба 45 мм с 3-мя пазами (AMG)', 'qty' => 'B19'],
                ['cell' => 'B21', 'name' => 'Цепь управления сплошная, пластик (LVT)', 'qty' => 'IF(B5==0,B3*2*0.72*2*B4,0)'],
                ['cell' => 'B22', 'name' => 'Цепь управления сплошная, металлическая, AMILUX', 'qty' => 'IF(B5==1,B3*2*0.72*2*B4,0)'],
            ],
            'amg_classic_double' => [
                ['cell' => 'B10', 'name' => 'Заглушка нижней рейки, белая СК', 'qty' => '4*B4'],
                ['cell' => 'B11', 'name' => 'Замок цепи управления, металлический', 'qty' => 'IF(B6==1,(B4),0)*2'],
                ['cell' => 'B12', 'name' => 'Замок цепи управления, пластиковый, односоставный', 'qty' => 'IF(B6==0,(B4),0)*2'],
                ['cell' => 'B13', 'name' => 'Кронштейн промежуточный 45', 'qty' => 'B4'],
                ['cell' => 'B14', 'name' => 'Крышка кронштейна 45, белая', 'qty' => '2*(B4-B5)'],
                ['cell' => 'B15', 'name' => 'Механизм упр. цепь 45 (комплект) СК', 'qty' => '(B4-B5)*2'],
                ['cell' => 'B16', 'name' => 'Крышка удл. кронштейна 45 для монт.профиля СК', 'qty' => 'B5*2'],
                ['cell' => 'B17', 'name' => 'Механизм упр. цепь 45 для монтажного профиля', 'qty' => 'B5*2'],
                ['cell' => 'B18', 'name' => 'Профиль монтажный (AMG), универсальный', 'qty' => 'B5*B2'],
                ['cell' => 'B19', 'name' => 'Натяжитель цепи (LVT)', 'qty' => 'B7'],
                ['cell' => 'B20', 'name' => 'Ограничитель цепи управления, белый', 'qty' => '2*(B4)*2'],
                ['cell' => 'B21', 'name' => 'Пластиковая полоса-фиксатор клейкая 7мм', 'qty' => 'B2*(B4)'],
                ['cell' => 'B22', 'name' => 'Полоса-фиксатор 9мм', 'qty' => 'B24'],
                ['cell' => 'B23', 'name' => 'Пластина подкладочная для MONO/DOUBLE AMG', 'qty' => 'B4'],
                ['cell' => 'B24', 'name' => 'Рейка нижняя алюминий под полосу (AMG), белая', 'qty' => 'B2*(B4)'],
                ['cell' => 'B25', 'name' => 'Труба 45 мм с 3-мя пазами (AMG)', 'qty' => 'B2*(B4)'],
                ['cell' => 'B26', 'name' => 'Саморез, 2.9x6,5 DIN 7981 остроконечный', 'qty' => '4*(B5)'],
                ['cell' => 'B27', 'name' => 'Кронштейн потолочный кассеты 32 СК', 'qty' => '(IF(B2<=1,2,IF(B2<=1.5,3,IF(B2<=2,4,IF(B2<=2.5,5,6)))))*(B5)'],
                ['cell' => 'B28', 'name' => 'Цепь управления сплошная, пластик (LVT)', 'qty' => 'IF(B6==0,(B3*0.75*2)*(B4),0)*2'],
                ['cell' => 'B29', 'name' => 'Цепь управления сплошная, металлическая, AMILUX', 'qty' => 'IF(B6==1,(B3*0.75*2)*(B4),0)*2'],
            ],
            'amg_classic_mono' => [
                ['cell' => 'B11', 'name' => 'Заглушка нижней рейки, белая СК', 'qty' => '2*B5*B4'],
                ['cell' => 'B12', 'name' => 'Замок цепи управления, металлический', 'qty' => 'IF(B7==1,(B5),0)'],
                ['cell' => 'B13', 'name' => 'Замок цепи управления, пластиковый, односоставный', 'qty' => '2*B3'],
                ['cell' => 'B14', 'name' => 'Кронштейн соединительный 45', 'qty' => '(B4-1)*B5'],
                ['cell' => 'B15', 'name' => 'Крышка кронштейна 45, белая', 'qty' => '2*(B5-B6)'],
                ['cell' => 'B16', 'name' => 'Пластина подкладочная для MONO/DOUBLE AMG', 'qty' => 'B5*2'],
                ['cell' => 'B17', 'name' => 'Механизм упр. цепь 45 (комплект) СК', 'qty' => 'B5-B6'],
                ['cell' => 'B18', 'name' => 'Крышка удл. кронштейна 45 для монт.профиля СК', 'qty' => '2*B6'],
                ['cell' => 'B19', 'name' => 'Механизм упр. цепь 45 для монтажного профиля', 'qty' => 'B6'],
                ['cell' => 'B20', 'name' => 'Профиль монтажный (AMG), универсальный', 'qty' => 'B6*B2'],
                ['cell' => 'B21', 'name' => 'Натяжитель цепи (LVT)', 'qty' => 'B8'],
                ['cell' => 'B22', 'name' => 'Ограничитель цепи управления, белый', 'qty' => '2*(B5)'],
                ['cell' => 'B23', 'name' => 'Пластиковая полоса-фиксатор клейкая 7мм', 'qty' => 'B2*(B5)'],
                ['cell' => 'B24', 'name' => 'Полоса-фиксатор 9мм', 'qty' => 'B25'],
                ['cell' => 'B25', 'name' => 'Рейка нижняя алюминий под полосу (AMG), белая', 'qty' => 'B2*(B5)'],
                ['cell' => 'B26', 'name' => 'Труба 45 мм с 3-мя пазами (AMG)', 'qty' => 'B2*(B5)'],
                ['cell' => 'B27', 'name' => 'Саморез, 2.9x6,5 DIN 7981 остроконечный', 'qty' => '4*(B6)'],
                ['cell' => 'B28', 'name' => 'Кронштейн потолочный кассеты 32 СК', 'qty' => '(IF(B2<=1,2,IF(B2<=1.5,3,IF(B2<=2,4,IF(B2<=2.5,5,6)))))*(B6)'],
                ['cell' => 'B29', 'name' => 'Цепь управления сплошная, пластик (LVT)', 'qty' => 'IF(B7==0,(B3*0.75*2)*(B5),0)'],
                ['cell' => 'B30', 'name' => 'Цепь управления сплошная, металлическая, AMILUX', 'qty' => 'IF(B7==1,(B3*0.75*2)*(B5),0)'],
            ],
        ];
    }
}

function __if($cond, $a, $b)
{
    return $cond ? $a : $b;
}

function __and(...$args)
{
    foreach ($args as $arg) {
        if (!$arg) {
            return false;
        }
    }
    return true;
}

function __or(...$args)
{
    foreach ($args as $arg) {
        if ($arg) {
            return true;
        }
    }
    return false;
}

function __roundup($number, $digits = 0)
{
    $factor = pow(10, (int) $digits);
    return ceil($number * $factor) / $factor;
}

function __sum(...$args)
{
    return array_sum($args);
}
