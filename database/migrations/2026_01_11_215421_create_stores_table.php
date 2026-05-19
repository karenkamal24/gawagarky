<?php
// database/migrations/2026_01_11_create_stores_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            
            // معلومات المتجر
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category');
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();
            
            // معلومات التواصل
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('whatsapp')->nullable();
            $table->text('address')->nullable();
            
            // البيانات
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('followers')->default(0);
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};