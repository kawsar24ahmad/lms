<?php

namespace App\Filament\Resources\Quizzes\Schemas;

use App\Policies\OwnedResourcePolicy;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class QuizForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('course_id')
                    ->relationship(
                        'course',
                        'title',
                        modifyQueryUsing: fn (Builder $query) => OwnedResourcePolicy::scopeToOwner($query, auth()->user()),
                    )
                    ->required(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('pass_score')
                    ->required()
                    ->numeric()
                    ->default(60),
                Toggle::make('is_active')
                    ->required(),

                Section::make('Questions')
                    ->description('Add the quiz questions, their types, and answers here. Everything is saved together with the quiz.')
                    ->schema([
                        Repeater::make('questions')
                            ->relationship()
                            ->label('Questions')
                            ->schema([
                                Textarea::make('question_text')
                                    ->label('Question')
                                    ->required()
                                    ->columnSpanFull(),
                                Select::make('type')
                                    ->options([
                                        'multiple_choice' => 'Multiple choice',
                                        'true_false' => 'True / false',
                                    ])
                                    ->required()
                                    ->default('multiple_choice')
                                    ->live(),
                                TextInput::make('points')
                                    ->required()
                                    ->numeric()
                                    ->default(1),
                                Select::make('correct_answer')
                                    ->label('Correct answer')
                                    ->options([
                                        'true' => 'True',
                                        'false' => 'False',
                                    ])
                                    ->visible(fn (Get $get): bool => $get('type') === 'true_false')
                                    ->required(fn (Get $get): bool => $get('type') === 'true_false')
                                    ->columnSpanFull(),
                                Repeater::make('options')
                                    ->relationship()
                                    ->label('Answer options')
                                    ->helperText('Tick the correct answer for each question.')
                                    ->visible(fn (Get $get): bool => $get('type') === 'multiple_choice')
                                    ->required(fn (Get $get): bool => $get('type') === 'multiple_choice')
                                    ->schema([
                                        Textarea::make('option_text')
                                            ->label('Option')
                                            ->required()
                                            ->columnSpanFull(),
                                        Toggle::make('is_correct')
                                            ->label('Correct answer'),
                                    ])
                                    ->columns(1)
                                    ->defaultItems(2)
                                    ->addActionLabel('Add option')
                                    ->itemLabel(fn (array $state): ?string => $state['option_text'] ?? 'New option')
                                    ->collapsible()
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel('Add question')
                            ->itemLabel(fn (array $state): ?string => $state['question_text'] ?? 'New question')
                            ->collapsible()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
