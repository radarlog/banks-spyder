<?php

use yii\db\Schema;
use yii\db\Migration;

class m160226_145043_init extends Migration
{
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%companies}}', [
            'id' => Schema::TYPE_PK,
            'bank_id' => Schema::TYPE_INTEGER . '(10) NOT NULL',
            'name' => Schema::TYPE_STRING . '(100) NOT NULL',
            'currency' => Schema::TYPE_STRING . '(3) NOT NULL',
            'risk' => Schema::TYPE_STRING . '(10) NOT NULL',
            'params' => Schema::TYPE_TEXT,
        ], $tableOptions);

        $this->createTable('{{%statuses}}', array(
            'company_id' => Schema::TYPE_INTEGER . ' NOT NULL',
            'status' => Schema::TYPE_SMALLINT . ' NOT NULL',
            'status_text' => Schema::TYPE_TEXT,
            'created_at' => Schema::TYPE_TIMESTAMP . ' NOT NULL DEFAULT CURRENT_TIMESTAMP'
        ), $tableOptions);

        $this->createTable('{{%banks}}', array(
            'id' => Schema::TYPE_PK,
            'bid' => Schema::TYPE_STRING . '(10) NOT NULL',
            'status' => Schema::TYPE_SMALLINT . ' NOT NULL',
            'status_text' => Schema::TYPE_TEXT,
            'created_at' => Schema::TYPE_TIMESTAMP . ' NOT NULL DEFAULT CURRENT_TIMESTAMP'
        ), $tableOptions);

        $this->addForeignKey('status_fk', '{{%statuses}}', 'company_id', '{{%companies}}', 'id', 'CASCADE');
        $this->addForeignKey('bank_fk', '{{%companies}}', 'bank_id', '{{%banks}}', 'id', 'CASCADE');
    }
}
