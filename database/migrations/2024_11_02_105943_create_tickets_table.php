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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->foreignId('space_id')->constrained('spaces');
            $table->foreignId('concourse_id')->constrained('concourses');
            $table->string('incident_ticket_number');
            $table->string('concern_type');
            $table->string('remarks')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority')->default('low');
            $table->string('status')->default('pending');
            $table->json('activity_log')->nullable();
            $table->json('images')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->cascadeOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
