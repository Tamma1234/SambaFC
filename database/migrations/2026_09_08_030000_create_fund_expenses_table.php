<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fund_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category')->default('other');
            $table->unsignedBigInteger('amount');
            $table->date('spent_at');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['spent_at', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_expenses');
    }
};
