<?php
class Qinvoice_Connect_Model_Source_Trigger
{
  public function toOptionArray()
  {
    return array(
      array('value' => 'order', 'label' =>'On every order'),
      array('value' => 'payment', 'label' => 'Only on successful payment'),
      array('value' => 'complete', 'label' => 'When order is marked complete')
    );
  }
}
?>
