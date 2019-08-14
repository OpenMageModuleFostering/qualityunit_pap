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
          Mage::helper('pap')->log('Postaffiliatepro: Transaction status changed to '.$order->getStatus());
          if ($order->getStatus() == 'holded' || $order->getStatus() == 'pending') {
              Mage::getModel('pap/pap')->setOrderStatus($order, $this->pending);
              return $this;
          }

          if ($order->getStatus() == 'canceled') {
              Mage::getModel('pap/pap')->setOrderStatus($order, $this->declined);
              return $this;
          }

          // refund
          if ($order->getStatus() == 'closed') {
              //Mage::getModel('pap/pap')->setOrderStatus($order, $this->declined);
              Mage::getModel('pap/pap')->refundCommissions($order);
              return $this;
          }

          $refunded = array();
          if ($order->getStatus() == 'complete') {
              if ($order->getBaseTotalPaid() > 0) { // was paid
                  if ($order->getBaseTotalRefunded() > 0) { // partial refund handling
                      $refunded = $this->getRefundedItemIDs($order);
                  }
                  Mage::getModel('pap/pap')->setOrderStatus($order, $this->approved, $refunded);
              }
              else { // completed but not paid
                  Mage::getModel('pap/pap')->setOrderStatus($order, $this->pending);
              }
              return $this;
          }

          // if we are here, it's probably a partial refund
          if ($order->getBaseTotalRefunded() > 0 || $order->getBaseTotalCanceled() > 0) {
              $refunded = $this->getRefundedItemIDs($order);
              //Mage::getModel('pap/pap')->setOrderStatus($order, $this->declined, $refunded);
              Mage::getModel('pap/pap')->refundCommissions($order, $refunded);
          }
      }
      catch (Exception $e) {
          Mage::getSingleton('adminhtml/session')->addWarning('A PAP API error occurred: '.$e->getMessage());
      }

      return $this;
    }

    private function getRefundedItemIDs($order) {
        $refunded = array();
        $items = $order->getAllVisibleItems();

        foreach($items as $i=>$item) {
            if ($item->getStatus() == 'Refunded') {
                $productid = $item->getProductId();
                $product = Mage::getModel('catalog/product')->load($productid);
                $refunded[$i] = $product->getSku();
            }
        }
        return $refunded;
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
