<?php

namespace App\Filament\Pages;

use App\Models\Course;
use App\Models\HeroSection;
use App\Models\SiteSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageSiteSettings extends Page
{
    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Website Settings';

    protected static ?string $title = 'Website Settings';

    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.pages.manage-site-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * Only administrators may manage global website settings.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        // Fill form with combined attributes from both SiteSetting and HeroSection
        $this->form->fill(array_merge(
            $this->getRecord()->attributesToArray(),
            $this->getHeroRecord()->attributesToArray()
        ));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Tabs::make('Website settings')
                        ->schema([
                            Tab::make('General')
                                ->icon(Heroicon::OutlinedGlobeAlt)
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('site_name')
                                                ->required()
                                                ->maxLength(255),
                                            TextInput::make('tagline')
                                                ->maxLength(255),
                                        ]),
                                    Textarea::make('site_description')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                    Textarea::make('footer_text')
                                        ->rows(2)
                                        ->columnSpanFull()
                                        ->helperText('Shown as the footer copyright line. Leave blank to use the site name and current year.'),
                                ]),
                            Tab::make('Branding')
                                ->icon(Heroicon::OutlinedPaintBrush)
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            FileUpload::make('logo')
                                                ->image()
                                                ->directory('branding')
                                                ->disk('public')
                                                ->visibility('public')
                                                ->helperText('Recommended: a wide PNG or SVG with a transparent background.'),
                                            FileUpload::make('favicon')
                                                ->acceptedFileTypes([
                                                    'image/x-icon',
                                                    'image/vnd.microsoft.icon',
                                                    'image/png',
                                                    'image/svg+xml',
                                                ])
                                                ->directory('branding')
                                                ->disk('public')
                                                ->visibility('public')
                                                ->helperText('Use a square ICO, PNG, or SVG.'),
                                        ]),
                                ]),
                            Tab::make('Hero Section')
                                ->icon('heroicon-o-paint-brush')
                                ->schema([
                                    // 1. Badge Configuration
                                    Section::make('Badge & Announcement')
                                        ->description('Manage the top announcement badge above the main heading.')
                                        ->schema([
                                            Grid::make(2)
                                                ->schema([
                                                    TextInput::make('badge_text')
                                                        ->label('Badge Text')
                                                        ->placeholder('e.g., New: 40+ courses added this month')
                                                        ->maxLength(255),

                                                    Select::make('badge_status_color')
                                                        ->label('Dot Indicator Color')
                                                        ->options([
                                                            'emerald-400' => 'Emerald Green',
                                                            'brand-400' => 'Brand Primary',
                                                            'sky-400' => 'Sky Blue',
                                                            'amber-400' => 'Amber Yellow',
                                                            'rose-400' => 'Rose Red',
                                                        ])
                                                        ->default('emerald-400'),
                                                ]),

                                            Toggle::make('badge_is_active')
                                                ->label('Show Badge')
                                                ->default(true),
                                        ])->collapsible(),

                                    // 2. Main Title & Typography
                                    Section::make('Hero Headline & Description')
                                        ->description('Configure the main value proposition headings.')
                                        ->schema([
                                            Grid::make(3)
                                                ->schema([
                                                    TextInput::make('title_heading')
                                                        ->label('Title Prefix')
                                                        ->placeholder('e.g., Learn skills that'),

                                                    TextInput::make('title_highlight')
                                                        ->label('Gradient Highlight Text')
                                                        ->placeholder('e.g., actually matter')
                                                        ->helperText('This text will have the multi-color gradient style applied.'),

                                                    TextInput::make('title_subheading')
                                                        ->label('Title Suffix')
                                                        ->placeholder('e.g., to your career.'),
                                                ]),

                                            Textarea::make('description')
                                                ->label('Hero Description')
                                                ->rows(3)
                                                ->placeholder('Expert-led courses, hands-on projects and real certificates...'),
                                        ])->collapsible(),

                                    // 4. Social Proof & Statistics
                                    Section::make('Social Proof & Metrics')
                                        ->schema([
                                            Grid::make(4)
                                                ->schema([
                                                    TextInput::make('learners_count')
                                                        ->label('Learners Count (Numeric)')
                                                        ->numeric()
                                                        ->default(42000)
                                                        ->required(),

                                                    TextInput::make('learners_text')
                                                        ->label('Learners Label')
                                                        ->default('learners')
                                                        ->required(),

                                                    TextInput::make('rating_score')
                                                        ->label('Average Rating Score')
                                                        ->numeric()
                                                        ->step(0.1)
                                                        ->default(4.8)
                                                        ->required(),

                                                    TextInput::make('rating_text')
                                                        ->label('Rating Label')
                                                        ->default('average course rating')
                                                        ->required(),
                                                ]),
                                        ])->collapsible(),

                                    // 5. Featured Preview Mockup (Right Column)
                                    Section::make('Right Preview Card & Mockup')
                                        ->description('Configure the featured course preview card displayed on the right side of the hero.')
                                        ->schema([
                                            Grid::make(2)
                                                ->schema([
                                                    Select::make('featured_course_id')
                                                        ->label('Featured Course')
                                                        ->options(Course::pluck('title', 'id'))
                                                        ->searchable()
                                                        ->preload()
                                                        ->placeholder('Select a course to showcase'),

                                                    TextInput::make('mockup_thumb_seed')
                                                        ->label('Thumbnail Seed / Slug')
                                                        ->placeholder('e.g., figma-masterclass')
                                                        ->helperText('Used for the graphic thumbnail component fallback.'),
                                                ]),

                                            TextInput::make('mockup_next_lesson_title')
                                                ->label('"Up Next" Lesson Title')
                                                ->placeholder('e.g., Building color tokens that scale'),

                                            Grid::make(2)
                                                ->schema([
                                                    TextInput::make('streak_title')
                                                        ->label('Streak Badge Title')
                                                        ->default('12-day streak'),

                                                    TextInput::make('streak_subtitle')
                                                        ->label('Streak Badge Subtitle')
                                                        ->default('Keep it going!'),

                                                    TextInput::make('badge_earned_title')
                                                        ->label('Certificate Card Title')
                                                        ->default('Certificate earned'),

                                                    TextInput::make('badge_earned_subtitle')
                                                        ->label('Certificate Card Subtitle')
                                                        ->default('UX Foundations'),
                                                ]),
                                        ])->collapsible(),
                                ]),
                            Tab::make('Contact')
                                ->icon(Heroicon::OutlinedEnvelope)
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('contact_email')
                                                ->email()
                                                ->maxLength(255),
                                            TextInput::make('contact_phone')
                                                ->tel()
                                                ->maxLength(50),
                                        ]),
                                    Textarea::make('contact_address')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ]),
                            Tab::make('Social')
                                ->icon(Heroicon::OutlinedShare)
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('facebook_url')
                                                ->url()
                                                ->maxLength(255),
                                            TextInput::make('twitter_url')
                                                ->url()
                                                ->maxLength(255),
                                            TextInput::make('instagram_url')
                                                ->url()
                                                ->maxLength(255),
                                            TextInput::make('linkedin_url')
                                                ->url()
                                                ->maxLength(255),
                                            TextInput::make('youtube_url')
                                                ->url()
                                                ->maxLength(255),
                                        ]),
                                ]),
                            Tab::make('SEO')
                                ->icon(Heroicon::OutlinedMagnifyingGlass)
                                ->schema([
                                    TextInput::make('meta_title')
                                        ->maxLength(70)
                                        ->helperText('Defaults to the site name when left blank.'),
                                    Textarea::make('meta_description')
                                        ->rows(3)
                                        ->maxLength(160)
                                        ->helperText('Keep it under 160 characters for search results.'),
                                    FileUpload::make('meta_image')
                                        ->image()
                                        ->directory('branding')
                                        ->disk('public')
                                        ->visibility('public')
                                        ->helperText('Used for social sharing previews.'),
                                ]),
                        ])
                        ->columnSpanFull(),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ])
            ->record($this->getRecord())
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        // Separate fields destined for SiteSetting vs HeroSection
        $heroFields = [
            'badge_text',
            'badge_status_color',
            'badge_is_active',
            'title_heading',
            'title_highlight',
            'title_subheading',
            'description',
            'learners_count',
            'learners_text',
            'rating_score',
            'rating_text',
            'featured_course_id',
            'mockup_thumb_seed',
            'mockup_next_lesson_title',
            'streak_title',
            'streak_subtitle',
            'badge_earned_title',
            'badge_earned_subtitle',
        ];

        $siteSettingData = collect($state)->except($heroFields)->toArray();
        $heroSectionData = collect($state)->only($heroFields)->toArray();

        // Update Site Setting
        $this->getRecord()
            ->fill($siteSettingData)
            ->save();

        // Update Hero Section
        $this->getHeroRecord()
            ->fill($heroSectionData)
            ->save();

        Notification::make()
            ->success()
            ->title('Website settings saved')
            ->send();
    }

    public function getRecord(): SiteSetting
    {
        return SiteSetting::current();
    }

    public function getHeroRecord(): HeroSection
    {
        $hero = HeroSection::first();

        if ($hero !== null) {
            return $hero;
        }

        return HeroSection::create([
            'title_heading' => 'Learn skills that',
            'title_highlight' => 'actually matter',
            'title_subheading' => 'to your career.',
        ])->refresh();
    }
}
