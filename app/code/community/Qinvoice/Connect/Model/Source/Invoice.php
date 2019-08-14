<?php
class Qinvoice_Connect_Model_Source_Invoice
{
  public function toOptionArray()
  {
    return array(
      array('value' => 0, 'label' => 'Save invoice as concept'),
      array('value' => 1, 'label' => 'Finalize invoice'),
      array('value' => 2, 'label' => 'Finalize and send via email')
    );
  }
}
?>
