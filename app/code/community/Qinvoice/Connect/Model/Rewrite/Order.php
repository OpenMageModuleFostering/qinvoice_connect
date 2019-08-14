<?php

class Qinvoice_Connect_Model_Rewrite_Order extends Mage_Sales_Model_Order{
	public function _setState($state, $status = false, $comment = '', $isCustomerNotified = null, $shouldProtectState = false){
	    // attempt to set the specified state
	    // tenta definir o status especificado
	    if ($shouldProtectState) {
	        if ($this->isStateProtected($state)) {
	            Mage::throwException(
	                Mage::helper('sales')->__('The Order State "%s" must not be set manually.', $state)
	            );
	        }
	    }
	    $this->setData('state', $state);

	    // add status history
	    if ($status) {
	        if ($status === true) {
	            $status = $this->getConfig()->getStateDefaultStatus($state);
	        }
	        $this->setStatus($status);
	        $history = $this->addStatusHistoryComment($comment, false); // no sense to set $status again
	        $history->setIsCustomerNotified($isCustomerNotified); // for backwards compatibility
	    }

	 	// echo 'hier';

	    Mage::dispatchEvent('qinvoice_connect_order_status_change', array('order' => $this, 'state' => $state, 'status' => $status, 'comment' => $comment, 'isCustomerNotified' => $isCustomerNotified));              
	    // Mage::log("Qinvoice_Connect_Model_Rewrite_Order Changing order to STATE ".$state." STATUS ".$status);

	    // exit('exit');
	    return $this;
	}
}

?>