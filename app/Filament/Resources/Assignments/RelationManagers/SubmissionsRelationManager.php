<?php

namespace App\Filament\Resources\Assignments\RelationManagers;

use App\Models\Submission;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'submissions';

    protected static ?string $title = 'Submissions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                    ->rows(4)
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('content')
                    ->label('Submission')
                    ->limit(60)
                    ->tooltip(fn (?Submission $record): ?string => $record?->content)
                    ->wrap(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === Submission::STATUS_GRADED ? 'success' : 'warning'),
                TextColumn::make('score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
