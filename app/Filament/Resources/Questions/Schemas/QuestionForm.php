<?php

namespace App\Filament\Resources\Questions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('quiz_id')
                    ->relationship('quiz', 'title')
                    ->required(),
                Textarea::make('question_text')
                    ->required()
                    ->columnSpanFull(),
                Select::make('type')
                    ->options([
                        'multiple_choice' => 'Multiple choice',
                        'true_false' => 'True / false',
                        'open_answer' => 'Open answer',
                    ])
                    ->required()
                    ->default('multiple_choice')
                    ->live(),
                TextInput::make('points')
                    ->required()
                    ->numeric()
                    ->default(1),
                Textarea::make('correct_answer')
                    ->label(fn (Get $get): string => $get('type') === 'true_false' ? 'Correct answer (true / false)' : 'Expected answer')
                    ->helperText(fn (Get $get): ?string => $get('type') === 'true_false'
                        ? 'Enter true or false.'
                        : 'The student answer must match this text, ignoring case.')
                    ->visible(fn (Get $get): bool => in_array($get('type'), ['true_false', 'open_answer'], true))
                    ->required(fn (Get $get): bool => in_array($get('type'), ['true_false', 'open_answer'], true))
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }
}
