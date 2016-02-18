<?php

class Divvit_Divvit_Block_Order extends Mage_Core_Block_Template {
    /**
     * @return Mage_Sales_Model_Order
     */
    public function getLastOrder() {
        return Mage::getSingleton("checkout/session")->getLastRealOrder();
    }
}
