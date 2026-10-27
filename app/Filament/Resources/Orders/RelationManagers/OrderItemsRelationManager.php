<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Order line items are snapshots created by the checkout service when the
 * order is placed, so they are read-only here: editing or removing items
 * after the fact would corrupt the stored price history.
 */
class OrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('course.title')
            ->columns([
                TextColumn::make('course.title')
                    ->searchable(),
                TextColumn::make('original_price')
                    ->money()
                    ->sortable(),
                TextColumn::make('discount_amount')
                    ->money()
                    ->sortable(),
                TextColumn::make('final_price')
                    ->money()
                    ->sortable(),
                TextColumn::make('enrollment.status')
                    ->label('Enrollment')
                    ->badge()
                    ->placeholder('Not granted yet'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                //
            ]);
    }
}
