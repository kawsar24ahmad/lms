<?php

namespace App\Models;

use App\Models\Course;
use Illuminate\Database\Eloquent\Model;

class HeroSection extends Model
{
    protected $guarded = [];
    protected $casts = [
        'badge_is_active' => 'boolean',
        'learners_count' => 'integer',
        'rating_score' => 'float',
    ];

    public function featuredCourse()
    {
        return $this->belongsTo(Course::class, 'featured_course_id');
    }

    // Helper for formatted learners count (e.g., 42000 -> 42,000+)
    public function getFormattedLearnersCountAttribute(): string
    {
        return number_format($this->learners_count) . '+';
    }
}
