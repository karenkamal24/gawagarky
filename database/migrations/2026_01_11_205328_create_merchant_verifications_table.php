<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // الصور
            $table->string('id_card_front')->nullable(); // الهوية الأمامية
            $table->string('id_card_back')->nullable(); // الهوية الخلفية
            $table->string('commercial_register')->nullable(); // السجل التجاري
            $table->string('store_front')->nullable(); // واجهة المحل
            $table->string('owner_photo')->nullable(); // صورة المالك
            
            // حالات التحقق
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable(); // سبب الرفض
            
            // البيانات الإضافية
            $table->string('store_name')->nullable();
            $table->text('store_description')->nullable();
            $table->string('store_category')->nullable();
            
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_verifications');
    }
};