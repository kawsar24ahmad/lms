<?php

namespace App\Filament\Resources\Options\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class OptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('question_id')
                    ->relationship('question', 'question_text')
                    ->searchable()
                    ->required(),
                Textarea::make('option_text')
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('is_correct')
                    ->required(),
            ]);
    }
}
