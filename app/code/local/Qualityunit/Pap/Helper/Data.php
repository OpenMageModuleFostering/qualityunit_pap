<?php
class Qualityunit_Pap_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Check if the module is enabled in configuration
     *
     * @return boolean
     */
    public function enabled()
    {
        return (bool)Mage::getStoreConfigFlag('pap/general/active');
    }

    /**
     * Get data from configuration
     *
     * @param string $field
     * @return string
     */
    public function config($field)
    {
        return Mage::getStoreConfig('pap/general/' . $field);
    }

    public function pushVars($customer)
    {

        if (Mage::helper('monkey')->canMonkey() && $customer->getId()) {

            $mergeVars = Mage::helper('monkey')->getMergeVars($customer, TRUE);
            $api = Mage::getSingleton('monkey/api', array('store' => $customer->getStoreId()));

            $lists = $api->listsForEmail($customer->getEmail());

            if (is_array($lists)) {
                foreach ($lists as $listId) {
                    $api->listUpdateMember($listId, $customer->getEmail(), $mergeVars);
                }
            }

        }
    }

}