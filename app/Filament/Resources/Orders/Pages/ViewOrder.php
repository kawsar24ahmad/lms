<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        $order = $this->getRecord();

        if (! $order instanceof Order) {
            return [];
        }

        return [
            Action::make('cancel')
                ->label('Cancel order')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn (): bool => $order->status === 'pending')
                ->requiresConfirmation()
                ->action(function (OrderService $orders) use ($order): void {
                    $orders->cancelOrder($order);

                    Notification::make()
                        ->title('Order cancelled')
                        ->body('Course access is not granted for cancelled orders.')
                        ->warning()
                        ->send();
                }),
        ];
    }
}
