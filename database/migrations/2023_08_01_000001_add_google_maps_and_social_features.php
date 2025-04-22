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
        // Add Google Maps columns to listings table only if they don't exist
        Schema::table('listings', function (Blueprint $table) {
            // Skip latitude if it exists
            if (!Schema::hasColumn('listings', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('city');
            }
            
            // Skip longitude if it exists
            if (!Schema::hasColumn('listings', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
            
            // Skip map_link if it exists
            if (!Schema::hasColumn('listings', 'map_link')) {
                $table->text('map_link')->nullable()->after('longitude');
            }
        });

        // Add social login columns to users table
        Schema::table('users', function (Blueprint $table) {
            // Check before adding each column
            if (!Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id')->nullable()->after('password');
            }
            if (!Schema::hasColumn('users', 'facebook_id')) {
                $table->string('facebook_id')->nullable()->after('google_id');
            }
            if (!Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('profile_image');
            }
        });

        // Add comment filtering features
        Schema::table('comments', function (Blueprint $table) {
            // Check before adding each column
            if (!Schema::hasColumn('comments', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false)->after('is_approved');
            }
            if (!Schema::hasColumn('comments', 'helpful_count')) {
                $table->integer('helpful_count')->default(0)->after('is_pinned');
            }
            if (!Schema::hasColumn('comments', 'unhelpful_count')) {
                $table->integer('unhelpful_count')->default(0)->after('helpful_count');
            }
            if (!Schema::hasColumn('comments', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('unhelpful_count');
            }
            if (!Schema::hasColumn('comments', 'status')) {
                $table->string('status')->default('active')->after('is_featured');
            }
        });

        // Create comment votes table if it doesn't exist
        if (!Schema::hasTable('comment_votes')) {
            Schema::create('comment_votes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('comment_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->boolean('is_helpful');
                $table->timestamps();
                
                $table->unique(['comment_id', 'user_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment_votes');
        
        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn([
                'is_pinned',
                'helpful_count',
                'unhelpful_count',
                'is_featured',
                'status'
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'google_id',
                'facebook_id',
                'avatar'
            ]);
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn([
                'latitude',
                'longitude',
                'map_link'
            ]);
        });
    }
}; 