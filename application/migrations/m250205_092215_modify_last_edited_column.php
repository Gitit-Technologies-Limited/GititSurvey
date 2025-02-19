<?php

class m250205_092215_modify_last_edited_column extends CDbMigration
{
    public function up()
    {
        // Check if the column already exists
        $table = Yii::app()->db->schema->getTable('{{surveys}}');
        if (!isset($table->columns['last_edited'])) {
            $this->addColumn('{{surveys}}', 'last_edited', 'DATETIME');
        }

        // Modify the column to have default CURRENT_TIMESTAMP and auto-update
        $this->execute("
            ALTER TABLE {{surveys}} 
            MODIFY last_edited DATETIME DEFAULT CURRENT_TIMESTAMP 
            ON UPDATE CURRENT_TIMESTAMP;
        ");
    }

    public function down()
    {
        // Revert to NULL if the column exists
        $table = Yii::app()->db->schema->getTable('{{surveys}}');
        if (isset($table->columns['last_edited'])) {
            $this->execute("
                ALTER TABLE {{surveys}} 
                MODIFY last_edited DATETIME NULL;
            ");
        }
    }
}
