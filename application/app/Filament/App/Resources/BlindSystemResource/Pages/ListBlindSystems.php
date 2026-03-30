<?php

namespace App\Filament\App\Resources\BlindSystemResource\Pages;

use App\Filament\App\Resources\BlindSystemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBlindSystems extends ListRecords
{
    protected static string $resource = BlindSystemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
