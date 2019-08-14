<?php
class Qualityunit_Pap_Helper_Data extends Mage_Core_Helper_Abstract {
    /**
     * Check if the module is enabled in configuration
     *
     * @return boolean
     */
    public function enabled() {
        return (bool)Mage::getStoreConfigFlag('pap/general/active');
    }

    /**
     * Get data from configuration
     *
     * @param string $field
     * @return string
     */
    public function config($field) {
        return Mage::getStoreConfig('pap/general/' . $field);
    }

    /**
     * Write a log message
     *
     * @param mixed $message
     * @return Mage_Core_Model_Log_Adapter
     */
    public function log($message) {
        //return Mage::getModel('core/log_adapter', 'PostAffiliatePro.log')->log($message);
        Mage::log($message, null, 'PostAffiliatePro.log');
        return;
    }
}