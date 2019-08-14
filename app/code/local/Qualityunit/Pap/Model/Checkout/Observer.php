<?php
class Qualityunit_Pap_Model_Checkout_Observer {

  public function onCheckoutSuccess($observer) {
      $config = Mage::getSingleton('pap/config');
      if ($config->getTrackingMethod() != 'api') {
          return false;
      }

      //$event = $observer->getEvent();

      $lastOrderId = $this->getOnepage()->getCheckout()->getLastOrderId();
      if (!$lastOrderId) {
          Mage::log('Postaffiliatepro: No order has been found.');
          return false;
      }

      Mage::getModel('pap/pap')->createAffiliate($lastOrderId, true);
      Mage::getModel('pap/pap')->registerOrderByID($lastOrderId);
  }

  public function getOnepage() {
      return Mage::getSingleton('checkout/type_onepage');
  }
}
