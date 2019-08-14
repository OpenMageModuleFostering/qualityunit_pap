<?php
class Qualityunit_Pap_Block_SaleTracking extends Mage_Core_Block_Text {
    protected function _toHtml() {
        $config = Mage::getSingleton('pap/config');
        if (!$config->isConfigured()) {
            Mage::helper('pap')->log('Postaffiliatepro: The module is still not configured!');
            return '';
        }

        if ($config->getTrackingMethod() != 'javascript') {
            Mage::helper('pap')->log('Postaffiliatepro: JavaScript tracking not allowed.');
            return '';
        }

        $quote = $this->getQuote();

        if ($quote) {
            if ($quote instanceof Mage_Sales_Model_Quote) {
                $quoteId = $quote->getId();
            }
            else {
                $quoteId = $quote;
            }
        }
        else {
            $quoteId = Mage::getSingleton('checkout/session')->getLastQuoteId();
        }

        if (empty($quoteId)) {
            Mage::helper('pap')->log('Postaffiliatepro: Could not find quote ID for order details.');
            return '';
        }

        $orders = Mage::getResourceModel('sales/order_collection')->addAttributeToFilter('quote_id', $quoteId)->load();
        $accountID = $config->getAPICredential('account');
        $sale_tracker = '
          <script type="text/javascript">'.
          "PostAffTracker.setAccountId('$accountID');\n";

        foreach ($orders as $order) {
            if (!$order) continue;

            if (!($order instanceof Mage_Sales_Model_Order)) {
                $order = Mage::getModel('sales/order')->load($order);
                if (!$order) continue;
            }

            Mage::getModel('pap/pap')->createAffiliate($order);

            $items = Mage::getModel('pap/pap')->getOrderSaleDetails($order);
            foreach ($items as $i => $item) {
                $sale_tracker .= "
                    sale$i = PostAffTracker.createSale();\n";
                $sale_tracker .= "
                    sale$i.setTotalCost('".$item['totalcost']."');
                    sale$i.setOrderID('".$item['orderid']."($i)');
                    sale$i.setProductID('".$item['productid']."');
                    sale$i.setStatus('".$item['status']."');
                    sale$i.setCurrency('".(Mage::app()->getStore()->getBaseCurrencyCode())."');\n";

                if (!empty($item['campaignid'])) $sale_tracker .= "sale$i.setCampaignID('".$item['campaignid']."');\n";
                if (!empty($item['data1'])) $sale_tracker .= "sale$i.setData1('".$item['data1']."');\n";
                if (!empty($item['data2'])) $sale_tracker .= "sale$i.setData2('".$item['data2']."');\n";
                if (!empty($item['data3'])) $sale_tracker .= "sale$i.setData3('".$item['data3']."');\n";
                if (!empty($item['data4'])) $sale_tracker .= "sale$i.setData4('".$item['data4']."');\n";
                if (!empty($item['data5'])) $sale_tracker .= "sale$i.setData5('".$item['data5']."');\n";

                if ($config->isCouponTrackingEnabled()) $sale_tracker .= "sale$i.setCoupon('".$item['couponcode']."');\n";

                $sale_tracker .= '
                    PostAffTracker.register();';
            }
        }

        $url = $config->getInstallationPath();
        $this->addText('
            <!-- Post Affiliate Pro integration snippet -->
            <script type="text/javascript">
              document.write(unescape("%3Cscript id=\'pap_x2s6df8d\' src=\'" + (("https:" == document.location.protocol) ? "https://" : "http://") +
              "'.$url.'/scripts/trackjs.js\' type=\'text/javascript\'%3E%3C/script%3E"));
            </script>'.
            $sale_tracker.
            '</script>
            <!-- Post Affiliate Pro integration snippet -->
        ');

        return parent::_toHtml();
    }
}
