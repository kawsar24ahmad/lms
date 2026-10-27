<?php

namespace App\Filament\Resources\Submissions\Schemas;

use App\Models\Submission;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SubmissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('assignment_id')
                    ->relationship('assignment', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('user_id')
                    ->label('Student')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabledOn('edit'),
                Select::make('status')
                    ->options([
                        Submission::STATUS_PENDING => 'Pending review',
                        Submission::STATUS_GRADED => 'Graded',
                    ])
                    ->required()
                    ->default(Submission::STATUS_PENDING),
                Textarea::make('content')
                    ->label('Submission (link or note)')
                    ->rows(5)
                    ->columnSpanFull(),
                TextInput::make('score')
                    ->numeric()
                    ->minValue(0),
                Textarea::make('feedback')
                    ->rows(3)
                    ->columnSpanFull(),
                DateTimePicker::make('submitted_at')
                    ->required()
                    ->default(now()),
            ]);
    }
}
