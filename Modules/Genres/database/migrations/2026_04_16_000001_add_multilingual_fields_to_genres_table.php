<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('genres', function (Blueprint $table) {
            if (! Schema::hasColumn('genres', 'name_en')) {
                $table->string('name_en')->nullable()->after('name');
            }
            if (! Schema::hasColumn('genres', 'name_ar')) {
                $table->string('name_ar')->nullable()->after('name_en');
            }
            if (! Schema::hasColumn('genres', 'description_en')) {
                $table->longText('description_en')->nullable()->after('description');
            }
            if (! Schema::hasColumn('genres', 'description_ar')) {
                $table->longText('description_ar')->nullable()->after('description_en');
            }
        });

        if (Schema::hasColumn('genres', 'name_en')) {
            DB::statement('UPDATE genres SET name_en = name WHERE name_en IS NULL OR name_en = \'\'');
        }
        if (Schema::hasColumn('genres', 'description_en')) {
            DB::statement('UPDATE genres SET description_en = description WHERE description_en IS NULL OR description_en = \'\'');
        }
    }

    public function down(): void
    {
        Schema::table('genres', function (Blueprint $table) {
            $dropColumns = [];
            if (Schema::hasColumn('genres', 'name_en')) {
                $dropColumns[] = 'name_en';
            }
            if (Schema::hasColumn('genres', 'name_ar')) {
                $dropColumns[] = 'name_ar';
            }
            if (Schema::hasColumn('genres', 'description_en')) {
                $dropColumns[] = 'description_en';
            }
            if (Schema::hasColumn('genres', 'description_ar')) {
                $dropColumns[] = 'description_ar';
            }
            if (! empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
