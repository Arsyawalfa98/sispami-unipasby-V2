<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveAssessmentColumnsFromPemenuhanDokumen extends Migration
{
    public function up()
    {
        Schema::table('pemenuhan_dokumen', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'nilai',
                'sebutan',
                'bobot',
                'tertimbang',
                'nilai_auditor',
                'revisi'
            ]);
        });
    }

    public function down()
    {
        Schema::table('pemenuhan_dokumen', function (Blueprint $table) {
            $table->string('status')->default('draft');
            $table->double('nilai')->nullable();
            $table->string('sebutan')->nullable();
            $table->double('bobot')->nullable();
            $table->double('tertimbang')->nullable();
            $table->double('nilai_auditor')->nullable();
            $table->text('revisi')->nullable();
        });
    }
}