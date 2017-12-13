<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$table = $installer->getConnection()
    ->newTable($installer->getTable('divvit_divvit/order'))
    ->addColumn('divvit_order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'divvit_order_id')
    ->addColumn('uid', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        'primary'   => false,
    ), 'UID')
    ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
    ), 'ORDER ID');
$installer->getConnection()->createTable($table);

$installer->endSetup();
