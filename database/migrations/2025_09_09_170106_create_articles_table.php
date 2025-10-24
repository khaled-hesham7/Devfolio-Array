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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('excerpt');
            $table->date('date');
            $table->bigInteger('views')->default(0);
            $table->bigInteger('likes')->default(0);
            $table->string('feature_image')->nullable();
            $table->json('tags')->nullable();
            $table->longText('content'); // كل البلوكات في JSON
            $table->string('lang', 5)->default('en');
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};

