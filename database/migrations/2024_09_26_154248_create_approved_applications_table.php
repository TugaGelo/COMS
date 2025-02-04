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
        Schema::create('approved_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('space_id')->constrained();
            $table->foreignId('concourse_id')->constrained();
            $table->string('business_name');
            $table->string('owner_name');
            $table->string('email');
            $table->string('phone_number');
            $table->text('address');
            $table->string('status');
            $table->text('remarks')->nullable();
            $table->string('business_type')->nullable();
            $table->string('concourse_lease_term');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approved_applications');
    }
};
