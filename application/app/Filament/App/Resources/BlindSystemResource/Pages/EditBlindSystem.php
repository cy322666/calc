<?php

namespace App\Filament\App\Resources\BlindSystemResource\Pages;

use App\Filament\App\Resources\BlindSystemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBlindSystem extends EditRecord
{
    protected static string $resource = BlindSystemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
