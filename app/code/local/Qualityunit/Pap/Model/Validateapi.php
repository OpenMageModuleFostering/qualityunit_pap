<?php
class Qualityunit_Pap_Model_Validateapi extends Mage_Core_Model_Config_Data {

    public function save() {
        // validation here... try to connect to PAP and throw error if problem occurred
        try {
            Mage::getSingleton('pap/config')->includePapAPI();
            $config = Mage::getSingleton('pap/config');
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError('An error occurred: '.$e);
            return;
        }

        $server = str_replace('https', 'http', $this->getValue());
        $server = str_replace('http://', '', $server);
        if (substr($server,-1) == '/') {
            $server = substr($server,0,-1);
        }

        $url = 'http://'.$server.'/scripts/server.php';
        $username = $_POST['groups']['api']['fields']['username']['value'];
        $password = $_POST['groups']['api']['fields']['password']['value'];

        $session = new Gpf_Api_Session($url);
        if (!@$session->login($username, $password)) {
            Mage::getSingleton('adminhtml/session')->addError('Credential are probably not correct: '.$session->getMessage());
            return null;
        } else {
            Mage::getSingleton('core/session')->addSuccess('API Connection tested successfully!');
        }
        return parent::save(); // let's save it anyway
    }
}
