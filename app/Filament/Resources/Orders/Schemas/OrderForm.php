<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Course;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('student_id')
                    ->label('Student')
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required()
                            ->autofocus()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8),
                    ]),
                CheckboxList::make('courses')
                    ->label('Courses')
                    ->options(fn (): array => Course::query()
                        ->published()
                        ->orderBy('title')
                        ->get()
                        ->mapWithKeys(fn (Course $course): array => [
                            $course->getKey() => sprintf(
                                '%s — $%s%s',
                                $course->title,
                                number_format($course->effectivePrice(), 2),
                                $course->isDiscounted() && (float) $course->price > 0
                                    ? ' (was $'.number_format((float) $course->price, 2).')'
                                    : '',
                            ),
                        ])->all())
                    ->columns(2)
                    ->live()
                    ->helperText('Only published courses are selectable. Prices are computed server-side; unpublished or already-actively-enrolled courses are rejected at checkout.'),
                TextInput::make('order_discount')
                    ->label('Order-level discount')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->prefix('$')
                    ->live(onBlur: true)
                    ->helperText('Applied on top of the per-course discount, capped at the remaining total.'),
                Placeholder::make('summary')
                    ->label('Order summary')
                    ->content(fn ($get): string => self::summariesFor($get('courses'), $get('order_discount'))),
            ]);
    }

    /**
     * A reactive summary of what the server will compute for the current
     * selection: totals are recalculated from course records, never trusted
     * from the form.
     */
    private static function summariesFor(mixed $courses, mixed $orderDiscount): string
    {
        $courseIds = collect((array) $courses)
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($courseIds->isEmpty()) {
            return 'Select at least one course.';
        }

        $selected = Course::query()->whereIn('id', $courseIds)->get();

        $total = (float) $selected->sum(fn (Course $course) => (float) $course->price);
        $final = round((float) $selected->sum(fn (Course $course) => $course->effectivePrice()), 2);
        $discount = round(min(max(0.0, (float) ($orderDiscount ?? 0)), $final), 2);

        return sprintf(
            '%d course(s): subtotal $%.2f, discounts $%.2f, final total $%.2f.',
            $selected->count(),
            $total,
            round($total - $final + $discount, 2),
            round($final - $discount, 2),
        );
    }
}
