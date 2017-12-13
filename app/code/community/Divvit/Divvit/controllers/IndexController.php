<?php

class Divvit_Divvit_IndexController extends Mage_Core_Controller_Front_Action
{
    public function orderAction()
    {
        /* @var $helper Divvit_Divvit_Helper_Data */
        $helper = Mage::helper('divvit_divvit');

        $helper->clearCacheConfig();

        $correctToken = "token ".$helper->getAccessToken();
        $token = $this->getRequest()->getHeader('Authorization');
        $this->getResponse()->setHeader('Content-type', 'application/json');

        $jsonContent = [];
        if ($token != $correctToken)
        {
            $this->getResponse()->setHttpResponseCode(401);
            $this->getResponse()->setBody(json_encode(array('error' => "Unauthorized")));
            return false;
        }

        /* @var $fromOrder Mage_Sales_Model_Order */

        /* @var $orderCollection Mage_Sales_Model_Resource_Order_Collection */
        $orderCollection = Mage::getModel('sales/order')->getResourceCollection();
        $fromOrderId = $this->getRequest()->getParam('after');

        if ($fromOrderId)
        {
            $fromOrder = Mage::getModel('sales/order')->load($fromOrderId);
            if (!$fromOrder->getId())
            {
                $this->getResponse()->setBody(json_encode(array('error' => "Your Order ID is not found")));
                return false;
            }
            $orderCollection->addAttributeToFilter('created_at',['gt' => $fromOrder->getCreatedAt()]);
        }

        $divvitOrderTable = Mage::getModel('divvit_divvit/order')->getResource()->getTable('divvit_divvit/order');

        $orderCollection->getSelect()->join($divvitOrderTable, "main_table.increment_id = ".$divvitOrderTable.".order_id");
        $orderCollection->load();

        foreach ($orderCollection as $_order)
        {
            /* @var $order Mage_Sales_Model_Order */
            $order = Mage::getModel('sales/order')->load($_order->getId());
            $orderJson = [];
            $orderJson['uid'] = $_order->getUid();
            $orderJson['createdAt'] = date('Y-m-d H:i:s',strtotime($order->getCreatedAt()));
            $orderJson['orderId'] = $_order->getId();
            $orderJson['total'] = (float)$_order->getGrandTotal();
            $orderJson['totalProductsNet'] = $_order->getGrandTotal() - $_order->getDiscountAmount();
            $orderJson['shipping'] = (float)$_order->getShippingAmount();
            $orderJson['currency'] = $_order->getOrderCurrencyCode();
            $orderJson['customer'] = $helper->getCustomerOrderDataJson($order);
            $orderJson['products'] = $helper->getOrderDataJson($order);
            $jsonContent[] = $orderJson;
        }

        $this->getResponse()->setBody(json_encode($jsonContent));
    }
}
