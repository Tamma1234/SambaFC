<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Samba FC club schema:
     * Google login on users, members, news, matches, attendance, and 7-a-side ratings.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
            $table->string('google_id')->nullable()->unique();
            $table->string('avatar')->nullable();
            $table->string('role')->default('member');
        });

        Schema::create('club_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Samba FC');
            $table->string('logo_path')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->text('description')->nullable();
            $table->string('founded_year', 4)->nullable();
            $table->string('home_venue')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('contact_email')->nullable();
            $table->timestamps();
        });

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('nickname')->nullable();
            $table->unsignedTinyInteger('jersey_number')->nullable();
            $table->string('preferred_position')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('phone')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('joined_at')->nullable();
            $table->string('status')->default('active');
            $table->string('role')->default('player');
            $table->text('bio')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'jersey_number']);
        });

        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('opponent');
            $table->string('venue')->nullable();
            $table->boolean('is_home')->default(true);
            $table->dateTime('kickoff_at');
            $table->unsignedTinyInteger('score_for')->nullable();
            $table->unsignedTinyInteger('score_against')->nullable();
            $table->string('status')->default('scheduled');
            $table->foreignId('mvp_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('cover_image_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['kickoff_at', 'status']);
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('match_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->default('news');
            $table->string('excerpt')->nullable();
            $table->longText('content');
            $table->string('cover_image_path')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'published_at']);
        });

        Schema::create('match_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['match_id', 'member_id']);
        });

        Schema::create('match_lineups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('slot');
            $table->boolean('is_starter')->default(true);
            $table->decimal('rating', 3, 1)->nullable();
            $table->unsignedTinyInteger('stars')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['match_id', 'member_id']);
            $table->unique(['match_id', 'slot']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_lineups');
        Schema::dropIfExists('match_attendances');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('matches');
        Schema::dropIfExists('members');
        Schema::dropIfExists('club_settings');

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropColumn(['google_id', 'avatar', 'role']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
