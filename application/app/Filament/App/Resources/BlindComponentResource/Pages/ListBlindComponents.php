<?php

namespace App\Filament\App\Resources\BlindComponentResource\Pages;

use App\Filament\App\Resources\BlindComponentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBlindComponents extends ListRecords
{
    protected static string $resource = BlindComponentResource::class;

    public function getTitle(): string
    {
        return 'Компоненты';
    }

    public function getSubheading(): ?string
    {
        return 'Единый справочник компонентов. Здесь удобно обновлять актуальные цены для всех систем сразу.';
    }

    public function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Добавить компонент'),
        ];
    }
}
