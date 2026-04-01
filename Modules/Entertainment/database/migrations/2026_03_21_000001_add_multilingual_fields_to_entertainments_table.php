<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entertainments', function (Blueprint $table) {
            if (!Schema::hasColumn('entertainments', 'name_en')) {
                $table->string('name_en')->nullable()->after('name');
            }
            if (!Schema::hasColumn('entertainments', 'name_ar')) {
                $table->string('name_ar')->nullable()->after('name_en');
            }
            if (!Schema::hasColumn('entertainments', 'description_en')) {
                $table->longText('description_en')->nullable()->after('description');
            }
            if (!Schema::hasColumn('entertainments', 'description_ar')) {
                $table->longText('description_ar')->nullable()->after('description_en');
            }
        });
    }

    public function down(): void
    {
        Schema::table('entertainments', function (Blueprint $table) {
            $dropColumns = [];
            if (Schema::hasColumn('entertainments', 'name_en')) {
                $dropColumns[] = 'name_en';
            }
            if (Schema::hasColumn('entertainments', 'name_ar')) {
                $dropColumns[] = 'name_ar';
            }
            if (Schema::hasColumn('entertainments', 'description_en')) {
                $dropColumns[] = 'description_en';
            }
            if (Schema::hasColumn('entertainments', 'description_ar')) {
                $dropColumns[] = 'description_ar';
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
