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
        Schema::create('spaces', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('concourse_id');
            $table->unsignedBigInteger('application_id')->nullable();
            $table->id();

            $table->string('name');
            $table->string('status');
            $table->float('sqm');
            $table->float('price');
            $table->string('business_name')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('address')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->string('space_type')->nullable();
            $table->string('business_type')->nullable();
            $table->date('lease_start')->nullable();
            $table->date('lease_end')->nullable();
            $table->date('lease_due')->nullable();
            $table->integer('lease_term')->nullable();
            $table->string('lease_status')->nullable();
            $table->decimal('water_consumption', 10, 2)->nullable();
            $table->decimal('water_bills', 10, 2)->nullable();
            $table->date('water_due')->nullable();
            $table->decimal('electricity_consumption', 10, 2)->nullable();
            $table->decimal('electricity_bills', 10, 2)->nullable();
            $table->date('electricity_due')->nullable();
            $table->decimal('rent_bills', 10, 2)->nullable();
            $table->date('rent_due')->nullable();
            $table->string('water_payment_status')->nullable();
            $table->string('electricity_payment_status')->nullable();
            $table->string('rent_payment_status')->nullable();
            $table->decimal('penalty', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('requirements_status')->nullable();
            $table->string('application_status')->nullable();
            $table->string('remarks')->nullable();
            $table->integer('space_width');
            $table->integer('space_length');
            $table->float('space_area');
            $table->string('space_dimension');
            $table->integer('space_coordinates_x');
            $table->integer('space_coordinates_y');
            $table->integer('space_coordinates_x2');
            $table->integer('space_coordinates_y2');
           
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('concourse_id')->references('id')->on('concourses')->cascadeOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spaces');
    }
};
