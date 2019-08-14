<?php
class Qualityunit_Pap_Model_Config extends Mage_Core_Model_Config_Base {
    var $url;
    var $username;
    var $password;
    var $accountid;

    public function __construct() {
        $this->url = Mage::getStoreConfig('pap_config/api/url');
        $this->username = Mage::getStoreConfig('pap_config/api/username');
        $this->password = Mage::getStoreConfig('pap_config/api/password');
        $this->accountid = Mage::getStoreConfig('pap_config/api/accountid');

        $this->includePapAPI();
    }

    public function isConfigured() {
        if ($this->password != '') {
            return true;
        }
        return false;
    }

    public function getAPICredential($name) {
        switch ($name) {
            case 'username':
                return $this->username;
                break;
            case 'pass':
                return $this->password;
                break;
            case 'account':
                return $this->accountid;
                break;
        }
        return null;
    }

    public function includePapAPI() {
        include_once('PAP/PapApi.class.php');
        return true;
    }

    public function getInstallationPath() {
        $server = Mage::getStoreConfig('pap_config/api/url');
        if (!$server) {
            $server = $_SERVER['SERVER_NAME'];
        }

        // sanitize the URL
        $server = str_replace('https://', 'http://', $server);
        $server = str_replace('http://', '', $server);
        if (substr($server,-1) == '/') $server = substr($server,0,-1);

        return $server;
    }

    public function getAPIPath() {
        return 'http://'.$this->getInstallationPath().'/scripts/server.php';
    }

    public function getTrackingMethod() {
        return Mage::getStoreConfig('pap_config/tracking/trackingmethod');
    }

    public function getData($n) {
        return Mage::getStoreConfig('pap_config/tracking/data'.$n);
    }

    public function isPerProductEnabled() {
        if (Mage::getStoreConfigFlag('pap_config/tracking/perproduct')) {
            return true;
        }
        return false;
    }

    public function isClickTrackingEnabled() {
        if (Mage::getStoreConfigFlag('pap_config/tracking/trackclicks')) {
            return true;
        }
        return false;
    }

    public function isCouponTrackingEnabled() {
        if (Mage::getStoreConfigFlag('pap_config/tracking/coupontrack')) {
            return true;
        }
        return false;
    }

    public function isAutoStatusChangeEnabled() {
        if (Mage::getStoreConfigFlag('pap_config/tracking/autostatuschange')) {
            return true;
        }
        return false;
    }


    public function isCreateAffiliateEnabled() {
        if (Mage::getStoreConfigFlag('pap_config/affiliate/createaff')) {
            return true;
        }
        return false;
    }

    public function getCreateAffiliateProducts() {
        $products = Mage::getStoreConfig('pap_config/affiliate/createaffproducts');
        if ($products == '' || $products == null) {
            return array();
        }
        if (strpos($products, ',') != false) {
            $products = str_replace(', ', ',', $products);
            $products = str_replace(' ,', ',', $products);
        }
        return explode(',',$products);
    }
}
