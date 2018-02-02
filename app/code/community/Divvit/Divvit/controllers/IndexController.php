<?php

class Divvit_Divvit_IndexController extends Mage_Core_Controller_Front_Action
{

    public function orderAction()
    {
        /* @var $helper Divvit_Divvit_Helper_Data */
        $helper = Mage::helper('divvit_divvit');
        $helper->clearCacheConfig();

        $this->getResponse()->setHeader('Content-type', 'application/json');
        if (!$helper->tableChecker()) {
            $this->getResponse()->setHttpResponseCode(401);
            $this->getResponse()->setBody(json_encode(array('error' => "`divvit_order` table not found")));
            return false;
        }

        $correctToken = $helper->getAccessToken();
        if (empty($correctToken))
        {
            $correctToken = $helper->generateAccessToken();
            $helper->setAccessToken($correctToken);
        }
        $requestToken = $this->getRequest()->getHeader('Authorization');

        $jsonContent = [];
        if ($requestToken != "token ".$correctToken)
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
            $fromOrder = Mage::getModel('sales/order')->loadByIncrementId($fromOrderId);
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
            $orderJson = $helper->getOrderDataJson($order)['order'];
            $orderJson['uid'] = $_order->getUid();
            $orderJson['createdAt'] = date('Y-m-d H:i:s', strtotime($order->getCreatedAt()));
            $jsonContent[] = $orderJson;
        }

        $this->getResponse()->setBody(json_encode($jsonContent));
    }
}
