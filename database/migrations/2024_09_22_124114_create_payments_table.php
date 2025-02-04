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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('space_id');
            $table->unsignedBigInteger('concourse_id');
            $table->integer('amount');
            $table->string('payment_type');
            $table->string('payment_method');
            $table->string('payment_status');
            $table->decimal('water_bill', 10, 2)->nullable();
            $table->date('water_due')->nullable();
            $table->decimal('water_consumption', 10, 2)->nullable();
            $table->decimal('electricity_bill', 10, 2)->nullable();
            $table->date('electricity_due')->nullable();
            $table->decimal('electricity_consumption', 10, 2)->nullable();
            $table->decimal('rent_bill', 10, 2)->nullable();
            $table->date('rent_due')->nullable();
            $table->date('due_date')->nullable();
            $table->date('paid_date')->nullable();
            $table->decimal('penalty', 10, 2)->nullable();
            $table->boolean('is_water_late')->default(false);
            $table->boolean('is_electricity_late')->default(false);
            $table->boolean('is_rent_late')->default(false);
            $table->boolean('is_penalty')->default(false);
            $table->foreign('tenant_id')->references('id')->on('users');
            $table->foreign('space_id')->references('id')->on('spaces');
            $table->foreign('concourse_id')->references('id')->on('concourses');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
