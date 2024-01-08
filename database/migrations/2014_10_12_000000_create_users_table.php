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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('username')->unique();
            $table->string('image')->default('');
            $table->string('cover')->default('');
            $table->string('password');
            $table->string('location')->default('');
            $table->string('lat')->default('');
            $table->string('lng')->default('');
            $table->string('dob')->default('');
            $table->string('gender')->default('');
            $table->string('bio')->default('');
            $table->integer('verify')->default(0);
            $table->string('otp')->default('');
            $table->string('otp_time')->default('');
            $table->boolean('is_public')->default(0);
            $table->boolean('counter')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
