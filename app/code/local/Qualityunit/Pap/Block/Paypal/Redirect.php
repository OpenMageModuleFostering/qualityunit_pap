<?php
class Qualityunit_Pap_Block_Paypal_Redirect extends Mage_Core_Block_Abstract {
    protected function _toHtml() {
        $standard = Mage::getModel('paypal/standard');
        
        $form = new Varien_Data_Form();
        $form->setAction($standard->getConfig()->getPaypalUrl())
            ->setId('paypal_standard_checkout')
            ->setName('paypal_standard_checkout')
            ->setMethod('POST')
            ->setUseContainer(true);
        foreach ($standard->getStandardCheckoutFormFields() as $field=>$value) {
            if ($field == 'notify_url') {
                $form->addField($field, 'hidden', array('name'=>$field, 'id'=>'pap_ab78y5t4a', 'value'=>$value)); // html_id
            }
            else {
                $form->addField($field, 'hidden', array('name'=>$field, 'value'=>$value));
            }
        }
        // custom field with order ID
        $orderIncrementId = $standard->getCheckout()->getLastRealOrderId();
        $form->addField('custom', 'hidden', array('name'=>'custom', 'value'=>$orderIncrementId));

        $idSuffix = Mage::helper('core')->uniqHash();
        $submitButton = new Varien_Data_Form_Element_Submit(array(
            'value' => $this->__('Click here if you are not redirected within 10 seconds...'),
        ));
        $id = "submit_to_paypal_button_{$idSuffix}";
        $submitButton->setId($id);
        $form->addElement($submitButton);
        $html = '<html><body>';
        $html.= $this->__('You will be redirected to the PayPal website in a few seconds.');
        $html.= $form->toHtml();
        $html.= '<script type="text/javascript">setTimeout("document.getElementById(\'paypal_standard_checkout\').submit()", 3000);</script>';

        $config = Mage::getSingleton('pap/config');
        $url = $config->getInstallationPath();
        $accountID = $config->getAPICredential('account');

        $html.= '
        <!-- Post Affiliate Pro integration snippet -->
        <script type="text/javascript"><!--
          document.write(unescape("%3Cscript id=\'pap_x2s6df8d\' src=\'" + (("https:" == document.location.protocol) ? "https://" : "http://") +
          "'.$url.'/scripts/trackjs.js\' type=\'text/javascript\'%3E%3C/script%3E"));//-->
        </script>
        <script type="text/javascript">'.
        "PostAffTracker.setAccountId('".$accountID."');
        PostAffTracker.writeCookieToCustomField('pap_ab78y5t4a', '', 'pap_custom');
        PostAffTracker.writeCookieToCustomField('notify_url', '', 'pap_custom');
        </script>
        <!-- /Post Affiliate Pro integration snippet -->";
        $html.= '</body></html>';

        return $html;
    }
}