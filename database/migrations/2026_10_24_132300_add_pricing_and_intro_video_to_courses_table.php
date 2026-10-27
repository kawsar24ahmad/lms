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
        Schema::table('courses', function (Blueprint $table) {
            $table->string('discount_type')->nullable()->after('price');
            $table->decimal('discount_amount', 10, 2)->nullable()->after('discount_type');
            $table->string('intro_video_url')->nullable()->after('image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_amount', 'intro_video_url']);
        });
    }
};
