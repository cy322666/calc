<?php

namespace App\Filament\App\Resources\FabricResource\Pages;

use App\Filament\App\Resources\FabricResource;
use Filament\Resources\Pages\EditRecord;

class EditFabric extends EditRecord
{
    protected static string $resource = FabricResource::class;

    public function getTitle(): string
    {
        return 'Редактировать ткань';
    }

    public function getSubheading(): ?string
    {
        return 'Вес (kg/m2) влияет на расчет трубы, цена — на итоговую стоимость.';
    }
}
