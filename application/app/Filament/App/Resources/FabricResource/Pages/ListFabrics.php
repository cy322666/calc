<?php

namespace App\Filament\App\Resources\FabricResource\Pages;

use App\Filament\App\Resources\FabricResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFabrics extends ListRecords
{
    protected static string $resource = FabricResource::class;

    public function getTitle(): string
    {
        return 'Ткани';
    }

    public function getSubheading(): ?string
    {
        return 'Справочник тканей: вес (kg/m2) используется для выбора трубы, цена за м2 — для расчета стоимости.';
    }

    public function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
