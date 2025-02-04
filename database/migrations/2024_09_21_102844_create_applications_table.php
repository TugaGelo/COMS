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
        Schema::create('applications', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('concourse_id');
            $table->unsignedBigInteger('space_id');
            $table->id();
            $table->string('business_name')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('space_type')->nullable();
            $table->string('address')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->string('business_type')->nullable();
            $table->string('requirements_status')->default('pending');
            $table->string('application_status')->default('pending');
            $table->integer('concourse_lease_term')->nullable();
            $table->string('remarks')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('concourse_id')->references('id')->on('concourses')->cascadeOnDelete();
            $table->foreign('space_id')->references('id')->on('spaces')->cascadeOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
