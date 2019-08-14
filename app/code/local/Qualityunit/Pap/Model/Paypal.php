<?php
require_once BP.DS.'app/code/core/Mage/Paypal/Model/Standard.php';

class Qualityunit_Pap_Model_Paypal extends Mage_Paypal_Model_Standard {

    public function ipnPostSubmit() {
        try {
            $pap = Mage::getModel('pap/pap');
            $postData = $this->getIpnFormData();
            $visitorID = $_GET['pap_custom'];

            $sReq = '';
            foreach($this->getIpnFormData() as $k=>$v) {
                $sReq .= '&'.$k.'='.urlencode(stripslashes($v));
            }
            //append ipn commdn
            $sReq .= '&cmd=_notify-validate';
            $sReq = substr($sReq, 1);

            $http = new Varien_Http_Adapter_Curl();
            $http->write(Zend_Http_Client::POST,$this->getPaypalUrl(), '1.1', array(), $sReq);
            $response = $http->read();
            $response = preg_split('/^\r?$/m', $response, 2);
            $response = trim($response[1]);

            if ($response=='VERIFIED') {
                $order = Mage::getModel('sales/order')->loadByIncrementId($_POST['custom']);
                $pap->registerOrder($order, isset($visitorID) ? $visitorID : null);
            }
        }
        catch (Exception $e) {
            Mage::log('Postaffiliatepro: Exception while trying to log PayPal sale: '.$e->getMessage()."\n");
        }

        parent::ipnPostSubmit();
    }
}
