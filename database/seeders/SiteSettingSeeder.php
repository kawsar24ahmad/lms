<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    /**
     * Create the singleton settings row without overwriting existing values.
     */
    public function run(): void
    {
        SiteSetting::query()->firstOrCreate([], [
            'site_name' => config('app.name', 'EduLearn LMS'),
        ]);
    }
}
