<?php

class Divvit_Divvit_IndexController extends Mage_Core_Controller_Front_Action
{

		public function orderAction()
		{
			/* @var $helper Divvit_Divvit_Helper_Data */
			$helper = Mage::helper('divvit_divvit');

			$correctToken = "token ".$helper->getAccessToken();
			$token = $this->getRequest()->getHeader('Authorization');

			$jsonContent = [];
			if ($token != $correctToken)
			{
				$this->getResponse()->setHttpResponseCode(401);
				$this->getResponse()->setHeader('Content-type', 'application/json');
				echo "Unauthorized";
				$this->getResponse()->sendResponse();
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
					echo "Your Order ID is not found";
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
				$orderJson['total'] = $_order->getGrandTotal();
				$orderJson['totalProductsNet'] = $_order->getGrandTotal() - $_order->getDiscountAmount();
				$orderJson['shipping'] = $_order->getShippingAmount();
				$orderJson['currency'] = $_order->getCurrentCurrencyCode();
				$orderJson['customer'] = $helper->getCustomerOrderDataJson($order);
				$orderJson['products'] = $helper->getOrderDataJson($order);
				$jsonContent[] = $orderJson;
			}
			
			echo json_encode($jsonContent);
		}
}
