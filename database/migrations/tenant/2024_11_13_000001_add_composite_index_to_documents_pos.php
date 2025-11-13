<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddCompositeIndexToDocumentsPos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Agregar índice compuesto para optimizar queries del dashboard
        // Esta es la query que se ejecuta en documento_pos_totals:
        // SELECT * FROM documents_pos WHERE establishment_id = ? AND currency_id = ? AND date_of_issue BETWEEN ? AND ?

        // Verificar si el índice ya existe
        $sm = Schema::getConnection()->getDoctrineSchemaManager();
        $indexes = $sm->listTableIndexes('documents_pos');

        if (!array_key_exists('idx_documents_pos_establishment_currency_date', $indexes)) {
            DB::statement('CREATE INDEX idx_documents_pos_establishment_currency_date ON documents_pos (establishment_id, currency_id, date_of_issue)');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $sm = Schema::getConnection()->getDoctrineSchemaManager();
        $indexes = $sm->listTableIndexes('documents_pos');

        if (array_key_exists('idx_documents_pos_establishment_currency_date', $indexes)) {
            DB::statement('DROP INDEX idx_documents_pos_establishment_currency_date ON documents_pos');
        }
    }
}
