<?php

namespace App\Filament\Resources\Instructors\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;

class InstructorInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profile')
                    ->components([
                        ImageEntry::make('profile_image')
                            ->label('')
                            ->circular()
                            ->grow(false)
                            ->columnSpan(1),
                        TextEntry::make('user.name')
                            ->label('Name')
                            ->size(TextSize::Large)
                            ->weight(FontWeight::Bold),
                        TextEntry::make('user.email')
                            ->label('Email')
                            ->copyable()
                            ->icon('heroicon-o-envelope'),
                    ])
                    ->columns(3)
                    ->grow(false),

                Section::make('Details')
                    ->components([
                        TextEntry::make('designation')
                            ->placeholder('-'),
                        TextEntry::make('qualification')
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                Section::make('Bio')
                    ->components([
                        TextEntry::make('bio')
                            ->placeholder('-')
                            ->prose()
                            ->columnSpanFull(),
                    ]),

                Section::make('Timestamps')
                    ->components([
                        TextEntry::make('created_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}
