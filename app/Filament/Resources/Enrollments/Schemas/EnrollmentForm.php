<?php

namespace App\Filament\Resources\Enrollments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EnrollmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('course_id')
                    ->relationship('course', 'title')
                    ->required(),
                Select::make('student_id')
                    ->label('Student')
                    ->relationship(
                        name: 'student',
                        titleAttribute: 'name',
                        // modifyQueryUsing: fn ($query) => $query
                        //     ->whereDoesntHave(
                        //         'roles',
                        //         fn ($q) => $q
                        //             ->whereIn('name', ['super_admin', 'instructor'])
                        //     )
                    )
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
                            ->label(fn (string $operation) => $operation === 'create' ? 'Password' : 'New password')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->required(fn (string $operation) => $operation === 'create')
                            ->helperText('Leave blank on edit to keep the current password.'),
                    ]),
                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'completed' => 'Completed',
                    ])
                    ->required()
                    ->default('active'),
                DateTimePicker::make('enrolled_at')
                    ->helperText('Defaults to now when left empty.'),
                DateTimePicker::make('completed_at'),
            ]);
    }
}
