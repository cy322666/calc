<?php

namespace App\Filament\App\Resources\FabricResource\Pages;

use App\Filament\App\Resources\FabricResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFabric extends CreateRecord
{
    protected static string $resource = FabricResource::class;

    public function getTitle(): string
    {
        return 'Добавить ткань';
    }

    public function getSubheading(): ?string
    {
        return 'Заполните вес и цену — они участвуют в автоматическом расчете стоимости.';
    }
}
