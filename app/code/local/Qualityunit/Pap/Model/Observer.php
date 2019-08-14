<?php
class Qualityunit_Pap_Model_Observer {
    public $declined = 'D';
    public $pending = 'P';
    public $approved = 'A';

    public function orderModified($observer) {
      $event = $observer->getEvent();
      $order = $event->getOrder();

      $config = Mage::getSingleton('pap/config');
      if (!$config->isConfigured()) return false;

      try {
          if ($order->getStatus() == 'holded') {
              Mage::getModel('pap/pap')->setOrderStatus($order, $this->pending);
          }

          if ($order->getStatus() == 'canceled') {
              Mage::getModel('pap/pap')->setOrderStatus($order, $this->declined);
          }

          // refund
          if (($order->getBaseTotalPaid() > 0) && ($order->getBaseTotalPaid() <= ($order->getBaseTotalRefunded() + $order->getBaseTotalCanceled()))) {
              Mage::getModel('pap/pap')->setOrderStatus($order, $this->declined);
          }

          // partial refund
          if (($order->getBaseTotalPaid() > 0) && ($order->getBaseTotalRefunded() > 0 || $order->getBaseTotalCanceled() > 0)) {
              Mage::getModel('pap/pap')->setOrderStatus($order, $this->pending);
          }

          if($order->getStatus() == 'complete') {
              if ($order->getBaseTotalPaid() > 0) { // was paid
                  Mage::getModel('pap/pap')->setOrderStatus($order, $this->approved);
              }
              else { // completed but not paid
                  Mage::getModel('pap/pap')->setOrderStatus($order, $this->pending);
              }
          }
      }
      catch (Exception $e) {
          Mage::getSingleton('adminhtml/session')->addWarning('A PAP API error occurred: '.$e->getMessage());
      }

      return $this;
    }

    public function thankYouPageViewed($observer) {
        $quoteId = Mage::getSingleton('checkout/session')->getLastQuoteId();
        $block = Mage::app()->getFrontController()->getAction()->getLayout()->getBlock('pap_saletracking');
        if ($quoteId && ($block instanceof Mage_Core_Block_Abstract)) {
            $quote = Mage::getModel('sales/quote')->load($quoteId);
            $block->setQuote($quote);
        }
    }
}
