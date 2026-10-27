<?php

namespace App\Filament\Resources\LessonProgress\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LessonProgressForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Student')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('lesson_id')
                    ->relationship('lesson', 'title')
                    ->required(),
                TextInput::make('seconds_watched')
                    ->label('Seconds watched')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('last_position')
                    ->label('Last position (seconds)')
                    ->numeric()
                    ->default(0),
                TextInput::make('furthest_position')
                    ->label('Furthest position (seconds)')
                    ->numeric()
                    ->default(0),
                TextInput::make('duration_seconds')
                    ->label('Video duration (seconds)')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_completed')
                    ->required(),
                DateTimePicker::make('completed_at'),
            ]);
    }
}
