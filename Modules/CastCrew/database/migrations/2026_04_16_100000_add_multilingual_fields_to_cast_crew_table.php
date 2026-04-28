<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cast_crew', function (Blueprint $table) {
            if (! Schema::hasColumn('cast_crew', 'name_en')) {
                $table->string('name_en')->nullable()->after('name');
            }
            if (! Schema::hasColumn('cast_crew', 'name_ar')) {
                $table->string('name_ar')->nullable()->after('name_en');
            }
            if (! Schema::hasColumn('cast_crew', 'bio_en')) {
                $table->longText('bio_en')->nullable()->after('bio');
            }
            if (! Schema::hasColumn('cast_crew', 'bio_ar')) {
                $table->longText('bio_ar')->nullable()->after('bio_en');
            }
            if (! Schema::hasColumn('cast_crew', 'place_of_birth_en')) {
                $table->string('place_of_birth_en')->nullable()->after('place_of_birth');
            }
            if (! Schema::hasColumn('cast_crew', 'place_of_birth_ar')) {
                $table->string('place_of_birth_ar')->nullable()->after('place_of_birth_en');
            }
            if (! Schema::hasColumn('cast_crew', 'designation_en')) {
                $table->string('designation_en')->nullable()->after('designation');
            }
            if (! Schema::hasColumn('cast_crew', 'designation_ar')) {
                $table->string('designation_ar')->nullable()->after('designation_en');
            }
        });

        if (Schema::hasColumn('cast_crew', 'name_en')) {
            DB::statement('UPDATE cast_crew SET name_en = name WHERE name_en IS NULL OR name_en = \'\'');
        }
        if (Schema::hasColumn('cast_crew', 'bio_en')) {
            DB::statement('UPDATE cast_crew SET bio_en = bio WHERE bio_en IS NULL OR bio_en = \'\'');
        }
        if (Schema::hasColumn('cast_crew', 'place_of_birth_en')) {
            DB::statement('UPDATE cast_crew SET place_of_birth_en = place_of_birth WHERE place_of_birth_en IS NULL OR place_of_birth_en = \'\'');
        }
        if (Schema::hasColumn('cast_crew', 'designation_en')) {
            DB::statement('UPDATE cast_crew SET designation_en = designation WHERE designation_en IS NULL OR designation_en = \'\'');
        }
    }

    public function down(): void
    {
        Schema::table('cast_crew', function (Blueprint $table) {
            $cols = ['name_en', 'name_ar', 'bio_en', 'bio_ar', 'place_of_birth_en', 'place_of_birth_ar', 'designation_en', 'designation_ar'];
            $drop = [];
            foreach ($cols as $c) {
                if (Schema::hasColumn('cast_crew', $c)) {
                    $drop[] = $c;
                }
            }
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
