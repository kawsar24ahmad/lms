<?php

namespace App\Filament\Resources\Submissions\Tables;

use App\Models\Submission;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('assignment.title')
                    ->label('Assignment')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('assignment.lesson.section.course.title')
                    ->label('Course')
                    ->searchable()
                    ->limit(25)
                    ->toggleable(),
                TextColumn::make('student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('content')
                    ->label('Submission')
                    ->limit(50)
                    ->tooltip(fn (?Submission $record): ?string => $record?->content),
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
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        Submission::STATUS_PENDING => 'Pending review',
                        Submission::STATUS_GRADED => 'Graded',
                    ]),
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
