<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Filament\Widgets\Concerns\ScopesDashboardMetrics;
use App\Models\Enrollment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentEnrollmentsTable extends TableWidget
{
    use ScopesDashboardMetrics;

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent enrollments')
            ->description('The newest learners added to the courses you manage.')
            ->query($this->scopedEnrollments()->with(['student', 'course']))
            ->columns([
                TextColumn::make('student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('course.title')
                    ->label('Course')
                    ->searchable()
                    ->limit(26),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'active' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Enrolled')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5])
            ->defaultPaginationPageOption(5)
            ->recordUrl(fn (Enrollment $record): string => EnrollmentResource::getUrl('edit', ['record' => $record]));
    }
}
