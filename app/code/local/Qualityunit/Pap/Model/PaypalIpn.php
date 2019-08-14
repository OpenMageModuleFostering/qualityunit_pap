<?php
require_once BP.DS.'app/code/core/Mage/Paypal/Model/Ipn.php';

class Qualityunit_Pap_Model_PaypalIpn extends Mage_Paypal_Model_Ipn {

    protected function _registerPaymentCapture($skipFraudDetection = false) {
        try {
            Mage::helper('pap')->log('Postaffiliatepro: Loading PAP cookie from request');

            $pap = Mage::getModel('pap/pap');
            $visitorID = '';
            if (isset($this->_request['pap_custom']) && ($this->_request['pap_custom'] != '')) {
                $visitorID = $this->_request['pap_custom'];
            }

            $order = Mage::getModel('sales/order')->load($this->_request['custom']);

            Mage::helper('pap')->log("Postaffiliatepro: Starting registering sale for cookie '$visitorID'");
            if ($order == '') {
                $pap->registerOrder($this->_getOrder(), $visitorID);
            }
            else {
                $pap->registerOrder($order, $visitorID);
            }
            Mage::helper('pap')->log('Postaffiliatepro: Sale registered successfully');
        }
        catch (Exception $e) {
            Mage::helper('pap')->log('Postaffiliatepro: An error occurred while registering PayPal sale: '.$e->getMessage());
        }

        parent::_registerPaymentCapture($skipFraudDetection);
    }
}
