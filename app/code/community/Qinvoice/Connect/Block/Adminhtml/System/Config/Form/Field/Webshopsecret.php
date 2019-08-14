<?php

class Qinvoice_Connect_Block_Adminhtml_System_Config_Form_Field_Webshopsecret extends Mage_Adminhtml_Block_System_Config_Form_Field{
    protected function _getElementHtml($element) {
        if ($element->getValue() == '' || 1==1) {
        	$store = Mage::app()->getStore();
        	$value = md5( $store->getName() . $store->getHomeUrl() );
            $element->setValue( $value );
            Mage::getConfig()->saveConfig('invoice_options/invoice/webshop_secret', $value, 'default');

        }
        $element->setDisabled('disabled');
        return parent::_getElementHtml($element);
    }
}
?>