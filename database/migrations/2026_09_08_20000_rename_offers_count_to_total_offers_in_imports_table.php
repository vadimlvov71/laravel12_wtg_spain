<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Если есть старая колонка total_offers и нет total_offers — переименовываем
        if (Schema::hasColumn('imports', 'total_offers') && !Schema::hasColumn('imports', 'total_offers')) {
            Schema::table('imports', function (Blueprint $table) {
                $table->renameColumn('total_offers', 'total_offers');
            });
        }

        // На случай если ни одной нет (редкий кейс) — создаем total_offers
        if (!Schema::hasColumn('imports', 'total_offers')) {
            Schema::table('imports', function (Blueprint $table) {
                $table->unsignedInteger('total_offers')->default(0)->after('status');
            });
        }

        // processed_offers тоже должен существовать
        if (!Schema::hasColumn('imports', 'processed_offers')) {
            Schema::table('imports', function (Blueprint $table) {
                $table->unsignedInteger('processed_offers')->default(0)->after('total_offers');
            });
        }
    }

    public function down(): void
    {
        // Откат: если есть total_offers и нет total_offers — вернуть старое имя
        if (Schema::hasColumn('imports', 'total_offers') && !Schema::hasColumn('imports', 'total_offers')) {
            Schema::table('imports', function (Blueprint $table) {
                $table->renameColumn('total_offers', 'total_offers');
            });
        }

        // processed_offers не удаляю в down, чтобы не потерять данные
        // (если нужно жестко откатывать — скажи, дам вариант с dropColumn)
    }
};