<?php

class Divvit_Divvit_Model_Observer {
    /**
     * @param Varien_Event_Observer $observer
     */
    public function setCartData($observer) {
        Mage::getSingleton("customer/session")->setData("divvit_update_cart", true);

        $helper = Mage::helper('divvit_divvit');
        if ($helper->isEnabled()) {
            /** @var Mage_Sales_Model_Quote $order */
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $json = $helper->getQuoteDataJson($quote);
            $helper->sendBackgroundRequest("cart", $json);
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function onSalesOrderPlaceAfter($observer) {
        $helper = Mage::helper('divvit_divvit');
        if ($helper->isEnabled()) {
            /** @var Mage_Sales_Model_Order $order */
            $order = $observer->getOrder();
            $json = $helper->getOrderDataJson($order);
            $helper->sendBackgroundRequest("order", $json);
        }
    }
}
