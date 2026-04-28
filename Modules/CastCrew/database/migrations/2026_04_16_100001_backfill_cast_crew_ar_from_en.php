<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cast_crew')) {
            return;
        }

        if (Schema::hasColumn('cast_crew', 'name_ar')) {
            DB::statement('UPDATE cast_crew SET name_ar = name_en WHERE name_ar IS NULL OR TRIM(name_ar) = \'\'');
        }
        if (Schema::hasColumn('cast_crew', 'bio_ar')) {
            DB::statement('UPDATE cast_crew SET bio_ar = bio_en WHERE bio_ar IS NULL OR TRIM(bio_ar) = \'\'');
        }
        if (Schema::hasColumn('cast_crew', 'place_of_birth_ar')) {
            DB::statement('UPDATE cast_crew SET place_of_birth_ar = place_of_birth_en WHERE place_of_birth_ar IS NULL OR TRIM(place_of_birth_ar) = \'\'');
        }
    }

    public function down(): void
    {
        //
    }
};
