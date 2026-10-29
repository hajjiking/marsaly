<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The stock Laravel skeleton ships a default users migration; Marsaly defines
 * the full users schema in create_core_tables. We neutralize the default one
 * (sessions/cache/jobs tables from 0001_* stay untouched).
 */
return new class extends Migration
{
    public function up(): void
    {
        // no-op: users table is created by 2026_10_29_100001_create_core_tables
    }

    public function down(): void
    {
        // no-op
    }
};
