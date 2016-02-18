<?php

class Divvit_Divvit_Helper_Data extends Mage_Core_Helper_Abstract {
    const XML_DIVVIT_MERCHANT_SITE_ID = "divvit/settings/merchant_site_id";
    const XML_DIVVIT_ENABLED = "divvit/settings/enabled";

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
}
