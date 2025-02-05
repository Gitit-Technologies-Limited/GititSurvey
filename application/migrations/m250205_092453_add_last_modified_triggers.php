<?php

class m250205_092453_add_last_modified_triggers extends CDbMigration
{

    public function up()
    {
        $this->execute("
            CREATE TRIGGER update_questions_last_modified
            AFTER UPDATE ON {{question_l10ns}}
            FOR EACH ROW
            BEGIN
                UPDATE {{questions}}
                SET last_modified = NOW()
                WHERE qid = NEW.qid;
            END;
        ");

        $this->execute("
            CREATE TRIGGER insert_questions_last_modified
            AFTER INSERT ON {{question_l10ns}}
            FOR EACH ROW
            BEGIN
                UPDATE {{questions}}
                SET last_modified = NOW()
                WHERE qid = NEW.qid;
            END;
        ");

        $this->execute("
            CREATE TRIGGER update_surveys_last_modified
            AFTER UPDATE ON {{questions}}
            FOR EACH ROW
            BEGIN
                UPDATE {{surveys}}
                SET last_edited = NOW()
                WHERE sid = NEW.sid;
            END;
        ");
    }

    public function down()
    {
        $this->execute("DROP TRIGGER IF EXISTS update_questions_last_modified;");
        $this->execute("DROP TRIGGER IF EXISTS insert_questions_last_modified;");
        $this->execute("DROP TRIGGER IF EXISTS update_surveys_last_modified;");
    }
}
