<?php

namespace App\Filament\App\Resources\BlindComponentResource\Pages;

use App\Filament\App\Resources\BlindComponentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBlindComponent extends CreateRecord
{
    protected static string $resource = BlindComponentResource::class;

    public function getTitle(): string
    {
        return 'Добавить компонент';
    }

    public function getSubheading(): ?string
    {
        return 'Компонент станет доступен для привязки к системам и централизованного обновления стоимости.';
    }
}
