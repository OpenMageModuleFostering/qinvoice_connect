<?php
class Qinvoice_Connect_Model_Source_Calculationmethod
{
  public function toOptionArray()
  {
    return array(
      array('value' => 'excl', 'label' => 'Prices without VAT are leading'),
      array('value' => 'incl', 'label' => 'Prices with VAT included are leading')
    );
  }
}
?>
