<?php

class m250205_092215_modify_last_edited_column extends CDbMigration
{
    public function up()
    {
        $this->execute("
            ALTER TABLE {{surveys}} 
            MODIFY last_edited DATETIME DEFAULT CURRENT_TIMESTAMP 
            ON UPDATE CURRENT_TIMESTAMP;
        ");
    }

    public function down()
    {

        $this->execute("
            ALTER TABLE {{surveys}} 
            MODIFY last_edited DATETIME NULL;
        ");
    }
}
