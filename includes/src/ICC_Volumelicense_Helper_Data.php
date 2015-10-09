<?php
class ICC_Volumelicense_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Sends an email to shared person when a volumelicense is shared.
     * @param string $email_to
     * @param string $customername
     * @param string $oldcustomername
     * @param string $productname
     * @param int $Totaluser
     */
    public function volumeLicenseShareEmail($email_to,$mailData,$ccEmail){
       // This is the template name from your etc/config.xml 
        $template_id = 'volumelicense_share';

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

       // echo "<pre>";print_r($email_template);die;
        //Send the email!
        $email_template->send($email_to,null,$email_template_variables);
        
        
    }
    
    /**
     * Sends an email to the purchaser when a volumelicense is purchased
     * @param string $email_to
     * @param string $customername
     * @param string $productname
     * @param int $Totaluser
     */
        public function volumeLicensePurchaseEmail($email_to,$customername,$productdata){
        
        // This is the template name from your etc/config.xml 
        $template_id = 'volumelicense_purchase';
        // Who were sending to...
        //$email_to = 'demo@example.com';
        $customer_name  = $customername;
        $product_data   = $productdata;
        /*$Total_user     = (int)$Totaluser; 
        $expiration     = $expire;*/
        $myecode_url    = Mage::getUrl('ecodes/account/products');

        // Load our template by template_id
        $email_template  = Mage::getModel('core/email_template')->loadDefault($template_id);
        
        // Here is where we can define custom variables to go in our email template!
        $email_template_variables = array(
            'customer_name' => $customer_name,
            'product_data' => $product_data,
            //'users' => $Total_user,
           // 'duration' => $expiration,
            'myecode_url' => $myecode_url
            // Other variables for our email template.
       );

        // I'm using the Store Name as sender name here.
        $sender_name = Mage::getStoreConfig(Mage_Core_Model_Store::XML_PATH_STORE_STORE_NAME);
        // I'm using the general store contact here as the sender email.
        $sender_email = Mage::getStoreConfig('trans_email/ident_general/email');
        $email_template->setSenderName($sender_name);
        $email_template->setSenderEmail($sender_email); 
	//echo "<pre>=====";print_r($email_template_variables);print_r($email_template,$email_template_variables);die("stopppp");
        //Send the email!
        $email_template->send($email_to,$customer_name,$email_template_variables);
 
    }
    
    /**
     * Sends an email to the volumelicense purchaser notifying that still few users are pending or need to assign volumelicense to remaining users.
     * 
     * @param string $email_to
     * @param string $customername
     * @param string $productname
     * @param string $cteatedDate
     * @param int $Totaluser
     * @param int $registred
     */
     public function volumeLicenseNotifyEmail($email_to,$customername,$productname,$cteatedDate,$Totaluser,$registred,$orderId){
        $template_id = 'volume_license_notify';
        //echo $template_id; die;
        $model = Mage::getModel("optremainder/optremainder");
        $flag = $model->getOptOutFlag($orderId, $email_to);
        if (!$flag) {
            $customer_name  = $customername;
            $product_name   = $productname;
            $purchased_date = $cteatedDate;
            $Total_user     = $Totaluser; 
            $orderId = $orderId;
            $remaining_user   = (int)$Totaluser-$registred;
            $myecode_url    = Mage::getUrl('ecodes/account/products');
            $optionArrayToOptOut["customer_email"] = $email_to;
            $optionArrayToOptOut["user_type"] = ICC_OptRemainder_Model_Optremainder::PURCHASING_AGENT;
            $optionArrayToOptOut["item_type"] = "volume_license";
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
    
    public function volumeLicenseNotifyUnregistered($email_to, $customername, $productname, $cteatedDate, $orderId = '') {
        $template_id = 'volumelicense_share_unregister';
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
            $optionArrayToOptOut["item_type"] = "volume_license";
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
        //return array_unique($email); 
        //parent order owner can assign multilpe quantities to himself
       // echo "<pre>";print_r(array_diff(array_unique($email), array($parent_order_email)));die;
        return array_diff(array_unique($email), array($parent_order_email));
    }
    
    
     public function setReportsLog ($order = null, $reassign = false , $update = false){
        $linkData = $this->getLinkData($order->getId());
        $chklastItem = Mage::getModel("volumelicense/reports")->getCollection()->addFieldToFilter('order_number', $order->getIncrementId())->getLastItem();
        $reports = Mage::getModel("volumelicense/reports");
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
        $reports->setData('link_data', $linkData);
        $reports->setData('from_date', date('d-m-Y H:i:s'));
        if(!$update){
             $reports->save();
             if($chklastItem && $reassign){
                //update link data for old user
                $chklastItem->setData('link_data', $linkData);
                $chklastItem->setData('reassigned_to', $reports->getId());
                $chklastItem->setToDate(date('d-m-Y H:i:s'))->save();
            }
        }else{
            //update link data for current user on pdf download
            $chklastItem->setData('link_data', $linkData);
            $chklastItem->setData('reassigned_to', $reports->getId());
            $chklastItem->save();
        }
        
    }
    public function getLinkData($order_id){
        $co = Mage::getModel('sales/order')->load($order_id);
        $items = $co->getAllItems();
        foreach ($items as $item) {
            if ('downloadable' == $item->getProductType()) {
                $linkData['product_name'] =  $item->getName();
                $links = Mage::getModel('downloadable/link_purchased_item')->getCollection()->addFieldToFilter('order_item_id', $item->getId());
                foreach ($links as $link) {
                    $linkData[] = $link->getData();
                }
            }
        }
        return serialize($linkData);
       // return $linkData;
    } 
    public function hasVolumeLicense() {
        $requiedVolumeStep = false;
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        foreach ($quote as $item) {
            //$product = ;
            // echo '=='.$product.'????';
            if (Mage::getModel('catalog/product')->load($item->getProductId())->getData('volume_license') && Mage::getModel('catalog/product')->load($item->getProductId())->getVolumeLicense() == 1 && $item->getQty() > 1) {
                $requiedVolumeStep = true;
                break;
            }
        }
        return $requiedVolumeStep;
    }
    public function checkNotifyData(){
        $order = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('volume_license', 1)->addAttributeToFilter('parent_order_id', array('null' => true)) ;
        foreach($order as $odr){
            $childData = $this->checkEmailStatus($odr, $odr->getCustomerEmail());
        }

    }
    public function checkEmailStatus($order, $parentEmail){
        $childs = Mage::getModel('sales/order')->getCollection()
                    ->addFieldToFilter('volume_license', 1)->addFieldToFilter('parent_order_id', $order->getId())
                    ->addFieldToFilter('status', array('neq' => 'canceled'));
      //  print_r($childs->getData()); die;
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
    

}       
	 