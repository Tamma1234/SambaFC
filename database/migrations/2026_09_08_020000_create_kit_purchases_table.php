<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kit_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount')->default(200000);
            $table->string('status')->default('unpaid');
            $table->timestamp('paid_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('member_id');
            $table->index(['status', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kit_purchases');
    }
};
