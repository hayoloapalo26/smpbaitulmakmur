<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('users')
            ->whereNotNull('email')
            ->where('email', 'not like', '%@placeholder.local%')
            ->whereNotNull('username')
            ->update(['has_account' => true]);
    }

    public function down(): void
    {
    }
};
