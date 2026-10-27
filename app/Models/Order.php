<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable(['student_id', 'number', 'total_amount', 'discount_amount', 'final_amount', 'paid_amount', 'payment_status', 'status'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * The number identifies the order in the admin panel and references.
     */
    public function getRouteKeyName(): string
    {
        return 'number';
    }

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'final_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    /**
     * The student who placed the order.
     *
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * The priced course line items of the order.
     *
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * The payment records applied to the order.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * The enrollments granted by the order items.
     *
     * @return HasManyThrough<Enrollment, OrderItem, $this>
     */
    public function enrollments(): HasManyThrough
    {
        return $this->hasManyThrough(
            Enrollment::class,
            OrderItem::class,
            'order_id',
            'order_item_id',
        );
    }

    /**
     * The amount still owed on the order: never negative.
     */
    public function dueAmount(): float
    {
        return round(max(0.0, (float) $this->final_amount - (float) $this->paid_amount), 2);
    }

    /**
     * Whether every line item of the order has an enrollment.
     */
    public function isFullyEnrolled(): bool
    {
        return $this->items()
            ->whereDoesntHave('enrollment')
            ->doesntExist();
    }

    /**
     * Limit the query to orders awaiting payment.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }
}
