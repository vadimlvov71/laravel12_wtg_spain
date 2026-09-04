<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_import_logs', function (Blueprint $table) {
            $table->id();
            $table->string('supplier_code'); // Код поставщика из запроса
            $table->enum('status', ['success', 'not_found', 'blocked', 'error'])->default('error');
            $table->string('external_import_id')->nullable();
            $table->text('error_message')->nullable();
            $table->json('request_data')->nullable(); // Сохраняем данные запроса
            $table->string('ip_address')->nullable();
            $table->timestamps();
            
            $table->index(['supplier_code', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_import_logs');
    }
};
