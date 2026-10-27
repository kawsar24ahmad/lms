<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Submissions\SubmissionResource;
use App\Filament\Widgets\Concerns\ScopesDashboardMetrics;
use App\Models\Submission;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PendingSubmissionsTable extends TableWidget
{
    use ScopesDashboardMetrics;

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Pending grading')
            ->description('Submissions waiting for instructor feedback.')
            ->query($this->scopedSubmissions()->pending()->with(['student', 'assignment']))
            ->columns([
                TextColumn::make('student.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('assignment.title')
                    ->label('Assignment')
                    ->searchable()
                    ->limit(26),

                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('submitted_at', 'asc')
            ->paginated([5])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Nothing to grade')
            ->emptyStateDescription('New student submissions will appear here.')
            ->recordUrl(fn (Submission $record): string => SubmissionResource::getUrl('edit', ['record' => $record]))
            ->recordActions([
                Action::make('grade')
                    ->label('Grade')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->url(fn (Submission $record): string => SubmissionResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
