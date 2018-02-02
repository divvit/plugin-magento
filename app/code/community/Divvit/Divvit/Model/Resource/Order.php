<?php
    class Divvit_Divvit_Model_Resource_Order extends Mage_Core_Model_Resource_Db_Abstract{
        protected function _construct()
        {
            $this->_init('divvit_divvit/order', 'divvit_order_id');
        }
    }
