<?php

class Divvit_Divvit_Model_Observer
{
    /**
     * @param Varien_Event_Observer $observer
     */
    public function setCartData($observer)
    {
        $helper = Mage::helper('divvit_divvit');
        if ($helper->isEnabled()) {
            /** @var Mage_Sales_Model_Quote $order */
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $json = $helper->getQuoteDataJson($quote);
            $helper->queueEvent("cartUpdated", $json);

        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function emptyCart($observer)
    {
        $post = Mage::app()->getRequest()->getPost('update_cart_action');
        if ($post == 'empty_cart') {
            self::setCartData($observer);
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function onSalesOrderPlaceAfter($observer)
    {
        $helper = Mage::helper('divvit_divvit');
        if ($helper->isEnabled()) {
            /** @var Mage_Sales_Model_Order $order */
            $order = $observer->getOrder();
            $json = $helper->getOrderDataJson($order);
            $helper->queueEvent("orderPlaced", $json);
        }
    }
}
