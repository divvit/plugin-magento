<?php

class Divvit_Divvit_Helper_Data extends Mage_Core_Helper_Abstract {
    const XML_DIVVIT_MERCHANT_SITE_ID = "divvit/settings/merchant_site_id";
    const XML_DIVVIT_ENABLED = "divvit/settings/enabled";
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
     * @return bool
     */
    public function hasCartData() {
        return Mage::getSingleton("customer/session")->hasData("divvit_update_cart");
    }

    /**
     * @return void
     */
    public function resetCartData() {
        Mage::getSingleton("customer/session")->unsetData("divvit_update_cart");
    }

    /**
     * @return string
     */
    public function getExtensionVersion() {
        return (string)Mage::getConfig()->getNode()->modules->Divvit_Divvit->version;
    }


    /**
     * @param Mage_Sales_Model_Order $order
     * @return string
     */
    public function getOrderDataJson($order) {
        $discountAmount = $order->getDiscountAmount() * -1.0;
        $data = ["order" =>
            [
                "products" => [],
                "orderId" => $order->getIncrementId(),
                "total" => $order->getGrandTotal() + $discountAmount,
                "currency" => $order->getOrderCurrencyCode(),
                "shipping" => $order->getShippingAmount(),
                "paymentMethod" => $order->getPayment()->getMethod(),
            ]
        ];

        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            $data["order"]["products"][] = [
                "id" => $item->getProduct()->getSku(),
                "name" => $item->getProduct()->getName(),
                "price" => $item->getPriceInclTax(),
                "currency" => $order->getOrderCurrencyCode(),
                "quantity" => $item->getQtyOrdered(),
            ];
        }

        if ($discountAmount > 0.001) {
            $data["order"]["voucher"] = $order->getCouponCode();
            $data["order"]["voucherDiscount"] = $discountAmount;
            $data["order"]["voucherType"] = "promo";
        }

        if (Mage::getSingleton("customer/session")->isLoggedIn()) {
            $data["order"]["customer"] = [
                "name" => Mage::getSingleton("customer/session")->getCustomer()->getName(),
                "idFields" => [
                    "id" => Mage::getSingleton("customer/session")->getCustomerId(),
                    "email" => Mage::getSingleton("customer/session")->getCustomer()->getEmail()
                ]
            ];
        } else {
            // also store name and email for guest checkouts
            $data["order"]["customer"] = [
                "name" => $order->getCustomerName(),
                "idFields" => [
                    "email" => $order->getBillingAddress()->getEmail()
                ]
            ];
        }

        return json_encode($data);
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
            $data["products"][] = [
                "id" => $item->getProduct()->getSku(),
                "name" => $item->getProduct()->getName(),
                "price" => $item->getPriceInclTax(),
            ];
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
}