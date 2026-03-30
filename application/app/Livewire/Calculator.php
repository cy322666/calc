<?php

namespace App\Livewire;

use App\Models\Calculator\BlindSystem;
use App\Models\Calculator\Fabric;
use App\Models\Shop\Category;
use App\Models\Shop\Product;
use App\Models\User;
use App\Services\Calculator\CalculatorPricingService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class Calculator extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    public ?array $data = [];
    private bool $isRecalculating = false;

    public function render()
    {
        return view('livewire.calculator');
    }

    public function calculateAction(): void
    {
        $this->recalculate();
    }

    private function result(Get $get): int
    {
        $temp = ($get('weight') + $get('height') + $get('width')) * $get('count');

        if ($get('control_handle_count') !== null) {

            return (int)$temp + (int)$get('control_handle_count') * 1000;
        }

        return $temp;
    }

    //https://filamentphp.com/docs/3.x/forms/advanced#field-updates

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make('Рулонные шторы')
                            ->schema([

                                //тут тип штор (поиск по бд)
                                Section::make()
                                    ->schema(\App\Forms\RolledСurtains\Form::getForm()),

                                Section::make()
                                    ->schema(fn(Get $get): array => \App\Forms\RolledСurtains\Form::all($get))
                            ]),

                        Tab::make('Вертикальные жалюзи')
                            ->schema([

                                //Vertical blinds
                                Section::make()
                                    ->schema([
                                        Select::make('type_vertical_blinds')
                                            ->label('Тип')
                                            ->options(
                                               []
                                            )
                                            ->reactive(),
                                    ]),

                                Section::make()
                                    ->schema(fn(Get $get): array => \App\Forms\VerticalBlinds\Form::all($get))


                            ]),
                        Tab::make('Горизонтальные жалюзи')
                            ->schema([
                                //Horizontal blinds

                                Section::make()
                                    ->schema([
                                        Section::make()
                                            ->schema(\App\Forms\HorizontalBlinds\Form::getForm()),
                                    ]),

                                Section::make()
                                    ->schema(fn(Get $get): array => \App\Forms\HorizontalBlinds\Form::all($get))

                            ]),
                        Tab::make('Шторы')
                            ->schema([
                                // ...
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),
                Group::make()
                    ->schema([
                        Section::make('Стоимость')
                            ->schema([
                                TextInput::make('my_price')
                                    ->label('Моя цена')
                                    ->disabled(),
//                                           ->content(fn (?User $record): string => $record ? $record->created_at->diffForHumans() : '-'),

                                TextInput::make('price')
                                    ->label('Розничная цена')
                                    ->disabled(),
                                Textarea::make('price_breakdown')
                                    ->label('Разбивка')
                                    ->rows(10)
                                    ->disabled(),
//                                           ->dehydrated()
//                                           ->content(fn (?User $record): string => $record ? $record->created_at->diffForHumans() : '-'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),

//                MarkdownEditor::make('content'),
                // ...

                /*
                 *    Select::make('status')
            ->options([
                'draft' => 'Draft',
                'reviewing' => 'Reviewing',
                'published' => 'Published',
            ]),
                 */
            ])
            ->columns(['lg' => 3])
            //        ->model($this->post);
            ->statePath('data');
    }

    private function recalculate(): void
    {
        if ($this->isRecalculating) {
            return;
        }

        $this->isRecalculating = true;

        try {
            $state = array_replace_recursive($this->form->getState(), $this->data ?? []);
            $fabricDetails = $this->calculateFabricDetails($state);
            $state['fabric_price_total'] = $fabricDetails['total'];
            $state['fabric_unit_price'] = $fabricDetails['unit_price'];
            $state['fabric_area'] = $fabricDetails['area'];
            $state['fabric_name'] = $fabricDetails['name'];

            $totals = app(CalculatorPricingService::class)->calculateTotals($state);

            $this->data['my_price'] = $totals['cost_total'];
            $this->data['price'] = $totals['retail_total'];
            $this->data['fabric_price_total'] = $fabricDetails['total'];
            $this->data['price_breakdown'] = $this->formatBreakdown($totals['breakdown'] ?? [], $totals['cost_total'], $totals['retail_total']);

            if (!empty($state['type_rolled_curtains']) && str_contains((string) $state['type_rolled_curtains'], 'bnt')) {
                $this->data['rol_bnt_pipe_recommended'] = $this->recommendBntPipe($state);
            } else {
                $this->data['rol_bnt_pipe_recommended'] = null;
            }
        } finally {
            $this->isRecalculating = false;
        }
    }

    private function calculateFabricDetails(array $state): array
    {
        $fabricId = $state['rol_fabric_id'] ?? null;
        $width = (float) ($state['rol_width'] ?? 0);
        $height = (float) ($state['rol_height'] ?? 0);

        if (!$fabricId || $width <= 0 || $height <= 0) {
            return [
                'name' => null,
                'area' => 0,
                'unit_price' => 0,
                'total' => 0,
            ];
        }

        $fabric = Fabric::query()->find($fabricId);
        if (!$fabric) {
            return [
                'name' => null,
                'area' => 0,
                'unit_price' => 0,
                'total' => 0,
            ];
        }

        $area = $width * $height / 1000000; // mm2 -> m2
        $unitPrice = (float) $fabric->price_per_m2;
        return [
            'name' => (string) $fabric->name,
            'area' => round($area, 4),
            'unit_price' => round($unitPrice, 2),
            'total' => round($area * $unitPrice, 2),
        ];
    }

    private function recommendBntPipe(array $state): string
    {
        $fabricId = $state['rol_fabric_id'] ?? null;
        $fabricWeight = 0;

        if ($fabricId) {
            $fabricWeight = (float) Fabric::query()->whereKey($fabricId)->value('weight_factor');
        }

        return app(CalculatorPricingService::class)->recommendBntPipe([
            'bnt_width' => $state['rol_width'] ?? 0,
            'bnt_height' => $state['rol_height'] ?? 0,
            'bnt_fabric_weight' => $fabricWeight,
        ]);
    }

    private function formatBreakdown(array $breakdown, float $costTotal, float $retailTotal): string
    {
        if (empty($breakdown)) {
            return '';
        }

        $lines = [];
        $allZero = true;

        foreach ($breakdown as $line) {
            $qty = (float) ($line['qty'] ?? 0);
            $unitCost = (float) ($line['unit_cost'] ?? 0);
            $unitRetail = (float) ($line['unit_retail'] ?? 0);
            $totalCost = (float) ($line['total_cost'] ?? 0);
            $totalRetail = (float) ($line['total_retail'] ?? 0);
            $label = (string) ($line['label'] ?? '');

            if ($unitCost > 0 || $unitRetail > 0) {
                $allZero = false;
            }

            $lines[] = sprintf(
                "%s | %s x %s = %s",
                $label,
                rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.'),
                number_format($unitRetail, 2, '.', ''),
                number_format($totalRetail, 2, '.', '')
            );
        }

        $lines[] = sprintf("Итого: %s", number_format($retailTotal, 2, '.', ''));

        if ($allZero) {
            $lines[] = "Внимание: цены = 0 (проверьте Компоненты)";
        }

        return implode("\n", $lines);
    }

    //сохраняем форму
    public function saveAction()
    {
        $prefixes = [
            \App\Forms\RolledСurtains\Form::$prefix => \App\Forms\RolledСurtains\Form::$typeName,
            \App\Forms\VerticalBlinds\Form::$prefix => \App\Forms\VerticalBlinds\Form::$typeName,
            \App\Forms\HorizontalBlinds\Form::$prefix => \App\Forms\HorizontalBlinds\Form::$typeName,
        ];

        $dataResult = [];

        Log::info(__METHOD__, [$this->form->getState()]);

        //структурируем данные из формы по типам выбранного

        foreach ($this->form->getState() as $key => $value) {

            foreach ($prefixes as $prefix => $typeName) {

                //ищем по префиксу значение для каждого типа

                if (strripos($key, $prefix) !== false) {

                    //array_push($types[$typeName], $value);
                    $dataResult[$typeName][] = $value;
                }
            }
        };

        if (isset($dataResult[\App\Forms\RolledСurtains\Form::$typeName])) {

            $dataResult[\App\Forms\RolledСurtains\Form::$typeName][] = $this->form->getState()['type_2_rolled_curtains'];
        }

        Log::info(__METHOD__, $dataResult);


//        return
//            Action::make('save')type_2_rolled_curtains
//            ->label('Сохранить')
//            ->action(function (array $arguments): void {
//
//
//            })->size(ActionSize::Small);
    }

    public function mount(): void
    {
        $this->form->fill();
    }
}
