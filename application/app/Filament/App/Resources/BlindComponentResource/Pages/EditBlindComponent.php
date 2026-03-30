<?php

namespace App\Filament\App\Resources\BlindComponentResource\Pages;

use App\Filament\App\Resources\BlindComponentResource;
use Filament\Resources\Pages\EditRecord;

class EditBlindComponent extends EditRecord
{
    protected static string $resource = BlindComponentResource::class;

    public function getTitle(): string
    {
        return 'Редактировать компонент';
    }

    public function getSubheading(): ?string
    {
        return 'Изменение цены здесь влияет на все системы, где используется компонент.';
    }
}
