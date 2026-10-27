<?php

namespace App\Filament\Resources\Instructors\Schemas;

use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InstructorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Instructor')
                    ->relationship(
                        name: 'user',
                        titleAttribute: 'name',
                        // Only instructor-role users, same source of truth
                        // User::isInstructor() checks.
                        modifyQueryUsing: fn ($query) => $query
                            ->whereHas('roles', fn ($role) => $role->where('name', User::ROLE_INSTRUCTOR))
                    )
                    ->getOptionLabelFromRecordUsing(
                        fn ($record) => "{$record->name} ({$record->email})"
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(table: User::class, column: 'email'),

                        TextInput::make('password')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->revealable(),
                    ])
                    // The created user is an instructor by definition. Grant
                    // the Shield instructor role so the dropdown keeps
                    // offering them and permissions stay consistent.
                    ->createOptionUsing(function (array $data): int {
                        $user = User::create([
                            'name' => $data['name'],
                            'email' => $data['email'],
                            'password' => $data['password'],
                        ]);
                        $user->assignRole(User::ROLE_INSTRUCTOR);

                        return $user->getKey();
                    }),
                TextInput::make('designation')
                    ->default(null),
                TextInput::make('qualification')
                    ->default(null),
                FileUpload::make('profile_image')
                    ->image(),
                Textarea::make('bio')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
