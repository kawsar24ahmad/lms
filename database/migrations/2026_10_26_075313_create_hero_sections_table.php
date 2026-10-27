<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hero_sections', function (Blueprint $table) {
            $table->id();

            // Announcement / Badge
            $table->string('badge_text')->nullable(); // e.g., "New: 40+ courses added this month"
            $table->string('badge_status_color')->default('emerald-400'); // e.g., "emerald-400"
            $table->boolean('badge_is_active')->default(true);

            // Hero Main Content
            $table->string('title_heading'); // e.g., "Learn skills that"
            $table->string('title_highlight'); // e.g., "actually matter"
            $table->string('title_subheading'); // e.g., "to your career."
            $table->text('description')->nullable();

            // Search Input Settings
            $table->string('search_placeholder')->default('What do you want to learn?');
            $table->string('search_button_text')->default('Search');
            $table->string('search_route_name')->default('courses.index');

            // Social Proof / Stats
            $table->unsignedInteger('learners_count')->default(42000); // e.g., 42000
            $table->string('learners_text')->default('learners'); // e.g., "learners"
            $table->decimal('rating_score', 2, 1)->default(4.8); // e.g., 4.8
            $table->string('rating_text')->default('average course rating');

            // Featured Preview Mockup (Right Column)
            $table->foreignId('featured_course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->string('mockup_thumb_seed')->nullable(); // e.g., "figma-masterclass"
            $table->string('mockup_next_lesson_title')->nullable(); // e.g., "Building color tokens that scale"

            // Floating Cards Info
            $table->string('streak_title')->default('12-day streak');
            $table->string('streak_subtitle')->default('Keep it going!');
            $table->string('badge_earned_title')->default('Certificate earned');
            $table->string('badge_earned_subtitle')->default('UX Foundations');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hero_sections');
    }
};
