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
        Schema::create('works', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users');
            $table->enum('status', ['standing', 'complete'])->default('standing');
            $table->date('date');
            $table->string('team');
            $table->string('ort');
            $table->boolean('vorort');
            $table->json('list_of_helpers')->nullable();
            $table->text('plan');
            $table->time('start_work');

            // Fields that can be added later (nullable)
            $table->text('reflection')->nullable();
            $table->text('defect')->nullable();
            $table->text('parent_contact')->nullable();
            $table->text('wellbeing_of_children')->nullable();
            $table->text('notes')->nullable();
            $table->text('wishes')->nullable();
            $table->string('pdf_file')->nullable();
            $table->time('end_work')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('works');
    }
};
