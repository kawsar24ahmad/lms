<?php

namespace App\Filament\Resources\Courses\Schemas;

use App\Models\Course;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()->schema([
                    Tab::make('Course Details')
                        ->icon('heroicon-o-academic-cap')
                        ->schema([
                            Select::make('category_id')
                                ->relationship('category', 'name')
                                ->required()
                                ->createOptionForm([
                                    TextInput::make('name')
                                        ->label('Category Name')
                                        ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                            $set('slug', Str::slug($state));
                                        })
                                        ->maxLength(255),
                                    TextInput::make('slug')
                                        ->label('slug')
                                        ->required()
                                        ->unique(table: 'categories', column: 'slug'),
                                ]),

                            Select::make('user_id')
                                ->label('Instructor')
                                ->relationship(
                                    name: 'instructor',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: fn($query) => $query
                                        ->whereHas('roles', fn($role) => $role->where('name', User::ROLE_INSTRUCTOR)),
                                )
                                ->searchable()
                                ->preload()
                                ->required()
                                ->createOptionForm([
                                    Section::make('Instructor Profile')
                                        ->schema([
                                            TextInput::make('name')
                                                ->label('Instructor Name')
                                                ->required()
                                                ->maxLength(255),

                                            TextInput::make('email')
                                                ->email()
                                                ->required()
                                                ->unique(table: 'users', column: 'email'),

                                            TextInput::make('password')
                                                ->password()
                                                ->required()
                                                ->minLength(8)
                                                ->dehydrated(),

                                            TextInput::make('designation')
                                                ->label('Designation')
                                                ->required()
                                                ->maxLength(255),

                                            TextInput::make('qualification')
                                                ->label('Qualification')
                                                ->maxLength(255),

                                            FileUpload::make('profile_image')
                                                ->image()
                                                ->directory('instructors')
                                                ->imageEditor(),

                                            Textarea::make('bio')
                                                ->label('Biography')
                                                ->rows(4)
                                                ->columnSpanFull(),
                                        ])
                                        ->columns(2),
                                ])
                                ->createOptionUsing(function (array $data): int {
                                    return DB::transaction(
                                        function () use ($data): int {
                                            $user = User::create([
                                                'name' => $data['name'],
                                                'email' => $data['email'],
                                                'password' => Hash::make(
                                                    $data['password']
                                                ),
                                            ]);
                                            $user->assignRole(User::ROLE_INSTRUCTOR);

                                            $user->instructor()->create([
                                                'designation' => $data['designation'],
                                                'qualification' => $data['qualification'] ?? null,
                                                'profile_image' => $data['profile_image'] ?? null,
                                                'bio' => $data['bio'] ?? null,
                                            ]);

                                            return $user->getKey();
                                        }
                                    );
                                }),
                            TextInput::make('title')
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                    if ($operation == 'create') {
                                        $set('slug', Str::slug($state));
                                    }
                                }),
                            TextInput::make('slug')
                                ->required(),
                            Textarea::make('description')
                                ->default(null)
                                ->columnSpanFull(),
                            FileUpload::make('image_path')
                                ->image()
                                ->directory('courses')
                                ->disk('public')
                                ->visibility('public'),
                            TextInput::make('price')
                                ->required()
                                ->numeric()
                                ->default(0.0)
                                ->prefix('$')
                                ->live(onBlur: true)
                                ->minValue(0),
                            Select::make('discount_type')
                                ->label('Discount type')
                                ->options([
                                    Course::DISCOUNT_TYPE_FIXED => 'Fixed amount',
                                    Course::DISCOUNT_TYPE_PERCENT => 'Percentage',
                                ])
                                ->default(null)
                                ->live()
                                ->placeholder('No discount')
                                ->helperText('Optional: reduce the selling price.'),
                            TextInput::make('discount_amount')
                                ->label('Discount')
                                ->numeric()
                                ->default(null)
                                ->nullable()
                                ->live(onBlur: true)
                                ->minValue(0)
                                ->prefix(fn($get) => $get('discount_type') === Course::DISCOUNT_TYPE_PERCENT ? null : '$')
                                ->suffix(fn($get) => $get('discount_type') === Course::DISCOUNT_TYPE_PERCENT ? '%' : null)
                                ->maxValue(fn($get) => $get('discount_type') === Course::DISCOUNT_TYPE_PERCENT ? 100 : 99999999)
                                ->visible(fn($get) => filled($get('discount_type')))
                                ->required(fn($get) => filled($get('discount_type'))),
                            Placeholder::make('selling_price')
                                ->label('Selling price')
                                ->content(function ($get): string {
                                    $price = (float) $get('price');
                                    $discount = (float) ($get('discount_amount') ?? 0);

                                    $effective = match ($get('discount_type')) {
                                        Course::DISCOUNT_TYPE_PERCENT => $price * (1 - ($discount / 100)),
                                        Course::DISCOUNT_TYPE_FIXED => $price - $discount,
                                        default => $price,
                                    };

                                    return '$' . number_format(max(0, $effective), 2);
                                }),
                            TextInput::make('intro_video_url')
                                ->label('Intro video URL')
                                ->url()
                                ->default(null)
                                ->placeholder('https://www.youtube.com/watch?v=…')
                                ->helperText('YouTube, Vimeo or a direct video file link.'),
                            Select::make('status')
                                ->options([
                                    'draft' => 'Draft',
                                    'published' => 'Published',
                                ])
                                ->required()
                                ->default('draft'),
                            DateTimePicker::make('published_at'),
                        ])
                        ->columns(2),
                    Tab::make('Sections & Lessons')
                        ->icon('heroicon-o-queue-list')
                        ->schema([
                            Repeater::make('sections')
                                ->relationship()
                                ->label('Course Curriculum')
                                ->schema([
                                    TextInput::make('title')
                                        ->label('Section Title')
                                        ->placeholder('e.g. Getting Started')
                                        ->required()
                                        ->columnSpan(2),

                                    // Textarea::make('description')
                                    //     ->label('Section Description')
                                    //     ->rows(2)
                                    //     ->columnSpanFull(),

                                    Repeater::make('lessons')
                                        ->relationship()
                                        ->label('Lessons')
                                        ->schema([
                                            Grid::make(2)

                                                ->schema([
                                                    TextInput::make('title')
                                                        ->label('Lesson Title')
                                                        ->placeholder('e.g. Introduction to Laravel')
                                                        ->required(),

                                                    // Select::make('type')
                                                    //     ->label('Lesson Type')
                                                    //     ->options([
                                                    //         'video' => 'Video Lesson',
                                                    //         'text' => 'Text Lesson',
                                                    //         'document' => 'Document',
                                                    //     ])
                                                    //     ->default('video')
                                                    //     ->required()
                                                    //     ->live(),
                                                ]),

                                            TextInput::make('video_url')
                                                ->label('Video URL')
                                                ->placeholder('https://youtube.com/...')
                                                ->url(),

                                            Textarea::make('content')
                                                ->label('Lesson Content')
                                                ->placeholder('Write lesson content here...')
                                                ->rows(5),

                                            // FileUpload::make('document')
                                            //     ->label('Lesson Document')
                                            //     ->disk('public')
                                            //     ->directory('lesson-documents')
                                            //     ->visible(
                                            //         fn($get) => $get('type') === 'document'
                                            //     ),

                                            Grid::make(2)
                                                ->schema([
                                                    TextInput::make('duration_minutes')
                                                        ->label('Duration (minutes)')
                                                        ->numeric()
                                                        ->minValue(0)
                                                        ->default(0)
                                                        ->suffix('min'),

                                                    Toggle::make('is_free')
                                                        ->label('Free Preview')
                                                        ->default(false),


                                                    Repeater::make('resources')
                                                        ->relationship()
                                                        ->schema([
                                                            TextInput::make('title')
                                                                ->required()
                                                                ->maxLength(255),

                                                            FileUpload::make('file_path')
                                                                ->directory('lesson-resources')
                                                                ->required(),

                                                            // Select::make('file_type')
                                                            //     ->options([
                                                            //         'ZIP' => 'ZIP',
                                                            //         'PDF' => 'PDF',
                                                            //         'MD' => 'Markdown',
                                                            //         'CODE' => 'Code Source',
                                                            //     ])
                                                            //     ->required(),

                                                            // TextInput::make('file_size')
                                                            //     ->placeholder('যেমন: 24 KB বা 1.2 MB'),

                                                            TextInput::make('sort_order')
                                                                ->numeric()
                                                                ->default(0),
                                                        ])
                                                        ->columnSpanFull()


                                                ]),
                                        ])
                                        ->defaultItems(0)
                                        ->addActionLabel('Add Lesson')
                                        ->itemLabel(
                                            fn(array $state): ?string => $state['title'] ?? 'New Lesson'
                                        )
                                        ->collapsible()
                                        ->cloneable()
                                        ->reorderable()
                                        ->columnSpanFull(),
                                ])
                                ->defaultItems(0)
                                ->addActionLabel('Add New Section')
                                ->itemLabel(
                                    fn(array $state): ?string => $state['title'] ?? 'New Section'
                                )
                                ->collapsible()
                                ->cloneable()
                                ->reorderable()
                                ->columnSpanFull(),
                        ]),

                ])
                    ->columnSpanFull(),

            ]);
    }
}
