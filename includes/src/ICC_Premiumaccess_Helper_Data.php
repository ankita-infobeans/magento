<?php
/**
 * Premiumaccess default helper
 *
 * @category    ICC
 * @package     ICC_Premiumaccess
 */
class ICC_Premiumaccess_Helper_Data extends Mage_Core_Helper_Abstract {
  
    Public function toRegistryStatus()
    {
        return array('0'=>'Inactive',
                     '1'=>'Active',
                     '2'=>'Expired',
                     '3'=>'Refunded',
                     '4'=>'Removed',
                     );
    }
    Public function toPremiumStatus()
    {
        return array('0'=>'Inactive',
                     '1'=>'Active',
                     '2'=>'Expired',
                     '3'=>'Refunded',
                     '4'=>'Removed',
                     );
    }
    
    
    const ACTIVE = 1;
    const PENDING = 0;
    const EXPIRED = 2;
    const REFUND = 3;
    const DELETE = 4;

        
    /**
     * This method used to check is order contain premium product ?
     */
    public function hasPremiumAccessProduct($orderId)
    {
        $order = Mage::getSingleton('sales/order')->load($orderId, 'increment_id');
        $allItems = $order->getAllItems();
        foreach($allItems as $item) {
            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            if ($product->getItemType() == ICC_Ecodes_Helper_Data::ECODES_ITEM_TYPE) { 
                $redirectUrl = Mage::getStoreConfig('icc_premiumaccess/access/avectra_url');
                $uri = Mage::getStoreConfig('icc_premiumaccess/access/site_url');
                $appendTokenString = (false === strpos(rawurldecode($uri), '?')) ? '?Token=' : '&Token=';
                $url = '';
                $url .= $redirectUrl . rawurlencode($uri. $appendTokenString . "{Token}");
                return $this->__("<p><a href='".$url."'>Click Here</a> to access the premium feature.</p>");
            }
        }
    }
    
    /**
     * This method used to update registry count on evry action on premum access.
     * @param type $_premium_access_sub_id
     */
//    public function updatePremiumAccessRegisteredCount($_premium_access_sub_id) {
//        if(isset($_premium_access_sub_id) && $_premium_access_sub_id > 0){
//            $premiumaccess_registry  = Mage::getModel('icc_premiumaccess/registry')->getCollection()
//                                        ->addFieldToFilter('subscription_id' , array('eq'=> $_premium_access_sub_id))
//                                        ->addFieldToFilter('status', array('in' => array($this::PENDING, $this::ACTIVE)));                                                    
//            $update_registered_cound = $premiumaccess_registry->getSize();
//            try {
//                $premiim_access     = Mage::getModel('icc_premiumaccess/premiumaccess')->load($_premium_access_sub_id);                                        
//                $premiim_access->setRegisteredCount($update_registered_cound);                                        
//                $premiim_access->save();         
//            } catch (Exception $e) {
//                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
//            }
//        }
//    }
    
    /**
     * This method used to send email on purchse premium access product 
     * @param type $email_to
     * @param type $customername
     * @param type $productname
     * @param type $Totaluser
     * @param type $expire
     */
    public function premiumAccessPurchaseEmail($email_to,$customername,$productname,$Totaluser,$expire){
        
        // This is the template name from your etc/config.xml 
        $template_id = 'premium_access_purchase';

        // Who were sending to...
        //$email_to = 'demo@example.com';
        $customer_name  = $customername;
        $product_name   = $productname;
        $Total_user     = (int)$Totaluser; 
        $expiration     = $expire;
        $myecode_url    = Mage::getUrl('ecodes/account/products');

        // Load our template by template_id
        $email_template  = Mage::getModel('core/email_template')->loadDefault($template_id);
        
        // Here is where we can define custom variables to go in our email template!
        $email_template_variables = array(
            'customer_name' => $customer_name,
            'product_data' => $product_name,
            'users' => $Total_user,
            'duration' => $expiration,
            'myecode_url' => $myecode_url
            // Other variables for our email template.
       );

        // I'm using the Store Name as sender name here.
        $sender_name = Mage::getStoreConfig(Mage_Core_Model_Store::XML_PATH_STORE_STORE_NAME);
        // I'm using the general store contact here as the sender email.
        $sender_email = Mage::getStoreConfig('trans_email/ident_general/email');
        $email_template->setSenderName($sender_name);
        $email_template->setSenderEmail($sender_email); 
        //Send the email!
        $email_template->send($email_to,$customer_name,$email_template_variables);
 
    }
    /**
     * This method used to send notification email on shared premium access .
     * @param type $email_to
     * @param type $customername
     * @param type $oldcustomername
     * @param type $productname
     * @param type $Totaluser
     * @param type $expire
     */
   /* public function premiumAccessShareEmail($email_to,$customername,$oldcustomername,$productname,$Totaluser,$expire){
       // This is the template name from your etc/config.xml 
        $template_id = 'premium_access_share';

        // Who were sending to...
        //$email_to = 'demo@example.com';
        $customer_name  = $customername;
        $old_customer_name=$oldcustomername;
        $product_name   = $productname;
        $Total_user     = $Totaluser; 
        $expiration     = $expire;
        $myecode_url    = Mage::getUrl('ecodes/account/products');
        $customer_creat_url= Mage::getUrl('customer/account/create');

        // Load our template by template_id
        $email_template  = Mage::getModel('core/email_template')->loadDefault($template_id);
        
        // Here is where we can define custom variables to go in our email template!
        $email_template_variables = array(
            'customer_name' => $customer_name,
            'old_customer_name' => $old_customer_name,
            'product_name' => $product_name,
            'users' => $Total_user,
            //'duration' => $expiration,
            'myecode_url' => $myecode_url,
            'customer_creat_url'=> $customer_creat_url
            // Other variables for our email template.
        );

        // I'm using the Store Name as sender name here.
        $sender_name = Mage::getStoreConfig(Mage_Core_Model_Store::XML_PATH_STORE_STORE_NAME);
        // I'm using the general store contact here as the sender email.
        $sender_email = Mage::getStoreConfig('trans_email/ident_general/email');
        $email_template->setSenderName($sender_name);
        $email_template->setSenderEmail($sender_email); 

        //Send the email!
        $email_template->send($email_to,$customer_name,$email_template_variables);
        
        
    }*/
    
    
    public function premiumAccessShareEmail($email_to,$mailData,$ccEmail){
       // This is the template name from your etc/config.xml 
        $template_id = 'premium_access_share';
        // This is the template name from your etc/config.xml 
        // Who were sending to...
        //$email_to = 'demo@example.com';
        //$customer_name  = $customername;
        //$old_customer_name=$oldcustomername;
        //$product_name   = $productname;
        //$mail_data     = $mailData; 
       
        $myecode_url    = Mage::getUrl('ecodes/account/products');
        $customer_creat_url= Mage::getUrl('customer/account/create');

        // Load our template by template_id
        $email_template  = Mage::getModel('core/email_template')->loadDefault($template_id);
        
        // Here is where we can define custom variables to go in our email template!
        $email_template_variables = array(
            //'customer_name' => $customer_name,
           // 'old_customer_name' => $old_customer_name,
            //'product_name' => $product_name,
            'mail_data' => $mailData,
        
            'myecode_url' => $myecode_url,
            'customer_creat_url'=> $customer_creat_url
            // Other variables for our email template.
        );

        // I'm using the Store Name as sender name here.
        $sender_name = Mage::getStoreConfig(Mage_Core_Model_Store::XML_PATH_STORE_STORE_NAME);
        // I'm using the general store contact here as the sender email.
        $sender_email = Mage::getStoreConfig('trans_email/ident_general/email');
        $email_template->setSenderName($sender_name);
        $email_template->setSenderEmail($sender_email); 
        $email_template->addBcc($ccEmail);
       // echo "<pre>====="; print_r($email_to); print_r($email_template,$email_template_variables);die("stopppp");
     //  echo "<pre>";print_r($email_template);die;
       //Send the email!
        $email_template->send($email_to,null,$email_template_variables);     
        
    }
    /**
     * This method used to send notification email on 2 days and 2 week For not shared custoemr count. 
     * @param type $email_to
     * @param type $customername
     * @param type $productname
     * @param type $cteatedDate
     * @param type $Totaluser
     * @param type $registred
     */
    public function premiumAccessNotifyEmail($email_to,$customername,$productname,$cteatedDate,$Totaluser,$registred, $orderId){
        Mage::log("Testing",null,'cront.log');
        $template_id = 'premium_access_notify';
        $model = Mage::getModel("optremainder/optremainder");
        $flag = $model->getOptOutFlag($orderId, $email_to);
        if (!$flag) {
            $customer_name  = $customername;
            $product_name   = $productname;
            $purchased_date = $cteatedDate;
            $Total_user     = $Totaluser; 
            $remaining_user   = (int)$Totaluser-$registred;
            $myecode_url    = Mage::getUrl('ecodes/account/products');
            $optionArrayToOptOut["customer_email"] = $email_to;
            $optionArrayToOptOut["user_type"] = ICC_OptRemainder_Model_Optremainder::PURCHASING_AGENT;
            $optionArrayToOptOut["item_type"] = "premium_access";
            $optionArrayToOptOut["order_id"] = $orderId;
            $urlOptions = urlencode(serialize($optionArrayToOptOut));
            $email_template  = Mage::getModel('core/email_template')->loadDefault($template_id);
            $email_template_variables = array(
                'customer_name' => $customer_name,
                'product_name' => $product_name,
                'purchased_date'=>$purchased_date,
                'users' => $Total_user,
                'remaining_user' => $remaining_user,
                'myecode_url' => $myecode_url,
                'order_id' => $orderId,
                'url_options' => $urlOptions
            );
            $sender_name = Mage::getStoreConfig(Mage_Core_Model_Store::XML_PATH_STORE_STORE_NAME);
            $sender_email = Mage::getStoreConfig('trans_email/ident_general/email');
            $email_template->setSenderName($sender_name);
            $email_template->setSenderEmail($sender_email);
            $var = $email_template->send($email_to,$customer_name,$email_template_variables);  
        }
    }
    
    public function getPremiumAccessType() {
    
	$attributeId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product', 'item_type');
        $collection = Mage::getResourceModel('eav/entity_attribute_option_collection')
                    ->setPositionOrder('asc')
                    ->setAttributeFilter($attributeId)
                    ->setStoreFilter(0)
                    ->load();
        $collection->getSelect()->where("tsv.value = 'PremiumAccess'");
        $option_ids = array_column($collection->getData(), 'option_id');
        return $option_ids;
    
    }
    
     public function getChildEmailIds($parentid,$product_id){
      
	$parent_order = $orders = Mage::getModel('sales/order')->load($parentid);
        $parent_order_email = $parent_order->getCustomerEmail();
	$child_order = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('parent_order_id', array('eq' => $parentid));
        $child_order->getSelect()->join(array('sfoi' => 'sales_flat_order_item'), "main_table.entity_id = sfoi.order_id");
        $child_order->addFieldToFilter('product_id', array('eq'=> $product_id));
      
      
        foreach ($child_order as $odr){
            $email[] = $odr->getCustomerEmail();
            if($odr->getFutureEmail() != ''){
                  $email[] = $odr->getFutureEmail();
            }
            
        }
        return array_diff(array_unique($email), array($parent_order_email));
    }
    
    public function setReportsLog ($order = null, $reassign = false , $update = false){
        $linkData = $this->getLinkData($order->getId());
        $chklastItem = Mage::getModel("icc_premiumaccess/reports")->getCollection()->addFieldToFilter('order_number', $order->getIncrementId())->getLastItem();
        $reports = Mage::getModel("icc_premiumaccess/reports");
	$reports->setData('order_number', $order->getIncrementId());
        $parent_order_num = Mage::getModel('sales/order')->load($order->getParentOrderId())->getIncrementId();
        $reports->setData('parent_order_num', $parent_order_num);
        if($order->getFutureEmail()){
            $reports->setData('email', $order->getFutureEmail());
        }else{
            $reports->setData('email', $order->getCustomerEmail());
        }
        if(!$order->getFutureEmail()){
            $reports->setData('customer_id', $order->getCustomerId());
        }
        $reports->setData('link_data', $linkData['product_name']);
        $reports->setData('from_date', date('d-m-Y H:i:s'));
        //echo "<pre>";print_r($reports->getData());die;
        if(!$update){
       // die("innnn");
             $reports->save();
             if($chklastItem && $reassign){
                //update link data for old user
                $chklastItem->setData('link_data', $linkData['product_name']);
                $chklastItem->setData('reassigned_to', $reports->getId());
                $chklastItem->setToDate(date('d-m-Y H:i:s'))->save();
            }
        }else{
        //die("outttt");
            //update link data for current user on pdf download
            $chklastItem->setData('link_data', $linkData['product_name']);
            $chklastItem->setData('reassigned_to', $reports->getId());
            $chklastItem->save();
        }
        
    }
    
        public function getLinkData($order_id){
        
        $co = Mage::getModel('sales/order')->load($order_id);
        $items = $co->getAllItems();
        foreach ($items as $item) {
                $linkData['product_name'] =  $item->getName();            
        }
        //return serialize($linkData);
        return $linkData;
       // return $linkData;
    }
    
        public function checkEmailStatus($order, $parentEmail){
        $childs = Mage::getModel('sales/order')->getCollection()
                    ->addFieldToFilter('premium_access', 1)->addFieldToFilter('parent_order_id', $order->getId())
                    ->addFieldToFilter('status', array('neq' => 'canceled'));
        $count = 0;
        foreach($childs as $child){
            if($child->getCustomerEmail() == $parentEmail){
                 $items = $order->getAllVisibleItems();
                    foreach($items as $i):
                      $data['name'] = $name = $i->getName();
                      $data['created_date'] = $order->getCreatedAt();
                    endforeach;

                $count++;
            }
        }
        if($count > 1){
            $data['max_register'] = sizeof($childs);
            $data['register_count'] = sizeof($count);
            return $data;
        }
        return false;
        
    }
    
     public function premiumaccessNotifyUnregistered($email_to, $customername, $productname, $cteatedDate, $orderId='') {
        $template_id = 'premiumaccess_share_unregister';
        //echo $template_id; die;
        $model = Mage::getModel("optremainder/optremainder");
        $flag = $model->getOptOutFlag($orderId, $email_to);
        if (!$flag) {
            $customer_name = $customername;
            $product_name = $productname;
            $purchased_date = $cteatedDate;
            $myecode_url = Mage::getUrl('ecodes/account/products');
            $optionArrayToOptOut["customer_email"] = $email_to;
            $optionArrayToOptOut["user_type"] = ICC_OptRemainder_Model_Optremainder::NON_REGISTERED_USER;
            $optionArrayToOptOut["item_type"] = "premium_access";
            $optionArrayToOptOut["order_id"] = $orderId;
            $urlOptions = urlencode(serialize($optionArrayToOptOut));


            $email_template = Mage::getModel('core/email_template')->loadDefault($template_id);
            $email_template_variables = array(
                'customer_name' => $customer_name,
                'product_name' => $product_name,
                'purchased_date' => $purchased_date,
                'myecode_url' => $myecode_url,
                'order_id' => $orderId,
                'url_options' => $urlOptions
            );
            $sender_name = Mage::getStoreConfig(Mage_Core_Model_Store::XML_PATH_STORE_STORE_NAME);
            $sender_email = Mage::getStoreConfig('trans_email/ident_general/email');
            $email_template->setSenderName($sender_name);
            $email_template->setSenderEmail($sender_email);

            $var = $email_template->send($email_to, $customer_name, $email_template_variables);
        }
    }
 
    public function isValExists($needle, $haystack)
    {
        if(in_array($needle, $haystack)) {
             return true;
        }
        foreach($haystack as $element) {
             if(is_array($element) && $this->isValExists($needle, $element))
                  return true;
        }
        return false; 
   }
}
