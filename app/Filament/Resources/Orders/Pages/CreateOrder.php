<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\User;
use App\Services\OrderService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    /**
     * Orders are priced by the server: the selection is validated inside
     * the order service inside a database transaction, which also grants
     * enrollments for free orders and attaches the price snapshots.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(OrderService::class)->placeOrder(
            User::query()->whereKey($data['student_id'] ?? null)->firstOrFail(),
            array_values(array_map('intval', (array) ($data['courses'] ?? []))),
            (float) ($data['order_discount'] ?? 0),
        );
    }
}
