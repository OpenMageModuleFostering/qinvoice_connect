<?php
class Qinvoice_Connect_Module_Block_Webshop_Secret_Renderer extends Mage_Adminhtml_Block_System_Config_Form_Field{
    protected function _getElementHtml($element) {
        if (1==1) {
            $element->setDisabled('disabled');
        }
        return parent::_getElementHtml($element);
    }
}
?>