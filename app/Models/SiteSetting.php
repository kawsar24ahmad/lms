<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Database\Factories\SiteSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;

use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable([
    'site_name',
    'tagline',
    'site_description',
    'logo',
    'favicon',
    'contact_email',
    'contact_phone',
    'contact_address',
    'facebook_url',
    'twitter_url',
    'instagram_url',
    'linkedin_url',
    'youtube_url',
    'meta_title',
    'meta_description',
    'meta_image',
    'footer_text',
])]
class SiteSetting extends Model
{
    /** @use HasFactory<SiteSettingFactory> */
    use HasFactory;

    /**
     * The single row that stores the website settings.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'site_name' => config('app.name', 'EduLearn LMS'),
        ]);
    }

    /**
     * The configured name, falling back to the application name.
     */
    public function displayName(): string
    {
        return $this->site_name ?: config('app.name', 'EduLearn LMS');
    }

    /**
     * The configured page title, falling back to the display name.
     */
    public function displayTitle(): string
    {
        return $this->meta_title ?: $this->displayName();
    }

    /**
     * The configured meta description, falling back to the tagline or the
     * short site description.
     */
    public function displayDescription(): ?string
    {
        return $this->meta_description ?: $this->tagline ?: $this->site_description;
    }
}
