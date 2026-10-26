<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('osis_programs', function (Blueprint $table) {
            $table->id();
            $table->string('nama_program');
            $table->date('tanggal');
            $table->enum('status', ['direncanakan', 'berjalan', 'selesai', 'batal']);
            $table->string('proposal_file')->nullable();
            $table->string('lpj_file')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('osis_programs');
    }
};
