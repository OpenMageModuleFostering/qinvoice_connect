<?php
class Qinvoice_Connect_Model_Source_Relation
{
  public function toOptionArray()
  {
    return array(
      array('value' => 0, 'label' => 'No, do nothing'),
      array('value' => 1, 'label' => 'Save or update customer'),
    );
  }
}
?>
