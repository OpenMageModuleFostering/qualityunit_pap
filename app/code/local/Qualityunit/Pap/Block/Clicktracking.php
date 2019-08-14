<?php
class Qualityunit_Pap_Block_Clicktracking extends Mage_Core_Block_Text {
    protected function _toHtml() {
        $config = Mage::getSingleton('pap/config');
        if (!$config->isConfigured()) {
            Mage::log('Postaffiliatepro: The module is still not configured!');
            return '';
        }

        $url = $config->getInstallationPath();
        $accountID = $config->getAPICredential('account');

        if ($url == '') {
            $this->addText('<!-- Post Affiliate Pro plugin has not been configured yet! -->');
        }
        else {
            $this->addText('
                <!-- Post Affiliate Pro integration snippet -->
                <script type="text/javascript">
                  document.write(unescape("%3Cscript id=\'pap_x2s6df8d\' src=\'" + (("https:" == document.location.protocol) ? "https://" : "http://") +
                  "'.$url.'/scripts/trackjs.js\' type=\'text/javascript\'%3E%3C/script%3E"));
                </script>
                <script type="text/javascript">
                PostAffTracker.setAccountId(\''.$accountID.'\');
                try {
                  PostAffTracker.track();
                } catch (err) { }
                </script>
                <!-- /Post Affiliate Pro integration snippet -->
            ');
        }
        
        return parent::_toHtml();
    }
}