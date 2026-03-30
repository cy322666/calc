<?php

namespace App\Filament\Pages;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\Facades\Log;

class Calculators extends Page implements HasForms
{
    use InteractsWithForms;
        //, InteractsWithRecord;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.calculator';

    protected static ?string $title = 'Калькулятор';

//    public function mount(int | string $record): void
//    {
//        $this->record = $this->resolveRecord($record);
//    }

    public function create(): void
    {
        dd($this->form->getState());
    }

    public function mount(): void
    {
        $this->form->fill();
    }
}
