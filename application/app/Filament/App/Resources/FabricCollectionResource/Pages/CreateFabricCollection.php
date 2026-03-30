<?php

namespace App\Filament\App\Resources\FabricCollectionResource\Pages;

use App\Filament\App\Resources\FabricCollectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFabricCollection extends CreateRecord
{
    protected static string $resource = FabricCollectionResource::class;

    public function getTitle(): string
    {
        return 'Создать коллекцию тканей';
    }

    public function getSubheading(): ?string
    {
        return 'Коллекция нужна для группировки тканей и фильтрации по типу (standard/zebra).';
    }
}
