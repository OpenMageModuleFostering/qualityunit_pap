<?php
class Qualityunit_Pap_Model_Config_Source_CustomVariables {
    public function toOptionArray() {
        return array(
            array('label'=>' ', 'value'=>'empty'),
            array('label'=>'Item name', 'value'=>'itemName'),
            array('label'=>'Item quantity', 'value'=>'itemQuantity'),
            array('label'=>'Item price (multiplied by quantity, before discount)', 'value'=>'itemPrice'),
            array('label'=>'Item SKU', 'value'=>'itemSKU'),
            array('label'=>'Item weight (single item)', 'value'=>'itemWeight'),
            array('label'=>'Item weight (multiplied by quantity)', 'value'=>'itemWeightAll'),
            array('label'=>'Item cost (multiplied by quantity)', 'value'=>'itemCost'),
            array('label'=>'Item discount (value)', 'value'=>'itemDiscount'),
            array('label'=>'Item discount (percent)', 'value'=>'itemDiscountPercent'),
            array('label'=>'Item total tax (value)', 'value'=>'itemTax'),
            array('label'=>'Item total tax (percent)', 'value'=>'itemTaxPercent'),
            array('label'=>'Product category ID', 'value'=>'productCategoryID'),
            array('label'=>'Product URL', 'value'=>'productURL'),
            array('label'=>'Store ID', 'value'=>'storeID'),
            array('label'=>'Internal order ID', 'value'=>'internalOrderID'),
            array('label'=>'Customer ID', 'value'=>'customerID'),
            array('label'=>'Customer email', 'value'=>'customerEmail'),
            array('label'=>'Customer name', 'value'=>'customerName'),
            array('label'=>'Coupon code', 'value'=>'couponCode')
        );
    }
}