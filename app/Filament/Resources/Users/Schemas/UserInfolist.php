<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profile')
                    ->components([
                        TextEntry::make('name')
                            ->label(''),
                        TextEntry::make('email')
                            ->copyable()
                            ->icon('heroicon-o-envelope'),
                        TextEntry::make('email_verified_at')
                            ->dateTime()
                            ->label('Email verified')
                            ->placeholder('Not verified'),
                        TextEntry::make('created_at')
                            ->dateTime()
                            ->label('Registered'),
                    ])
                    ->columns(2),

                Section::make('Access')
                    ->components([
                        TextEntry::make('roles.name')
                            ->label('Shield roles')
                            ->badge()
                            ->color('info')
                            ->placeholder('Student'),
                    ]),

            ]);
    }
}
