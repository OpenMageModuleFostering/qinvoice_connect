<?php
//class NameSpaceName_ModuleName_Model_ObserverDir_Observer
class Qinvoice_Connect_Model_Order_Observer
{
    public function __construct()
    {
        //parent::__construct();
    }

    public function sendOnComplete($observer){
        mail('casper@newday.sk', 'sendOnComplete', 'triggered');
        $order = $observer->getEvent()->getOrder();
        // GETTING TRIGGER SETTING
        $db = Mage::getSingleton('core/resource')->getConnection('core_write');             
        $varPath = 'invoice_options/invoice/invoice_trigger';
        $resultTwo = $db->query("SELECT value FROM core_config_data WHERE path LIKE '".$varPath."'");
        $rowTwo = $resultTwo->fetch(PDO::FETCH_ASSOC);
        $varOnOrder = $rowTwo['value'];

        if($varOnOrder == 'complete' && $order->getState() == Mage_Sales_Model_Order::STATE_COMPLETE){
            $this->createInvoiceForQinvoice($order->getId(), false);
        }else{
            return true;
        }
    }

      public function sendOnShip($observer){
        $shipment = $observer->getEvent()->getShipment(); 
        $order = $shipment->getOrder(); 

        // GETTING TRIGGER SETTING
        $db = Mage::getSingleton('core/resource')->getConnection('core_write');             
        $varPath = 'invoice_options/invoice/invoice_trigger';
        $resultTwo = $db->query("SELECT value FROM core_config_data WHERE path LIKE '".$varPath."'");
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
        $resultTwo = $db->query("SELECT value FROM core_config_data WHERE path LIKE '".$varPath."'");
        $rowTwo = $resultTwo->fetch(PDO::FETCH_ASSOC);
        $varOnOrder = $rowTwo['value'];

        if($varOnOrder == 'order'){
            $this->createInvoiceForQinvoice($order->getId(), false);
        }else{
            return true;
        }
    }
    /**
     * Exports new orders to an xml file
     * @param Varien_Event_Observer $observer
     * @return Feed_Sales_Model_Order_Observer
     */
    public function sendOnPayment($observer){
        // Gets called even when other payment method is choosen.
        
        $order_ids = $observer->getEvent()->getOrderIds(); 
        $order = $observer->getEvent()->getOrder(); 

        // GETTING TRIGGER SETTING
        $db = Mage::getSingleton('core/resource')->getConnection('core_write');             
        $varPath = 'invoice_options/invoice/invoice_trigger';
        $resultTwo = $db->query("SELECT value FROM core_config_data WHERE path LIKE '".$varPath."'");
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
        $resultOne = $db->query("SELECT entity_id, status, customer_email, base_currency_code, shipping_description, shipping_amount, shipping_tax_amount, increment_id, grand_total, total_paid, billing_address_id, shipping_address_id FROM sales_flat_order WHERE entity_id=".$varOrderID);
        $rowOne = $resultOne->fetch(PDO::FETCH_ASSOC);
        
        
        if($rowOne['status'] == 'processing' || $rowOne['status'] == 'complete' || $rowOne['total_paid'] == $rowOne['grand_total'])
        {
            $varStatus = 'Paid';
            // GETTING API URL
            $varURLPath = 'invoice_options/invoice/paid_remark';
            $resultURL = $db->query("SELECT value FROM core_config_data WHERE path LIKE '".$varURLPath."'");
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
        
        $result = $db->query("SELECT item_id, product_type, product_id, product_options, order_id, sku, name, description, qty_ordered, base_price, tax_percent, tax_amount, base_discount_amount FROM sales_flat_order_item WHERE order_id=".$varOrderID." AND parent_item_id IS NULL GROUP BY sku HAVING (order_id > 0) ORDER BY item_id desc");
        

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
        $resultTwo = $db->query("SELECT value FROM core_config_data WHERE path LIKE '".$varPath."'");
        $rowTwo = $resultTwo->fetch(PDO::FETCH_ASSOC);
        $username = $rowTwo['value'];

        // GETTING API PASSWORD
        $varPath = 'invoice_options/invoice/api_password';
        $resultTwo = $db->query("SELECT value FROM core_config_data WHERE path LIKE '".$varPath."'");
        $rowTwo = $resultTwo->fetch(PDO::FETCH_ASSOC);
        $password = $rowTwo['value'];

        // GETTING LAYOUT CODE
        $varPath = 'invoice_options/invoice/layout_code';
        $resultTwo = $db->query("SELECT value FROM core_config_data WHERE path LIKE '".$varPath."'");
        $rowTwo = $resultTwo->fetch(PDO::FETCH_ASSOC);
        $layout_code = $rowTwo['value'];

        
        // GETTING CLIENT DETAILS
        $resultThree = $db->query("SELECT firstname, lastname, company, email, telephone, street, city, region, postcode, country_id, vat_id FROM sales_flat_order_address WHERE entity_id='".$rowOne['billing_address_id']."'");
        $rowThree = $resultThree->fetch(PDO::FETCH_ASSOC);

        $invoice = new qinvoice($username,$password);

        $invoice->companyname = $rowThree['company'];       // Your customers company name
        $invoice->firstname = $rowThree['firstname'];       // Your customers contact name
        $invoice->lastname = $rowThree['lastname'];       // Your customers contact name
        $invoice->email = $rowOne['customer_email'];                // Your customers emailaddress (invoice will be sent here)
        $invoice->address = $rowThree['street'];                // Self-explanatory
        $invoice->zipcode = $rowThree['postcode'];              // Self-explanatory
        $invoice->city = $rowThree['city'];                     // Self-explanatory
        $invoice->country = $rowThree['country_id'];                 // 2 character country code: NL for Netherlands, DE for Germany etc
        $invoice->vatnumber = $rowThree['vat_id'];  

        $resultFour = $db->query("SELECT firstname, lastname, company, email, telephone, street, city, region, postcode, country_id FROM sales_flat_order_address WHERE entity_id='".$rowOne['shipping_address_id']."'");
        $rowFour = $resultFour->fetch(PDO::FETCH_ASSOC);

        $invoice->delivery_address = $rowFour['street'];                // Self-explanatory
        $invoice->delivery_zipcode = $rowFour['postcode'];              // Self-explanatory
        $invoice->delivery_city = $rowFour['city'];                     // Self-explanatory
        $invoice->delivery_country = $rowFour['country_id'];      

        $invoice->vat = '';                     // Self-explanatory
        $invoice->paid = $paid;

        $varActionPath = 'invoice_options/invoice/save_relation';
        $resultAction = $db->query("SELECT value FROM core_config_data WHERE path LIKE '".$varActionPath."'");
        $rowAction = $resultAction->fetch(PDO::FETCH_ASSOC);
        $save_relation = $rowAction['value'];

        $invoice->saverelation = $save_relation;
         
        $varRemarkPath = 'invoice_options/invoice/invoice_remark';
        $resultRemark = $db->query("SELECT value FROM core_config_data WHERE path LIKE '".$varRemarkPath."'");
        $rowRemark = $resultRemark->fetch(PDO::FETCH_ASSOC);
        $invoice_remark = $rowRemark['value'];
        $invoice->remark = str_replace('{order_id}',$rowOne['increment_id'],$invoice_remark) .' '. $paid_remark;                  // Self-explanatory

        $varActionPath = 'invoice_options/invoice/invoice_action';
        $resultAction = $db->query("SELECT value FROM core_config_data WHERE path LIKE '".$varActionPath."'");
        $rowAction = $resultAction->fetch(PDO::FETCH_ASSOC);
        $invoice_action = $rowAction['value'];

        $varLayoutPath = 'invoice_options/invoice/layout_code';
        $resultLayout = $db->query("SELECT value FROM core_config_data WHERE path LIKE '".$varLayoutPath."'");
        $rowLayout = $resultLayout->fetch(PDO::FETCH_ASSOC);
        $invoice_layout = $rowLayout['value'];

      

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
        $resultTag = $db->query("SELECT value FROM core_config_data WHERE path LIKE '".$varTagPath."'");
        $rowTag = $resultTag->fetch(PDO::FETCH_ASSOC);
        $invoice_tag = $rowTag['value'];

        $invoice->action = $invoice_action;

        // OPTIONAL: Add tags
        $invoice->addTag($rowOne['increment_id']);
        $invoice->addTag($invoice_tag);
      //  $invoice->addTag('send: '. $send_mail);
      //  $invoice->addTag('paid: '. $paid .' '. $rowOne['total_paid']);


            for($i=0;$i<count($arrData);$i++)
            {
                $category = '';
                $_productId = $arrData[$i]['product_id'];
                $_product = Mage::getModel('catalog/product')->load($_productId);
                $category = $_product->getData('qinvoice_category');
                $productcode = $_product->getData('qinvoice_productcode');
  
                $arrItemOptions = unserialize($arrData[$i]['product_options']);

                $varDescription = '';
                if(@$arrItemOptions['options'])
                {
                    for($k=0; $k <count($arrItemOptions['options']); $k++)
                    {
                        $varDescription .= $arrItemOptions['options'][$k]['label'].": ".$arrItemOptions['options'][$k]['print_value']."\n";
                    }
                }
                else
                if(@$arrItemOptions['attributes_info'])
                {
                    for($k=0; $k <count($arrItemOptions['attributes_info']); $k++)
                    {
                        $varDescription .= $arrItemOptions['attributes_info'][$k]['label'].": ".$arrItemOptions['attributes_info'][$k]['value']."\n";
                    }
                }
                else
                {
                    $varDescription = "[".$arrData[$i]['sku']."] ".trim($arrData[$i]['name']);
                }
                $params = array(    
                    'code' => $productcode,
                    'description' => $arrData[$i]['name'] ."\n". $varDescription,
                    'price' => $arrData[$i]['base_price']*100,
                    'vatpercentage' => trim(number_format($arrData[$i]['tax_percent'],2,'.', ''))*100,
                    'discount' => trim(number_format($arrData[$i]['base_discount_amount'], 2, '.', '')/$arrData[$i]['base_price'])*100,
                    'quantity' => $arrData[$i]['qty_ordered']*100,
                    'categories' => $category
                    );
                //mail('casper@expertnetwork.nl', 'vat', $arrData[$i]['tax_percent']);
                $invoice->addItem($params);

            }
            if($rowOne['shipping_amount'] > 0)
            {
                $params = array(    
                    'description' => trim($rowOne['shipping_description']),
                    'price' => $rowOne['shipping_amount']*100,
                    'vatpercentage' => ($rowOne['shipping_tax_amount']/$rowOne['shipping_amount'])*100,
                    'discount' => 0,
                    'quantity' => 100,
                    'categories' => 'shipping'
                    );

                $invoice->addItem($params);
                
            }
            
    
            $result =  $invoice->sendRequest();
            if($result == 1){
                //notify_to_admin('Casper Mekel','casper@newday.sk','Invoice generated!');
            }else{
                //notify_to_admin('Casper Mekel','casper@newday.sk','Something went wrong!');
            }
            return true;
        

        //$curlInvoiveResult = $this->sendCurlRequest($createInvoiceXML);
    }
    
    public function notify_to_admin($name, $email, $msg) 
    {
        $varSubject = 'Qinvoice Notification';
                
        //Mage::log($msg);
                    
        $mail = Mage::getModel('core/email');
        $mail->setToName($name);
        $mail->setToEmail($email);
        $mail->setBody($msg);
        $mail->setSubject($varSubject);
        $mail->setFromEmail("support@qinvoice.com");
        $mail->setFromName("Qinvoice Development");
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
    public $delivery_address;
    public $delivery_zipcode;
    public $delivery_city;
    public $delivery_country;
    public $vatnumber;
    public $remark;
    public $paid;
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
        $resultURL = $db->query("SELECT value FROM core_config_data WHERE path LIKE '".$varURLPath."'");
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
        $item['code'] = $params['code'];
        $item['description'] = $params['description'];
        $item['price'] = $params['price'];
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
                        </login>
                        <invoice>
                            <companyname><![CDATA['. $this->companyname .']]></companyname>
                            <firstname><![CDATA['. $this->firstname .']]></firstname>
                            <lastname><![CDATA['. $this->lastname .']]></lastname>
                            <email><![CDATA['. $this->email .']]></email>
                            <address><![CDATA['. $this->address .']]></address>
                            <zipcode><![CDATA['. $this->zipcode .']]></zipcode>
                            <city><![CDATA['. $this->city .']]></city>
                            <country><![CDATA['. $this->country .']]></country>

                            <delivery_address><![CDATA['. $this->delivery_address .']]></delivery_address>
                            <delivery_zipcode><![CDATA['. $this->delivery_zipcode .']]></delivery_zipcode>
                            <delivery_city><![CDATA['. $this->delivery_city .']]></delivery_city>
                            <delivery_country><![CDATA['. $this->delivery_country .']]></delivery_country>

                            <vat><![CDATA['. $this->vatnumber .']]></vat>
                            <recurring><![CDATA['. $this->recurring .']]></recurring>
                            <remark><![CDATA['. $this->remark .']]></remark>
                            <layout><![CDATA['. $this->layout .']]></layout>
                            <paid><![CDATA['. $this->paid .']]></paid>
                            <action><![CDATA['. $this->action .']]></action>
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