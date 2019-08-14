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

	    Mage::log("Store ID: ". $this->getStoreId());

	    $varActionPath = 'invoice_options/invoice/invoice_trigger';
        $trigger = Mage::getStoreConfig($varActionPath,$this->getStoreId()); 

        Mage::log("Config: ". $trigger);


	    // GETTING TRIGGER SETTING
        $db = Mage::getSingleton('core/resource')->getConnection('core_write');             
        $varPath = 'invoice_options/invoice/api_username';
        $prefix = Mage::getConfig()->getTablePrefix();
        $resultTwo = $db->query("SELECT value FROM {$prefix}core_config_data WHERE path LIKE '".$varPath."'");
        $rowTwo = $resultTwo->fetch(PDO::FETCH_ASSOC);
        $varOnOrder = $rowTwo['value'];

        if($varOnOrder == 'complete' && $order->getState() == Mage_Sales_Model_Order::STATE_COMPLETE){
            $this->createInvoiceForQinvoice($order->getId(), false);
        }else{
            return true;
        }

	    switch($state){
	    	case 'complete': // shipped?
	    		if($trigger == 'complete'){
	    			// send request
	    		}
	    	break;
	    	case 'processing': // after payment?
	    		if($trigger == 'payment'){
	    			// send request
	    		}
	    		// update invoice
	    	break;
	    	case 'new': // new order
	    		if($trigger == 'order'){
	    			// send request
	    		}
	    	break;
	    }

	    Mage::dispatchEvent('qinvoice_connect_order_status_change', array('order' => $this, 'state' => $state, 'status' => $status, 'comment' => $comment, 'isCustomerNotified' => $isCustomerNotified));              
	    Mage::log("Qinvoice_Connect_Model_Rewrite_Order Changing order to STATE ".$state." STATUS ".$status);
	    return $this;
	}
}

?>