<?php

namespace App\Filament\Resources\Assignments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class AssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('lesson.section.course.title')
                    ->label('Course')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                TextColumn::make('lesson.title')
                    ->label('Lesson')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('submissions_count')
                    ->label('Submissions')
                    ->counts('submissions')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('max_score')
                    ->label('Max score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label('Due date')
                    ->dateTime()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state): string => filled($state) && Carbon::parse($state)->isPast() ? 'danger' : 'success')
                    ->placeholder('No due date'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('due_date')
            ->filters([
                //
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
