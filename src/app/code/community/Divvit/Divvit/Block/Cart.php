<?php

class Divvit_Divvit_Block_Cart extends Mage_Core_Block_Template {
    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote() {
        return Mage::getSingleton("checkout/cart")->getQuote();
    }
}
