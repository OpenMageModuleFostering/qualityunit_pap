<?php
class Qualityunit_Pap_Model_Pap extends Mage_Core_Model_Abstract {
    protected $papSession;
    public $declined = 'D';
    public $pending = 'P';
    public $approved = 'A';

    public function getSession() {
      if (($this->papSession != '') && ($this->papSession != null)) {
        return $this->papSession;
      }

      Mage::getSingleton('pap/config')->includePapAPI();
      $config = Mage::getSingleton('pap/config');
      $url = $config->getInstallationPath().'/scripts/server.php';
      $username = $config->getAPICredential('username');
      $password = $config->getAPICredential('pass');

      $session = new Gpf_Api_Session($url);
      if (!$session->login($username, $password)) {
          Mage::log('Postaffiliatepro: Could not initiate API session: '.$session->getMessage());
          return null;
      }

      $this->papSession = $session;
      return $this->papSession;
    }

    public function setOrderStatus($order, $status, $refunded = array()) {
        Mage::log('Postaffiliatepro: Changing status of order '.$order->getIncrementId()." to '$status'");
        $session = $this->getSession();

        if (empty($session)) {
            Mage::log('Postaffiliatepro: The module is still not configured!');
            return;
        }

        $request = new Pap_Api_TransactionsGrid($session);
        $request->addFilter('orderid', Gpf_Data_Filter::LIKE, $order->getIncrementId().'(%');
        $request->setLimit(0, 900);
        try {
            $request->sendNow();
            $grid = $request->getGrid();
            $recordset = $grid->getRecordset();

            $ids = array();
            $refundIDs = array();
            $approveIDs = array();
            foreach($recordset as $record) {
                if (count($refunded)) {
                    if ($status == 'A') {
                        if (in_array($record->get('productid'), $refunded)) {
                            $refundIDs[] = $record->get('id');
                        }
                        else {
                            $approveIDs[] = $record->get('id');
                        }
                        continue;
                    }
                    elseif ($status == 'D') {
                        if (in_array($record->get('productid'), $refunded)) {
                            $refundIDs[] = $record->get('id');
                        }
                        continue;
                    }
                }
                $ids[] = $record->get('id');
            }
        }
        catch (Exception $e) {
            Mage::log('An API error while searching for the order with postfix: '.$e->getMessage());
            return false;
        }

        $transaction = new Pap_Api_Transaction($session);
        if (count($refundIDs) == 0 && count($approveIDs) == 0 && count($ids) == 0) {
            $items = $order->getAllVisibleItems();
            foreach ($items as $i => $item) {
                $productid = $item->getProductId();
                $product = Mage::getModel('catalog/product')->load($productid);

                $transaction->setOrderID($order->getIncrementId()."($i)");
                if ($status == $this->approved) {
                    if (count($refunded) && in_array($product->getSku(), $refunded)) { // if we are refunding only specific order items
                        $transaction->declineByOrderId('');
                        continue;
                    }
                    $transaction->approveByOrderId('');
                }
                if ($status == $this->declined) {
                    if (count($refunded) && !in_array($product->getSku(), $refunded)) { // if we are refunding only specific order items
                        continue;
                    }
                    $transaction->declineByOrderId('');
                }
            }
            return;
        }

        try {
            Mage::log('We will be changing status of IDs: '.print_r($ids,true));
            $request = new Gpf_Rpc_FormRequest('Pap_Merchants_Transaction_TransactionsForm', 'changeStatus', $session);
            if (!empty($refundIDs)) {
                $request->addParam('ids',new Gpf_Rpc_Array($refundIDs));
                $request->addParam('status','D');
                $request->sendNow();
            }
            if (!empty($approveIDs)) {
                $request->addParam('ids',new Gpf_Rpc_Array($approveIDs));
                $request->addParam('status','A');
                $request->sendNow();
            }
            $request->addParam('ids',new Gpf_Rpc_Array($ids));
            $request->addParam('status',$status);
            $request->sendNow();
            return true;
        }
        catch (Exception $e) {
            Mage::log('API error while status changing: '.$e->getMessage());
            return false;
        }
    }

    private function getStatus($state) {
        if ($state === Mage_Sales_Model_Order::STATE_PENDING_PAYMENT || $state === Mage_Sales_Model_Order::STATE_NEW || $state === Mage_Sales_Model_Order::STATE_PROCESSING) {
            return $this->pending;
        }
        if ($state === Mage_Sales_Model_Order::STATE_COMPLETE) {
            return $this->approved;
        }
        return $this->declined;
    }

    public function getOrderSaleDetails($order) {
        $config = Mage::getSingleton('pap/config');

        $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
        $couponcode = $quote->getCouponCode();

        $sales = array();
        $status = $this->getStatus($order->getState());

        if ($config->getPerProduct()) { // per product tracking
            $items = $order->getAllVisibleItems();

            foreach($items as $i=>$item) {
                $productid = $item->getProductId();
                $product = Mage::getModel('catalog/product')->load($productid);

                $sales[$i] = array();
                $subtotal = ($item->getBaseRowTotal() == '') ? $item->getBasePrice() : $item->getBaseRowTotal();
                $discount = abs($item->getBaseDiscountAmount());

                $sales[$i]['totalcost'] = $subtotal - $discount;
                $sales[$i]['orderid'] = $order->getIncrementId();
                $sales[$i]['productid'] = $product->getSku();
                $sales[$i]['couponcode'] = $couponcode;
                $sales[$i]['status'] = $status;

                for ($n = 1; $n < 6; $n++) {
                    if ($config->getData($n)) {
                        $sales[$i]['data'.$n] = $this->changeExtraData($config->getData($n), $order, $item, $product);
                    }
                }
            }
        }
        else { // per order tracking
            $sales[0] = array();

            $subtotal = $order->getBaseSubtotal();
            $discount = abs($order->getBaseDiscountAmount());

            $sales[0]['totalcost'] = $subtotal - $discount;
            $sales[0]['orderid'] = $order->getIncrementId();
            $sales[0]['productid'] = null;
            $sales[0]['couponcode'] = $couponcode;
            $sales[0]['status'] = $status;

            for ($n = 1; $n < 6; $n++) {
                if ($config->getData($n)) {
                    $sales[0]['data'.$n] = $this->changeExtraData($config->getData($n), $order, $item, $product);
                }
            }
        }

        return $sales;
    }

    public function registerOrderByID($orderid, $realid = true) { // called from the checkout observer
        $order = Mage::getModel('sales/order')->load($orderid);
        if ($realid) {
            $order->load($orderid);
        }
        else {
            $order->loadByIncrementId($orderid);
        }

        $this->registerOrder($order);
    }

    public function registerOrder($order, $visitorID = '') {
        if ($order) {
            $orderid = $order->getId();
        }
        else {
            Mage::log('Postaffiliatepro: Order empty');
            return false;
        }
        Mage::log("Postaffiliatepro: Loading details of order $orderid");

        $items = $this->getOrderSaleDetails($order);
        $this->registerSaleDetails($items, $visitorID);
    }

    public function registerSaleDetails($items, $visitorID = '') {
      $config = Mage::getSingleton('pap/config');
      $config->includePapAPI();

      $saleTracker = new Pap_Api_SaleTracker($config->getInstallationPath().'/scripts/sale.php');
      $saleTracker->setAccountId($config->getAccountID());
      if (!empty($visitorID)) {
          $saleTracker->setVisitorId($visitorID);
      }

      foreach ($items as $i => $item) {
          Mage::log('Postaffiliatepro: Registering sale '.$item['orderid']."($i)");

          $sale = $saleTracker->createSale();
          $sale->setTotalCost($item['totalcost']);
          $sale->setOrderID($item['orderid']."($i)");
          $sale->setProductID($item['productid']);
          $sale->setStatus($item['status']);
          if ($item['couponcode']) $sale->setCouponCode($item['couponcode']);
          if ($item['data1']) $sale->setData1($item['data1']);
          if ($item['data2']) $sale->setData2($item['data2']);
          if ($item['data3']) $sale->setData3($item['data3']);
          if ($item['data4']) $sale->setData4($item['data4']);
          if ($item['data5']) $sale->setData5($item['data5']);
      }

      $saleTracker->register();
    }

    public function changeExtraData($data, $order, $item, $product) {
        switch ($data) {
          case 'itemName':
              return (!empty($item)) ? $item->getName() : null;
              break;
          case 'itemQuantity':
              return (!empty($item)) ? $item->getQtyOrdered() : null;
              break;
          case 'itemPrice':
              if (!empty($item)) {
                  $rowtotal = $item->getBaseRowTotal();
                  if (empty($rowtotal)) {
                      return $item->getBasePrice();
                  }
                  return $rowtotal;
              }
              return null;
              break;
          case 'itemSKU':
              return (!empty($item)) ? $item->getSku() : null;
              break;
          case 'itemWeight':
              return (!empty($item)) ? $item->getWeight() : null;
              break;
          case 'itemWeightAll':
              return (!empty($item)) ? $item->getRowWeight() : null;
              break;
          case 'itemCost':
              return (!empty($item)) ? $item->getCost() : null;
              break;
          case 'itemDiscount':
              return (!empty($item)) ? abs($item->getBaseDiscountAmount()) : null;
              break;
          case 'itemDiscountPercent':
              return (!empty($item)) ? $item->getDiscountPercent() : null;
              break;
          case 'itemTax':
              return (!empty($item)) ? $item->getTaxAmount() : null;
              break;
          case 'itemTaxPercent':
              return (!empty($item)) ? $item->getTaxPercent() : null;
              break;
          case 'productCategoryID':
              return (!empty($product)) ? $product->getCategoryId() : null;
              break;
          case 'productURL':
              return (!empty($product)) ? $product->getProductUrl(false) : null;
              break;
          case 'storeID':
              return (!empty($order)) ? $order->getStoreId() : null;
              break;
          case 'internalOrderID':
              return (!empty($order)) ? $order->getId() : null;
              break;
          case 'customerID':
              return (!empty($order) && $order->getCustomerId()) ? $order->getCustomerId() : null;
              break;
          case 'customerEmail':
              return (!empty($order) && $order->getCustomerEmail()) ? $order->getCustomerEmail() : null;
              break;
          case 'customerName':
              $name = '';
              if (!empty($order)) {
                  $name = $order->getCustomerFirstname().' '.$order->getCustomerMiddlename().' '.$order->getCustomerLastname();
              }
              return (!empty($name)) ? $name : null;
              break;
          case 'couponCode':
              return (!empty($order) && $order->getQuoteId()) ? Mage::getModel('sales/quote')->load($order->getQuoteId())->getCouponCode() : null;
              break;
          default: return $data;
        }
    }
}
