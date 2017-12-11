<?php

class Divvit_Divvit_Helper_Data extends Mage_Core_Helper_Abstract {
    const XML_DIVVIT_MERCHANT_SITE_ID = "divvit/settings/merchant_site_id";
    const XML_DIVVIT_ENABLED = "divvit/settings/enabled";
    const XML_DIVVIT_ACCESS_TOKEN = "divvit/settings/access_token";
    const EVENT_QUEUE = "divvit_event_queue";

    /**
     * @return string
     */
    public function getMerchantSiteId() {
        return Mage::getStoreConfig(self::XML_DIVVIT_MERCHANT_SITE_ID);
    }

    /**
     * @return bool
     */
    public function isEnabled() {
        return (bool)Mage::getStoreConfig(self::XML_DIVVIT_ENABLED);
    }

    /**
     * @return string
     */
    public function getExtensionVersion() {
        return (string)Mage::getConfig()->getNode()->modules->Divvit_Divvit->version;
    }

	public function getAccessToken()
	{
		return (string) Mage::getStoreConfig(self::XML_DIVVIT_ACCESS_TOKEN);
	}

    /**
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function getCustomerOrderDataJson($order) {
        $customerId = 0;

        //Check if customer is guest
        if ($order->getCustomerId() != '' || !empty($order->getCustomerId())) {
            $customerId = $order->getCustomerId();
        }

        $data = [
            "name" => $order->getCustomerName(),
            "id" => $customerId,
            "idFields" => [
                "email" => $order->getBillingAddress()->getEmail()
            ]
        ];
        return $data;
    }


    /**
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function getOrderDataJson($order) {
        $discountAmount = $order->getDiscountAmount() * -1.0;

        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            $data[] = [
                "id" => $item->getProduct()->getSku(),
                "name" => $item->getProduct()->getName(),
                "price" => (float)$item->getPriceInclTax(),
                "currency" => $order->getOrderCurrencyCode(),
                "quantity" => (int)$item->getQtyOrdered(),
            ];
        }
        return $data;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @return string
     */
    public function getQuoteDataJson($quote) {
        $data = [
            "cartId" => $quote->getId(),
            "products" => [],
        ];
        /** @var Mage_Sales_Model_Quote_Item $item */
        foreach ($quote->getAllVisibleItems() as $item) {
            $itemData = [
                "id" => $item->getProduct()->getSku(),
                "name" => $item->getProduct()->getName(),
                "price" => (float)$item->getPriceInclTax(),
            ];
            $itemQuantity = (int)$item->getQty();
            if ($itemQuantity > 1) {
                $itemData["quantity"] = $itemQuantity;
            }
            $data["products"][] = $itemData;
        }

        return json_encode($data);
    }

    /**
     * @param String $type
     * @param String $json
     */
    public function queueEvent($type, $json)
    {
        $session = Mage::getSingleton('customer/session');
        $queue = $session->getData(self::EVENT_QUEUE);
        if (!is_array($queue)) {
            $queue = [];
        }
        // queue new event
        $queue[] = [
            "type" => $type,
            "json" => $json
        ];
        $session->setData(self::EVENT_QUEUE, $queue);
    }

    public function getUID()
	{
		if ($this->isEnabled()){
			return Mage::getSingleton('customer/session')->getCookie()->get('DV_TRACK');
		}

		return false;

	}

	public function setAccessToken($token)
	{
		Mage::getConfig()->saveConfig(self::XML_DIVVIT_ACCESS_TOKEN,$token);
	}

    public function generateAccessToken()
    {

        $httpClient = new Zend_Http_Client($this->getDivvitUrl('tracker')."/auth/register");
        $httpClient->setHeaders('Content-type','application/json');

        $url = str_replace('index.php/', '', Mage::getUrl('divvit/index/order'));

        $data = ['frontendId' => $this->getMerchantSiteId(),'url' => $url];

        $httpClient->setRawData(json_encode($data));
        $requestResult = $httpClient->request("POST");

        $result = json_decode($requestResult->getBody());

        return $result->accessToken;
    }

    /**
     * Get corresponding divvit tag/tracker url
     * @access   public
     */
    public function getDivvitUrl($type = '')
    {
        if ($type == 'tag') {
            if (getenv('DIVVIT_TAG_URL') != '') {
                return getenv('DIVVIT_TAG_URL');
            } else {
                return 'https://tag.divvit.com';
            }
        } else {
            if (getenv('DIVVIT_TRACKING_URL') != '') {
                return getenv('DIVVIT_TRACKING_URL');
            } else {
                return 'https://tracker.divvit.com';
            }
        }
    }
}
