<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fund_contributions', function (Blueprint $table) {
            $table->uuid('group_key')->nullable()->after('id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('fund_contributions', function (Blueprint $table) {
            $table->dropIndex(['group_key']);
            $table->dropColumn('group_key');
        });
    }
};
