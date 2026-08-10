<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persons', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(true);
            $table->string('profile_picture_path')->nullable();
            $table->string('name');
            $table->string('person_type', 5);
            $table->string('document', 14);
            $table->string('email')->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('zip', 8)->nullable();
            $table->string('street')->nullable();
            $table->string('number', 10)->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('complement')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persons');
    }
};
