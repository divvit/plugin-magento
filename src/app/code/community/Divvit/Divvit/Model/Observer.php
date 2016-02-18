<?php

class Divvit_Divvit_Model_Observer {
    public function setCartData(Varien_Event_Observer $observer) {
        Mage::getSingleton("customer/session")->setData("divvit_update_cart", true);
    }
}
