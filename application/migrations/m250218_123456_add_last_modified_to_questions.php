<?php

class m250218_123456_add_last_modified_to_questions extends CDbMigration
{
    public function up()
    {
        $this->addColumn('{{questions}}', 'last_modified', 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }

    public function down()
    {
        $this->dropColumn('{{questions}}', 'last_modified');
    }
}
