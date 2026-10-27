<?php

namespace App\Filament\Resources\LessonProgress\Pages;

use App\Filament\Resources\LessonProgress\LessonProgressResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLessonProgress extends EditRecord
{
    protected static string $resource = LessonProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
