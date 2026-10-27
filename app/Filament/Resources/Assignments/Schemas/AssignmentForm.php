<?php

namespace App\Filament\Resources\Assignments\Schemas;

use App\Models\Course;
use App\Models\Lesson;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class AssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('course_id')
                    ->label('Course')
                    ->options(fn (): array => self::courseOptions())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->dehydrated(false)
                    ->afterStateUpdated(fn (Set $set) => $set('lesson_id', null))
                    ->helperText('Select the course first, then choose one of its lessons.'),

                Select::make('lesson_id')
                    ->label('Lesson')
                    ->options(fn (Get $get): array => self::lessonOptions($get('course_id')))
                    ->searchable()
                    ->required()
                    ->disabled(fn (Get $get): bool => blank($get('course_id')))
                    ->helperText(fn (Get $get): ?string => blank($get('course_id'))
                        ? 'Select a course to load its lessons.'
                        : null),

                TextInput::make('title')
                    ->required()
                    ->maxLength(255),

                Textarea::make('description')
                    ->default(null)
                    ->rows(5)
                    ->columnSpanFull(),

                TextInput::make('max_score')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(100),

                DateTimePicker::make('due_date')
                    ->label('Due date')
                    ->minDate(fn (string $operation) => $operation === 'create' ? now() : null),
            ]);
    }

    /**
     * The courses the current user may attach an assignment to.
     *
     * @return array<int, string>
     */
    private static function courseOptions(): array
    {
        $user = auth()->user();

        if ($user === null) {
            return [];
        }

        return Course::query()
            ->when(! $user->isAdmin(), fn (Builder $query) => $query->where('user_id', $user->getKey()))
            ->orderBy('title')
            ->pluck('title', 'id')
            ->all();
    }

    /**
     * The lessons of the selected course, grouped by section.
     *
     * @return array<string, array<int, string>>
     */
    private static function lessonOptions(mixed $courseId): array
    {
        if (blank($courseId)) {
            return [];
        }

        return Lesson::query()
            ->whereHas('section', fn (Builder $query) => $query->where('course_id', $courseId))
            ->with('section')
            ->get()
            ->sortBy(fn (Lesson $lesson): string => sprintf(
                '%05d-%05d',
                $lesson->section?->position ?? 0,
                $lesson->position,
            ))
            ->groupBy(fn (Lesson $lesson): string => $lesson->section?->title ?? 'Lessons')
            ->map(fn ($lessons): array => $lessons->pluck('title', 'id')->all())
            ->all();
    }
}
