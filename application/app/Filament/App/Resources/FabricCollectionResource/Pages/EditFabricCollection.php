<?php

namespace App\Filament\App\Resources\FabricCollectionResource\Pages;

use App\Filament\App\Resources\FabricCollectionResource;
use Filament\Resources\Pages\EditRecord;

class EditFabricCollection extends EditRecord
{
    protected static string $resource = FabricCollectionResource::class;

    public function getTitle(): string
    {
        return 'Редактировать коллекцию тканей';
    }

    public function getSubheading(): ?string
    {
        return 'Тип коллекции влияет на доступность тканей для систем Zebra/Standard.';
    }
}
