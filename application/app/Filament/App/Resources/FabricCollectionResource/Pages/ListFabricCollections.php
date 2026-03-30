<?php

namespace App\Filament\App\Resources\FabricCollectionResource\Pages;

use App\Filament\App\Resources\FabricCollectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFabricCollections extends ListRecords
{
    protected static string $resource = FabricCollectionResource::class;

    public function getTitle(): string
    {
        return 'Коллекции тканей';
    }

    public function getSubheading(): ?string
    {
        return 'Справочник коллекций: задает тип (standard/zebra) и базовый вес. Используется для фильтрации тканей в калькуляторе.';
    }

    public function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
