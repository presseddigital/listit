<?php
namespace presseddigital\listit\migrations;

use presseddigital\listit\db\Table;

use Craft;
use craft\db\Table as CraftTable;
use craft\db\Migration;
use craft\helpers\MigrationHelper;

class Install extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp()
    {
        $this->createTable(Table::SUBSCRIPTIONS, [
            'id' => $this->primaryKey(),
            'list' => $this->string(64)->notNull(),
            'subscriberId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'elementId' => $this->integer(),
            'metadata' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, Table::SUBSCRIPTIONS, ['list'], false);
        $this->createIndex(null, Table::SUBSCRIPTIONS, ['siteId'], false);

        $this->addForeignKey(null, Table::SUBSCRIPTIONS, ['subscriberId'], CraftTable::USERS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::SUBSCRIPTIONS, ['elementId'], CraftTable::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, Table::SUBSCRIPTIONS, ['siteId'], CraftTable::SITES, ['id'], 'CASCADE', null);
    }

    public function safeDown()
    {
        if ($this->db->tableExists(Table::SUBSCRIPTIONS))
        {
            MigrationHelper::dropTable(Table::SUBSCRIPTIONS, $this);
        }
    }
}
