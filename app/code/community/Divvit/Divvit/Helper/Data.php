<?php

class Divvit_Divvit_Helper_Data extends Mage_Core_Helper_Abstract {
    const XML_DIVVIT_MERCHANT_SITE_ID = "divvit/settings/merchant_site_id";
    const XML_DIVVIT_ENABLED = "divvit/settings/enabled";
    const XML_DIVVIT_ACCESS_TOKEN = "divvit/settings/access_token";
    const EVENT_QUEUE = "divvit_event_queue";

    /**
     * @return string
     */
    public function getMerchantSiteId()
    {
        return Mage::getStoreConfig(self::XML_DIVVIT_MERCHANT_SITE_ID);
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)Mage::getStoreConfig(self::XML_DIVVIT_ENABLED);
    }

    /**
     * @return string
     */
    public function getExtensionVersion()
    {
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
    public function getCustomerOrderDataJson($order)
    {
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
    public function getOrderDataJson($order)
    {
        $discountAmount = $order->getDiscountAmount() * -1.0;
        $data = ["order" => [
            "products" => [],
            "orderId" => (string)$order->getIncrementId(),
            "total" => (float)$order->getGrandTotal(),
            "totalProductsNet" => $order->getGrandTotal() - $order->getShippingAmount() + $discountAmount,
            "currency" => (string)$order->getOrderCurrencyCode(),
            "shipping" => (float)$order->getShippingAmount(),
            "paymentMethod" => (string)$order->getPayment()->getMethod(),
        ]];

        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            $data["order"]["products"][] = [
                "id" => (string)$item->getProduct()->getSku(),
                "name" => (string)$item->getProduct()->getName(),
                "price" => (float)$item->getPriceInclTax(),
                "currency" => (string)$order->getOrderCurrencyCode(),
                "quantity" => (int)$item->getQtyOrdered(),
            ];
        }

        if ($discountAmount > 0.001) {
            $data["order"]["voucher"] = (string)$order->getCouponCode();
            $data["order"]["voucherDiscount"] = (float)$discountAmount;
            $data["order"]["voucherType"] = "promo";
        }

        // also store name and email for guest checkouts
        $data["order"]["customer"] = [
            "name" => (string)$order->getCustomerName(),
            "idFields" => [
                "email" => (string)$order->getBillingAddress()->getEmail()
            ]
        ];

        return $data;
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @return string
     */
    public function getQuoteDataJson($quote)
    {
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

        $data = [
            'frontendId' => $this->getMerchantSiteId(),
            'url' => Mage::getUrl('divvit/index/order', array('_type' => 'web'))
        ];

        $httpClient->setRawData(json_encode($data));
        $requestResult = $httpClient->request("POST");

        $result = json_decode($requestResult->getBody());

        return $result->accessToken;
    }

    public function tableChecker()
    {
        $installer = new Mage_Core_Model_Resource_Setup('Divvit_Divvit_setup');
        return $installer->tableExists('divvit_divvit/order');
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

    public function clearCacheConfig()
    {
        $caches=array('config','config_api','config_api2');
        foreach($caches as $cache) {
            $c = Mage::app()->getCacheInstance()->cleanType($cache);
            Mage::dispatchEvent('adminhtml_cache_refresh_type', array('type' => $cache));
        }
    }
}
