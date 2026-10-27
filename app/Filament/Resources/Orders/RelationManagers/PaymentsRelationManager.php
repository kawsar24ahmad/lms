<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Models\Payment;
use App\Services\OrderService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Manual payment records for an order. Payments are created as pending;
 * they only affect the order balance and course access once verified here.
 * Duplicate approvals and duplicate transaction references are rejected.
 */
class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('amount')
                    ->label('Amount')
                    ->numeric()
                    ->minValue(0.01)
                    ->prefix('$')
                    ->required(),
                Select::make('payment_method')
                    ->options([
                        'bank_transfer' => 'Bank transfer',
                        'card' => 'Card',
                        'cash' => 'Cash',
                        'other' => 'Other',
                    ])
                    ->nullable(),
                TextInput::make('transaction_id')
                    ->label('Transaction reference')
                    ->maxLength(255)
                    ->unique(table: Payment::class, ignoreRecord: true)
                    ->helperText('Optional; used to reject duplicate payment callbacks.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('transaction_id')
            ->columns([
                TextColumn::make('amount')
                    ->money()
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->placeholder('—'),
                TextColumn::make('transaction_id')
                    ->placeholder('—')
                    ->limit(30),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Payment::STATUS_SUCCEEDED => 'success',
                        Payment::STATUS_FAILED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve payment')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (Payment $record): bool => ! $record->isSucceeded())
                    ->requiresConfirmation()
                    ->action(function (Payment $record, OrderService $orders) {
                        $orders->verifyPayment($record);

                        Notification::make()
                            ->title('Payment verified')
                            ->body('The order balance has been recalculated; eligible enrollments were granted if the order is fully covered.')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Mark failed')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (Payment $record): bool => $record->status === Payment::STATUS_PENDING)
                    ->requiresConfirmation()
                    ->action(function (Payment $record, OrderService $orders) {
                        $orders->rejectPayment($record);

                        Notification::make()
                            ->title('Payment marked as failed')
                            ->warning()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
