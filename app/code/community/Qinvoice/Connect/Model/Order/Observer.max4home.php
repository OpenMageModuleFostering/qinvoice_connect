<?php
//class NameSpaceName_ModuleName_Model_ObserverDir_Observer
class Qinvoice_Connect_Model_Order_Observer
{
    public function __construct()
    {
        //parent::__construct();
    }

    public function sendOnComplete($observer){
        return false;
        $order = $observer->getEvent()->getOrder();



        // GETTING TRIGGER SETTING
        $db = Mage::getSingleton('core/resource')->getConnection('core_write');             
        $varPath = 'invoice_options/invoice/invoice_trigger';
        $prefix = Mage::getConfig()->getTablePrefix();
        $resultTwo = $db->query("SELECT value FROM {$prefix}core_config_data WHERE path LIKE '".$varPath."'");
        $rowTwo = $resultTwo->fetch(PDO::FETCH_ASSOC);
        $varOnOrder = $rowTwo['value'];

        if($varOnOrder == 'complete' && $order->getState() == Mage_Sales_Model_Order::STATE_COMPLETE){
            $this->createInvoiceForQinvoice($order->getId(), false);
        }else{
            return true;
        }
    }

    public function sendOnShip($observer){
        return false;
        $shipment = $observer->getEvent()->getShipment(); 

        $order = $shipment->getOrder(); 

        // GETTING TRIGGER SETTING
        $db = Mage::getSingleton('core/resource')->getConnection('core_write');             
        $varPath = 'invoice_options/invoice/invoice_trigger';
        $prefix = Mage::getConfig()->getTablePrefix();
        $resultTwo = $db->query("SELECT value FROM {$prefix}core_config_data WHERE path LIKE '".$varPath."'");
        $rowTwo = $resultTwo->fetch(PDO::FETCH_ASSOC);
        $varOnOrder = $rowTwo['value'];

        if($varOnOrder == 'ship'){
            $this->createInvoiceForQinvoice($order->getId(), false);
        }else{
            return true;
        }
    }

    public function sendOnOrder($observer){
        $order = $observer->getEvent()->getOrder(); 

        // GETTING TRIGGER SETTING
        $db = Mage::getSingleton('core/resource')->getConnection('core_write');             
        $varPath = 'invoice_options/invoice/invoice_trigger';
        $prefix = Mage::getConfig()->getTablePrefix();
        $resultTwo = $db->query("SELECT value FROM {$prefix}core_config_data WHERE path LIKE '".$varPath."'");
        $rowTwo = $resultTwo->fetch(PDO::FETCH_ASSOC);
        $varOnOrder = $rowTwo['value'];

        if($varOnOrder == 'order'){
            $this->createInvoiceForQinvoice($order->getId(), false);
        }else{
            return true;
        }
    }

    public function sendOnPayment($observer){
        // Gets called even when other payment method is choosen.
        
        $order_ids = $observer->getEvent()->getOrderIds(); 
        $order = $observer->getEvent()->getOrder(); 

        // GETTING TRIGGER SETTING
        $db = Mage::getSingleton('core/resource')->getConnection('core_write');             
        $varPath = 'invoice_options/invoice/invoice_trigger';
        $prefix = Mage::getConfig()->getTablePrefix();
        $resultTwo = $db->query("SELECT value FROM {$prefix}core_config_data WHERE path LIKE '".$varPath."'");
        $rowTwo = $resultTwo->fetch(PDO::FETCH_ASSOC);
        $varOnOrder = $rowTwo['value'];

        if($varOnOrder == 'payment'){
            $this->createInvoiceForQinvoice($order_ids[0], true);
        }else{
            return true;
        }        
    }
    public function createInvoiceForQinvoice($varOrderID,$ifPaid = false)
    {


        $paid = 0;
        $db = Mage::getSingleton('core/resource')->getConnection('core_write'); 
        // GETTING ORDER ID
        //$resultOne = $db->query("SELECT max(entity_id) as LastOrderID FROM sales_flat_order");
        //$rowOne = $resultOne->fetch(PDO::FETCH_ASSOC);
            
        //$varOrderID = $rowOne['LastOrderID'];
        
        $varCurrenyCode =  Mage::app()->getStore()->getCurrentCurrency()->getCode();
        // GETTING ORDER STATUS
        $prefix = Mage::getConfig()->getTablePrefix();
        $resultOne = $db->query("SELECT entity_id, status, customer_email, coupon_code, base_currency_code, shipping_description, shipping_amount, shipping_tax_amount, increment_id, grand_total, total_paid, billing_address_id, shipping_address_id, customer_taxvat, base_subtotal FROM {$prefix}sales_flat_order WHERE entity_id=".$varOrderID);
        $rowOne = $resultOne->fetch(PDO::FETCH_ASSOC);
        
        
        if($rowOne['status'] == 'processing' || $rowOne['status'] == 'complete' || $rowOne['total_paid'] == $rowOne['grand_total'])
        {
            $varStatus = 'Paid';
            // GETTING API URL
            $varURLPath = 'invoice_options/invoice/paid_remark';
            $prefix = Mage::getConfig()->getTablePrefix();
            $resultURL = $db->query("SELECT value FROM {$prefix}core_config_data WHERE path LIKE '".$varURLPath."'");
            $rowURL = $resultURL->fetch(PDO::FETCH_ASSOC);
            $paid_remark = $rowURL['value'];
            $paid = 1;
        }
        else
        {
            if($ifPaid == true){
                // cancel if invoice has to be paid
                return;
            }
            $paid_remark = '';
            $varStatus = 'Sent';
        }
        
        $prefix = Mage::getConfig()->getTablePrefix();
        $result = $db->query("SELECT item_id, product_type, product_id, product_options, order_id, sku, name, description, qty_ordered, base_price, original_price, tax_percent, tax_amount, base_discount_amount FROM {$prefix}sales_flat_order_item WHERE order_id=".$varOrderID." AND parent_item_id IS NULL  HAVING (order_id > 0) ORDER BY item_id desc");
        

        if(!$result) {
            //return false;
        }
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $arrData[] = $row;
            }
        if(!$arrData) {
            //return false;
        }
        //$comment = '';
        //$comment = $data['comment_text'];
        // getting po_number
        $random_number = rand(0, pow(10, 7));

        // GETTING API USERNAME
        $varPath = 'invoice_options/invoice/api_username';
        $prefix = Mage::getConfig()->getTablePrefix();
        $resultTwo = $db->query("SELECT value FROM {$prefix}core_config_data WHERE path LIKE '".$varPath."'");
        $rowTwo = $resultTwo->fetch(PDO::FETCH_ASSOC);
        $username = $rowTwo['value'];

        // GETTING API PASSWORD
        $varPath = 'invoice_options/invoice/api_password';
        $prefix = Mage::getConfig()->getTablePrefix();
        $resultTwo = $db->query("SELECT value FROM {$prefix}core_config_data WHERE path LIKE '".$varPath."'");
        $rowTwo = $resultTwo->fetch(PDO::FETCH_ASSOC);
        $password = $rowTwo['value'];

        // GETTING LAYOUT CODE
        $varPath = 'invoice_options/invoice/layout_code';
        $prefix = Mage::getConfig()->getTablePrefix();
        $resultTwo = $db->query("SELECT value FROM {$prefix}core_config_data WHERE path LIKE '".$varPath."'");
        $rowTwo = $resultTwo->fetch(PDO::FETCH_ASSOC);
        $layout_code = $rowTwo['value'];

        
        // GETTING CLIENT DETAILS
        $prefix = Mage::getConfig()->getTablePrefix();
        $resultThree = $db->query("SELECT * FROM {$prefix}sales_flat_order_address WHERE entity_id='".$rowOne['billing_address_id']."'");
        $rowThree = $resultThree->fetch(PDO::FETCH_ASSOC);

        $invoice = new qinvoice($username,$password);

        $invoice->companyname = $rowThree['company'];       // Your customers company name
        $invoice->firstname = $rowThree['firstname'];       // Your customers contact name
        $invoice->lastname = $rowThree['lastname'];       // Your customers contact name
        $invoice->email = $rowOne['customer_email'];                // Your customers emailaddress (invoice will be sent here)
        $invoice->phone = $rowThree['telephone'];
        $invoice->address = $rowThree['street'];                // Self-explanatory
        $invoice->zipcode = $rowThree['postcode'];              // Self-explanatory
        $invoice->city = $rowThree['city'];                     // Self-explanatory
        $invoice->country = $rowThree['country_id'];                 // 2 character country code: NL for Netherlands, DE for Germany etc
        $invoice->vatnumber = strlen($rowThree['vat_id']) > 3 ? $rowThree['vat_id'] : $rowOne['customer_taxvat'];  

        $prefix = Mage::getConfig()->getTablePrefix();
        $resultFour = $db->query("SELECT firstname, lastname, company, email, telephone, street, city, region, postcode, country_id FROM {$prefix}sales_flat_order_address WHERE entity_id='".$rowOne['shipping_address_id']."'");
        $rowFour = $resultFour->fetch(PDO::FETCH_ASSOC);

        $invoice->delivery_companyname = $rowFour['company'];       // Your customers company name
        $invoice->delivery_firstname = $rowFour['firstname'];       // Your customers contact name
        $invoice->delivery_lastname = $rowFour['lastname'];       // Your customers contact name
        $invoice->delivery_address = $rowFour['street'];                // Self-explanatory
        $invoice->delivery_zipcode = $rowFour['postcode'];              // Self-explanatory
        $invoice->delivery_city = $rowFour['city'];                     // Self-explanatory
        $invoice->delivery_country = $rowFour['country_id'];
        $invoice->delivery_phone = $rowFour['telephone'];
        $invoice->delivery_email = $rowFour['email'];      

        $invoice->vat = '';                     // Self-explanatory
        $invoice->paid = $paid;

        $varActionPath = 'invoice_options/invoice/save_relation';
        $prefix = Mage::getConfig()->getTablePrefix();
        
        $resultAction = $db->query("SELECT value FROM {$prefix}core_config_data WHERE path LIKE '".$varActionPath."'");
        $rowAction = $resultAction->fetch(PDO::FETCH_ASSOC);
        $save_relation = $rowAction['value'];

        $invoice->saverelation = $save_relation;

        $varRemarkPath = 'invoice_options/invoice/invoice_remark';
        $prefix = Mage::getConfig()->getTablePrefix();
        $resultRemark = $db->query("SELECT value FROM {$prefix}core_config_data WHERE path LIKE '".$varRemarkPath."'");
        $rowRemark = $resultRemark->fetch(PDO::FETCH_ASSOC);
        
        $order_id = $rowOne['increment_id'];
        $invoice_remark = $rowRemark['value'];
        $invoice_remark = str_replace('{order_id}',$rowOne['increment_id'],$invoice_remark);
        $invoice_remark = str_replace('{shipping_description}',$rowOne['shipping_description'],$invoice_remark);

        $order2 = Mage::getModel('sales/order')->load($order_id);

        $checkout = Mage::getSingleton('checkout/type_onepage')->getCheckout();
        $dd_data = $checkout->getAmastyDeliveryDate();

        if(method_exists($order2, 'deliverydate') ){
            
            $dd_comment = $dd_data['comment'];
            $invoice_remark = str_replace('{dd_comment}',$dd_comment,$invoice_remark);
            
            $dd_date = $dd_data['date'];
            $invoice_remark = str_replace('{dd_date}',$dd_date,$invoice_remark);
            
            $dd_time = $dd_data['time'];
            $invoice_remark = str_replace('{dd_time}',$dd_time,$invoice_remark);
            
            
            //$dd = explode("-",$order->deliverydate('date'));
            //$invoice->delivery_date = $order2->deliverydate('date'); //$dd[2].'-'. $dd[1].'-'. $dd[0];
            $invoice->delivery_date = $dd_data['date'];
        }

        $invoice->addTag($dd_data['date']);
        if(strlen($dd_time) > 6){
          $invoice->addTag($dd_data['time']);
        }

        // $payment_method_code = $order2->getPayment()->getMethodInstance()->getCode(); 
        // $invoice->addTag($payment_method_code);
        // $invoice->addTag($order2->getShippingDescription());

        //$payment = $order2->getPayment();
        //$invoice->addTag($payment->getMethod());
        $invoice->addTag(Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getShippingMethod());
        $invoice->addTag(Mage::getSingleton('checkout/session')->getQuote()->getPayment()->getMethodInstance()->getTitle());
        

        //$invoice->addTag(Mage::getSingleton('checkout/session')->getQuote()->getInvoiceFee());
        

        $invoice->remark = $invoice_remark ."\n". $paid_remark;

        $varActionPath = 'invoice_options/invoice/invoice_action';
        $prefix = Mage::getConfig()->getTablePrefix();
        $resultAction = $db->query("SELECT value FROM {$prefix}core_config_data WHERE path LIKE '".$varActionPath."'");
        $rowAction = $resultAction->fetch(PDO::FETCH_ASSOC);
        $invoice_action = $rowAction['value'];

        $varLayoutPath = 'invoice_options/invoice/layout_code';
        $prefix = Mage::getConfig()->getTablePrefix();
        $resultLayout = $db->query("SELECT value FROM {$prefix}core_config_data WHERE path LIKE '".$varLayoutPath."'");
        $rowLayout = $resultLayout->fetch(PDO::FETCH_ASSOC);
        $invoice_layout = $rowLayout['value'];

        $varCalculationmethodPath = 'invoice_options/invoice/calculation_method';
        $prefix = Mage::getConfig()->getTablePrefix();
        $resultCalculationmethod = $db->query("SELECT value FROM {$prefix}core_config_data WHERE path LIKE '".$varCalculationmethodPath."'");
        $rowCalculationmethod = $resultCalculationmethod->fetch(PDO::FETCH_ASSOC);
        $invoice_calculationmethod = $rowCalculationmethod['value'];

        $invoice->calculation_method = $invoice_calculationmethod;

        $invoice_layout_s = @unserialize($invoice_layout);
        if ($invoice_layout_s !== false) {
            // serialized
            $invoice_layout = @unserialize($invoice_layout);
            if(isset($invoice_layout[$rowFour['country_id']])){
                $invoice_layout = @$invoice_layout[$rowFour['country_id']];    
            }else{
                $invoice_layout = @$invoice_layout['default'];
            }
        } else {
            // not serialized
            $invoice_layout = $invoice_layout;
        }

        $invoice->setLayout($invoice_layout);

        $varTagPath = 'invoice_options/invoice/invoice_tag';
        $prefix = Mage::getConfig()->getTablePrefix();
        $resultTag = $db->query("SELECT value FROM {$prefix}core_config_data WHERE path LIKE '".$varTagPath."'");
        $rowTag = $resultTag->fetch(PDO::FETCH_ASSOC);
        $invoice_tag = $rowTag['value'];

        $varTagPath = 'invoice_options/invoice/product_attributes';
        $prefix = Mage::getConfig()->getTablePrefix();
        $resultTag = $db->query("SELECT value FROM {$prefix}core_config_data WHERE path LIKE '".$varTagPath."'");
        $pa_array = $resultTag->fetch(PDO::FETCH_ASSOC);
        

        $invoice->action = $invoice_action;

        // OPTIONAL: Add tags
        $invoice->addTag($rowOne['increment_id']);
        $invoice->addTag($invoice_tag);



        $order = Mage::getModel('sales/order')->loadByIncrementId($varOrderID);
        $store_id = $order->getStoreId();

      
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')->addVisibleFilter();
        $attributeArray = array();

        foreach($attributes as $attribute){
                $attributeArray[$attribute->getData('attribute_code')] = $attribute->getData('frontend_label');
                // $attributeArray[] = array(
                //     'label' => $attribute->getData('frontend_label'),
                //     'value' => $attribute->getData('attribute_code')
                // );
        }


       //print_r($order);

        for($i=0;$i<count($arrData);$i++)
        {
            $category = '';
            $_productId = $arrData[$i]['product_id'];
            $_product = Mage::getModel('catalog/product')->load($_productId);


            $category = $_product->getData('qinvoice_category');
            $productcode = $_product->getData('qinvoice_productcode');

            $arrItemOptions = unserialize($arrData[$i]['product_options']);

            $varDescription = '';

           //print_r();

            $product_attributes = explode(",",$pa_array['value']);
            foreach($product_attributes as $pa){
                if(isset($_product[$pa]))
                {
                    $varDescription .= "\n". $attributeArray[$pa] .': '. $_product[$pa];
                }
            }

            
            if(@$arrItemOptions['options'])
            {
                for($k=0; $k <count($arrItemOptions['options']); $k++)
                {
                    $varDescription .= "\n".$arrItemOptions['options'][$k]['label'].": ".$arrItemOptions['options'][$k]['print_value']."\n";
                }
            }
            
            if(@$arrItemOptions['attributes_info'])
            {
                for($k=0; $k <count($arrItemOptions['attributes_info']); $k++)
                {
                    $varDescription .= "\n".$arrItemOptions['attributes_info'][$k]['label'].": ".$arrItemOptions['attributes_info'][$k]['value']."\n";
                }
            }

            if(@$arrItemOptions['bundle_options'])
            {
                foreach($arrItemOptions['bundle_options'] as $option){
                    foreach($option['value'] as $value){
                       $varDescription .= "\n".'['. $option['label'] .'] '. $value['qty'] .' x '. $value['title'];
                    }
                } 
            }


          
        

           
            $params = array(    
                'code' => $productcode,
                'description' => "[".$arrData[$i]['sku']."] ".trim($arrData[$i]['name']) . $varDescription,
                'price' => $arrData[$i]['base_price']*100,
                //'price_incl' => ((($arrData[$i]['base_price']*$arrData[$i]['qty_ordered'])+$arrData[$i]['tax_amount'])/$arrData[$i]['qty_ordered'])*100,
                'price_incl' => $arrData[$i]['original_price']*100,
                'price_vat' => ($arrData[$i]['tax_amount']/$arrData[$i]['qty_ordered'])*100,
                'vatpercentage' => trim(number_format($arrData[$i]['tax_percent'],2,'.', ''))*100,
                'discount' => 0,
                'quantity' => $arrData[$i]['qty_ordered']*100,
                'categories' => $category
                );

            $invoice->addItem($params);

        }

        if($rowOne['shipping_amount'] > 0)
        {
            $params = array(  
                'code' => 'SHPMNT',  
                'description' => trim($rowOne['shipping_description']),
                'price' => $rowOne['shipping_amount']*100,
                'price_incl' => $rowOne['shipping_incl_tax']*100,
                'price_vat' => $rowOne['shipping_tax_amount']*100,
                'vatpercentage' => round(($rowOne['shipping_tax_amount']/$rowOne['shipping_amount'])*100)*100,
                'discount' => 0,
                'quantity' => 100,
                'categories' => 'shipping'
                );

            $invoice->addItem($params);
            
        }



        // $order = Mage::getModel('sales/order')->loadByIncrementId($varOrderID);

        // $orderDetails = $order->getData();

        $couponCode = $rowOne['coupon_code'];
        //echo $couponCode;
        //print_r($order);
       // $couponCode = $orderDetails['coupon_code'];

        if($couponCode > ''){
            $oCoupon = Mage::getModel('salesrule/coupon')->load($couponCode, 'code');
            $oRule = Mage::getModel('salesrule/rule')->load($oCoupon->getRuleId());
            //var_dump($oRule->getData());

            $ruleData = $oRule->getData();

            $discount = $ruleData['discount_amount'];
            $params = array(  
                    'code' => 'DSCNT',  
                    'description' => $couponCode,
                    // 'price' => ($rowOne['base_subtotal'] * ($discount/100))*100,
                    // 'price_incl' => ($rowOne['base_subtotal'] * ($discount/100))*100,
                    'price' => $discount*100,
                    'price_incl' => $discount*100,
                    'price_vat' => 0,
                    'vatpercentage' => 0,
                    'discount' => 0,
                    'quantity' => -100,
                    'categories' => 'discount'
                    );

            $invoice->addItem($params);
        }

        $invoice_fee = Mage::getSingleton('checkout/session')->getQuote()->getInvoiceFee();
        if($invoice_fee > 0){
            $price_incl = $invoice_fee*100;
            $price = ($invoice_fee/121)*100;

            $params = array(  
                'code' => 'FEE',  
                'description' => 'Betalingstoeslag',
                'price' => $price,
                'price_incl' => $price_incl,
                'price_vat' => $price_incl - $price,
                'vatpercentage' => 2100,
                'discount' => 0,
                'quantity' => 100,
                'categories' => 'fee'
                );

            $invoice->addItem($params);
        }

        
        // $coupon = Mage::getModel('salesrule/rule');
        // $couponCollection = $coupon->getCollection();
        // foreach($couponCollection as $c){
        //     print_r($c);
        //     echo 'Code:'.$c->getCode().'--->Discount Amount:'.$c->getDiscountAmount().'<br />';

        //     $params = array(  
        //         'code' => 'DSCNT',  
        //         'description' => $c->getCode(),
        //         'price' => $rowOne['base_subtotal'] * ($c->getDiscountAmount()/100),
        //         'price_incl' => $rowOne['base_subtotal'] * ($c->getDiscountAmount()/100),
        //         'price_vat' => 0,
        //         'vatpercentage' => 0,
        //         'discount' => 0,
        //         'quantity' => -100,
        //         'categories' => 'discount'
        //         );

        //     $invoice->addItem($params);

        // }

        $result =  $invoice->sendRequest();

       
        if($result != 1){
            $this->notify_admin('Qinvoice Connect Error','Could not send invoice for order '. $order_id);
        }
       
        return true;
        

        //$curlInvoiveResult = $this->sendCurlRequest($createInvoiceXML);
    }
    public function notify_admin($subject,$msg) 
    {
        $varSubject = 'Qinvoice Notification';
                
        Mage::log($subject .': '. $msg);

        $mail = Mage::getModel('core/email');
        $mail->setToName(Mage::getStoreConfig('trans_email/ident_general/name') );
        $mail->setToEmail(Mage::getStoreConfig('trans_email/ident_general/email') );
        $mail->setBody($msg);
        $mail->setSubject($subject);
        $mail->setFromEmail("support@qinvoice.com");
        $mail->setFromName("Qinvoice Support");
        $mail->setType('text');
        $mail->send();
    }
    
}




class qinvoice{

    protected $gateway = '';
    private $username;
    private $password;

    public $companyname;
    public $firstname;
    public $lastname;
    public $email;
    public $address;
    public $zipcode;
    public $city;
    public $country;
    public $delivery_companyname;
    public $delivery_firstname;
    public $delivery_lastname;
    public $delivery_address;
    public $delivery_zipcode;
    public $delivery_city;
    public $delivery_country;
    public $delivery_phone;
    public $delivery_email;
    public $delivery_date;
    public $vatnumber;
    public $remark;
    public $paid;
    public $saverelation = false;
    public $action;

    public $layout;
    
    private $tags = array();
    private $items = array();
    private $files = array();
    private $recurring;

    function __construct($username, $password){
        $this->username = $username;
        $this->password = $password;
        $this->recurring = 'none';

        $db = Mage::getSingleton('core/resource')->getConnection('core_write');
        
        // GETTING API URL
        $varURLPath = 'invoice_options/invoice/api_url';
        $prefix = Mage::getConfig()->getTablePrefix();
        $resultURL = $db->query("SELECT value FROM {$prefix}core_config_data WHERE path LIKE '".$varURLPath."'");
        $rowURL = $resultURL->fetch(PDO::FETCH_ASSOC);
        $apiURL = $rowURL['value'];

        $this->gateway = $apiURL;
    }

    public function addTag($tag){
        $this->tags[] = $tag;
    }

    public function setLayout($code){
        $this->layout = $code;
    }

    public function setRecurring($recurring){
        $this->recurring = strtolower($recurring);
    }

    public function addItem($params){
        $item['code'] = (isset($params['code']) ? $params['code'] : "");
        $item['description'] = $params['description'];
        $item['price'] = $params['price'];
        $item['price_incl'] = $params['price_incl'];
        $item['price_vat'] = $params['price_vat'];
        $item['vatpercentage'] = $params['vatpercentage'];
        $item['discount'] = $params['discount'];
        $item['quantity'] = $params['quantity'];
        $item['categories'] = $params['categories'];
        $this->items[] = $item;
    }
    
    public function addFile($name, $url){
        $this->files[] = array('url' => $url, 'name' => $name);
    }

    public function sendRequest() {
        $content = "<?xml version='1.0' encoding='UTF-8'?>";
        $content .= $this->buildXML();

        $headers = array("Content-type: application/atom+xml");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->gateway );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        $data = curl_exec($ch);
        if (curl_errno($ch)) {
            print curl_error($ch);
        } else {
            curl_close($ch);
        }
        if($data == 1){
            return true;
        }else{
            return false;
        }
        
    }

    private function buildXML(){
        $string = '<request>
                        <login mode="newInvoice">
                            <username><![CDATA['.$this->username.']]></username>
                            <password><![CDATA['.$this->password.']]></password>
                            <identifier><![CDATA[Magento_1.1.2]]></identifier>
                        </login>
                        <invoice>
                            <companyname><![CDATA['. $this->companyname .']]></companyname>
                            <firstname><![CDATA['. $this->firstname .']]></firstname>
                            <lastname><![CDATA['. $this->lastname .']]></lastname>
                            <email><![CDATA['. $this->email .']]></email>
                            <phone><![CDATA['. $this->phone .']]></phone>
                            <address><![CDATA['. $this->address .']]></address>
                            <zipcode><![CDATA['. $this->zipcode .']]></zipcode>
                            <city><![CDATA['. $this->city .']]></city>
                            <country><![CDATA['. $this->country .']]></country>

                            <delivery_companyname><![CDATA['. $this->delivery_companyname .']]></delivery_companyname>
                            <delivery_firstname><![CDATA['. $this->delivery_firstname .']]></delivery_firstname>
                            <delivery_lastname><![CDATA['. $this->delivery_lastname .']]></delivery_lastname>
                            <delivery_address><![CDATA['. $this->delivery_address .']]></delivery_address>
                            <delivery_zipcode><![CDATA['. $this->delivery_zipcode .']]></delivery_zipcode>
                            <delivery_city><![CDATA['. $this->delivery_city .']]></delivery_city>
                            <delivery_country><![CDATA['. $this->delivery_country .']]></delivery_country>
                            <delivery_phone><![CDATA['. $this->delivery_phone .']]></delivery_phone>
                            <delivery_email><![CDATA['. $this->delivery_email .']]></delivery_email>
                            <delivery_date><![CDATA['. $this->delivery_date .']]></delivery_date>

                            <vat><![CDATA['. $this->vatnumber .']]></vat>
                            <recurring><![CDATA['. $this->recurring .']]></recurring>
                            <remark><![CDATA['. $this->remark .']]></remark>
                            <layout><![CDATA['. $this->layout .']]></layout>
                            <paid><![CDATA['. $this->paid .']]></paid>
                            <action><![CDATA['. $this->action .']]></action>
                            <saverelation><![CDATA['. $this->saverelation .']]></saverelation>
                            <calculation_method><![CDATA['. $this->calculation_method .']]></calculation_method>
                            <tags>';
        foreach($this->tags as $tag){
            $string .= '<tag><![CDATA['. $tag .']]></tag>';
        }
                    
        $string .= '</tags>
                    <items>';
        foreach($this->items as $i){

            $string .= '<item>
                <code><![CDATA['. $i['code'] .']]></code>
                <quantity><![CDATA['. $i['quantity'] .']]></quantity>
                <description><![CDATA['. $i['description'] .']]></description>
                <price>'. $i['price'] .'</price>
                <price_incl>'. $i['price_incl'] .'</price_incl>
                <price_vat>'. $i['price_vat'] .'</price_vat>
                <vatpercentage>'. $i['vatpercentage'] .'</vatpercentage>
                <discount>'. $i['discount'] .'</discount>
                <categories><![CDATA['. $i['categories'] .']]></categories>
                
            </item>';
        }
                       
        $string .= '</items>
                    <files>';
        foreach($this->files as $f){
            $string .= '<file url="'.$f['url'].'">'.$f['name'].'</file>';
        }
        $string .= '</files>
                </invoice>
            </request>';
        return $string;
    }
}

 ?>