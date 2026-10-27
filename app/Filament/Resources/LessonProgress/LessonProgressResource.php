<?php

namespace App\Filament\Resources\LessonProgress;

use App\Filament\Resources\LessonProgress\Pages\CreateLessonProgress;
use App\Filament\Resources\LessonProgress\Pages\EditLessonProgress;
use App\Filament\Resources\LessonProgress\Pages\ListLessonProgress;
use App\Filament\Resources\LessonProgress\Schemas\LessonProgressForm;
use App\Filament\Resources\LessonProgress\Tables\LessonProgressTable;
use App\Models\LessonProgress;
use App\Policies\OwnedResourcePolicy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LessonProgressResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static ?string $model = LessonProgress::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getEloquentQuery(): Builder
    {
        return OwnedResourcePolicy::scopeToOwner(parent::getEloquentQuery(), auth()->user());
    }

    public static function form(Schema $schema): Schema
    {
        return LessonProgressForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LessonProgressTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLessonProgress::route('/'),
            'create' => CreateLessonProgress::route('/create'),
            'edit' => EditLessonProgress::route('/{record}/edit'),
        ];
    }
}
