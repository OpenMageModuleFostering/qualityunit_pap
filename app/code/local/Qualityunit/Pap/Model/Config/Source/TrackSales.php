<?php
class Qualityunit_Pap_Model_Config_Source_TrackSales {
    public function toOptionArray() {
        return array(
            array('label'=>'JavaScript tracking', 'value'=>'javascript'),
            array('label'=>'API tracking', 'value'=>'api')
        );
    }
}
