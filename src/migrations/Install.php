<?php

namespace benf\neo\migrations;

use benf\neo\Plugin as Neo;
use craft\db\Migration;

/**
 * Class Install
 *
 * @package benf\neo\migrations
 * @author Spicy Web <plugins@spicyweb.com.au>
 * @author Benjamin Fleming
 * @since 2.0.0
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $hasBlocksTable = $this->db->tableExists('{{%neoblocks}}');
        $hasBlockStructuresTable = $this->db->tableExists('{{%neoblockstructures}}');
        $hasBlockTypesTable = $this->db->tableExists('{{%neoblocktypes}}');
        $hasBlockTypeGroupsTable = $this->db->tableExists('{{%neoblocktypegroups}}');

        // Create tables

        if (!$hasBlocksTable) {
            $this->createTable('{{%neoblocks}}', [
                'id' => $this->integer()->notNull(),
                'primaryOwnerId' => $this->integer()->notNull(),
                'fieldId' => $this->integer()->notNull(),
                'typeId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'PRIMARY KEY([[id]])',
            ]);
        }

        if (!$hasBlockStructuresTable) {
            $this->createTable('{{%neoblockstructures}}', [
                'id' => $this->primaryKey(),
                'structureId' => $this->integer()->notNull(),
                'ownerId' => $this->integer()->notNull(),
                'siteId' => $this->integer(),
                'fieldId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$hasBlockTypesTable) {
            $this->createTable('{{%neoblocktypes}}', [
                'id' => $this->primaryKey(),
                'fieldId' => $this->integer()->notNull(),
                'fieldLayoutId' => $this->integer(),
                'groupId' => $this->integer(),
                'name' => $this->string()->notNull(),
                'handle' => $this->string()->notNull(),
                'description' => $this->string(),
                'iconFilename' => $this->string(),
                'iconId' => $this->integer(),
                'color' => $this->string(),
                'enabled' => $this->boolean()->defaultValue(true)->notNull(),
                'minBlocks' => $this->smallInteger()->unsigned()->defaultValue(0),
                'maxBlocks' => $this->smallInteger()->unsigned(),
                'minSiblingBlocks' => $this->smallInteger()->unsigned()->defaultValue(0),
                'maxSiblingBlocks' => $this->smallInteger()->unsigned()->defaultValue(0),
                'minChildBlocks' => $this->smallInteger()->unsigned()->defaultValue(0),
                'maxChildBlocks' => $this->smallInteger()->unsigned(),
                'groupChildBlockTypes' => $this->boolean()->defaultValue(true)->notNull(),
                'childBlocks' => $this->text(),
                'topLevel' => $this->boolean()->defaultValue(true)->notNull(),
                'ignorePermissions' => $this->boolean()->defaultValue(true)->notNull(),
                'sortOrder' => $this->smallInteger()->unsigned(),
                'conditions' => $this->text(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$hasBlockTypeGroupsTable) {
            $this->createTable('{{%neoblocktypegroups}}', [
                'id' => $this->primaryKey(),
                'fieldId' => $this->integer()->notNull(),
                'name' => $this->string()->notNull(),
                'sortOrder' => $this->smallInteger()->unsigned(),
                'alwaysShowDropdown' => $this->boolean(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        // Create indexes

        if (!$hasBlocksTable) {
            $this->createIndex(null, '{{%neoblocks}}', ['primaryOwnerId'], false);
            $this->createIndex(null, '{{%neoblocks}}', ['fieldId'], false);
            $this->createIndex(null, '{{%neoblocks}}', ['typeId'], false);
        }

        if (!$hasBlockStructuresTable) {
            $this->createIndex(null, '{{%neoblockstructures}}', ['structureId'], false);
            $this->createIndex(null, '{{%neoblockstructures}}', ['ownerId'], false);
            $this->createIndex(null, '{{%neoblockstructures}}', ['siteId'], false);
            $this->createIndex(null, '{{%neoblockstructures}}', ['fieldId'], false);
        }

        if (!$hasBlockTypesTable) {
            $this->createIndex(null, '{{%neoblocktypes}}', ['name', 'fieldId'], false);
            $this->createIndex(null, '{{%neoblocktypes}}', ['handle', 'fieldId'], true);
            $this->createIndex(null, '{{%neoblocktypes}}', ['fieldId'], false);
            $this->createIndex(null, '{{%neoblocktypes}}', ['fieldLayoutId'], false);
            $this->createIndex(null, '{{%neoblocktypes}}', ['groupId'], false);
        }

        if (!$hasBlockTypeGroupsTable) {
            $this->createIndex(null, '{{%neoblocktypegroups}}', ['name', 'fieldId'], false);
            $this->createIndex(null, '{{%neoblocktypegroups}}', ['fieldId'], false);
        }

        // Add foreign keys

        if (!$hasBlocksTable) {
            $this->addForeignKey(null, '{{%neoblocks}}', ['fieldId'], '{{%fields}}', ['id'], 'CASCADE', null);
            $this->addForeignKey(null, '{{%neoblocks}}', ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
            $this->addForeignKey(null, '{{%neoblocks}}', ['primaryOwnerId'], '{{%elements}}', ['id'], 'CASCADE', null);
            $this->addForeignKey(null, '{{%neoblocks}}', ['typeId'], '{{%neoblocktypes}}', ['id'], 'CASCADE', null);
        }

        if (!$hasBlockStructuresTable) {
            $this->addForeignKey(null, '{{%neoblockstructures}}', ['structureId'], '{{%structures}}', ['id'], 'CASCADE',
                null);
            $this->addForeignKey(null, '{{%neoblockstructures}}', ['fieldId'], '{{%fields}}', ['id'], 'CASCADE', null);
            $this->addForeignKey(null, '{{%neoblockstructures}}', ['ownerId'], '{{%elements}}', ['id'], 'CASCADE',
                null);
            $this->addForeignKey(null, '{{%neoblockstructures}}', ['siteId'], '{{%sites}}', ['id'], 'CASCADE',
                'CASCADE');
        }

        if (!$hasBlockTypesTable) {
            $this->addForeignKey(null, '{{%neoblocktypes}}', ['fieldId'], '{{%fields}}', ['id'], 'CASCADE', null);
            $this->addForeignKey(null, '{{%neoblocktypes}}', ['fieldLayoutId'], '{{%fieldlayouts}}', ['id'], 'SET NULL',
                null);
            $this->addForeignKey(null, '{{%neoblocktypes}}', ['groupId'], '{{%neoblocktypegroups}}', ['id'], 'SET NULL',
                null);
            $this->addForeignKey(null, '{{%neoblocktypes}}', ['iconId'], '{{%assets}}', ['id'], 'SET NULL', null);
        }

        if (!$hasBlockTypeGroupsTable) {
            $this->addForeignKey(null, '{{%neoblocktypegroups}}', ['fieldId'], '{{%fields}}', ['id'], 'CASCADE', null);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        // Convert Neo fields to Matrix fields before dropping Neo tables
        foreach (Neo::$plugin->fields->getNeoFields() as $field) {
            // Don't bother deleting the old Neo block types and groups during the conversion, since we're about to
            // drop the tables anyway
            Neo::$plugin->conversion->convertFieldToMatrix($field, false);
        }

        $this->dropTableIfExists('{{%neoblocks}}');
        $this->dropTableIfExists('{{%neoblockstructures}}');
        $this->dropTableIfExists('{{%neoblocktypes}}');
        $this->dropTableIfExists('{{%neoblocktypegroups}}');

        return true;
    }
}
