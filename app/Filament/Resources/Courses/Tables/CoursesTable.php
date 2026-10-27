<?php

namespace App\Filament\Resources\Courses\Tables;

use App\Models\Course;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CoursesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.name')
                    ->searchable(),
                TextColumn::make('instructor.name')

                    ->sortable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                ImageColumn::make('image_path'),
                TextColumn::make('price')
                    ->label('List price')
                    ->money()
                    ->sortable(),
                TextColumn::make('discount_amount')
                    ->label('Discount')
                    ->placeholder('-')
                    ->formatStateUsing(fn ($state, Course $record) => $record->discount_type === Course::DISCOUNT_TYPE_PERCENT
                        ? ((string) (float) $state).'%'
                        : '$'.number_format((float) $state, 2)),
                TextColumn::make('selling_price')
                    ->label('Selling price')
                    ->state(fn (Course $record) => $record->effectivePrice())
                    ->money()
                    ->color(fn (Course $record) => $record->isDiscounted() ? 'success' : null),
                TextColumn::make('intro_video_url')
                    ->label('Intro video')
                    ->limit(40)
                    ->copyable()
                    ->url(fn (Course $record) => $record->intro_video_url, shouldOpenInNewTab: true)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('published_at')
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
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
