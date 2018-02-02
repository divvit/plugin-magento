<?php

class Divvit_Divvit_Model_Observer
{
    const ACTION_CUSTOMER = 'customer';
    const ACTION_ORDER_PLACED = 'orderPlaced';
    const ACTION_PAGEVIEW = 'pageview';
    const ACTION_CART_UPDATE = 'cartAdd';

    /**
     * @param Varien_Event_Observer $observer
     * @return bool
     */
    public function setCartData($observer)
    {
        $helper = Mage::helper('divvit_divvit');
        if ($helper->isEnabled()) {
            /** @var Mage_Sales_Model_Quote $order */
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $json = $helper->getQuoteDataJson($quote);
            $helper->queueEvent(self::ACTION_CART_UPDATE, $json);
        }
        return true;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return bool
     */
    public function emptyCart($observer)
    {
        $post = Mage::app()->getRequest()->getPost('update_cart_action');
        if ($post == 'empty_cart') {
            self::setCartData($observer);
        }
        return true;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return bool
     */
    public function onSalesOrderPlaceAfter($observer)
    {

    	/* @var $helper Divvit_Divvit_Helper_Data */
        $helper = Mage::helper('divvit_divvit');

        if ($helper->isEnabled() && $helper->tableChecker()) {

        	/* @var Mage_Sales_Model_Order $order */
            $order = $observer->getOrder();

            $divvit_order = Mage::getModel('divvit_divvit/order');
            $divvit_order->setData('uid',$helper->getUID());
            $divvit_order->setData('order_id',$order->getIncrementId());
            $divvit_order->save();

            $json = $helper->getOrderDataJson($order);
            $helper->queueEvent(self::ACTION_ORDER_PLACED, json_encode($json));
        }
        return true;
    }

    /**
     * Add customer login to the send log queued, this action can be related to persistant cart update.
     * @param Varien_Event_Observer $observer
     * @return bool
     */
    public function customerLogin($observer)
    {
        $helper = Mage::helper('divvit_divvit');
        if ($helper->isEnabled())
        {
            /* @var Mage_Customer_Model_Customer $customer */
            $customer = $observer->getData('customer');
            $json = [];
            $json['email'] = $customer->getEmail();
            $json['customerId'] = $customer->getId();

            $helper->queueEvent(self::ACTION_CUSTOMER, $json);
        }
        return true;
    }

    /**
     * @param $observer
     */
    public function adminSystemConfigChangedSection($observer)
    {
        /* @var $helper Divvit_Divvit_Helper_Data */
        $helper = Mage::helper('divvit_divvit');
        $helper->clearCacheConfig();
        $accessToken = $helper->generateAccessToken();
        $helper->setAccessToken($accessToken);
    }
}
