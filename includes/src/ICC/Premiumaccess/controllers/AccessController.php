<?php
/* Premiumaccess front contrller
 *
 * @category    ICC
 * @package     ICC_Premiumaccess
 */
class ICC_Premiumaccess_AccessController extends Mage_Core_Controller_Front_Action
{
    /**
     * This method used for notify by email after 2 days and 2 weeks for non shared subscription.
     */
    public function notifyAction(){ // for test notification mail 
        $premiumaccess = Mage::getModel('icc_premiumaccess/notification');
        $premiumaccess->notificationEmail();
        echo "hi";
    }
    
    public function indexAction()
    {
        $accessId = $this->getRequest()->getParam('id', 0);
        if ($accessId) {
            $redirectUrl =  Mage::getModel('catalog/product')->loadByAttribute('sku',$accessId)->getProductUrl();
            $this->_redirectUrl($redirectUrl);
        } else {
             $redirectUrl = Mage::getStoreConfig('ecode_premium/ecode_premium_subscription/redirect_url');
             $this->_redirectUrl($redirectUrl);
        }
    }
    
    
    public function testAction()
    {
       $customer_id = 162853;
      
       $collection = Mage::getModel('icc_premiumaccess/premiumaccess')->getCollection();
       $msa_eventType = Mage::getSingleton('core/resource')->getTableName('ecodes_premium_access_registry');            
       $collection->getSelect()->joinLeft(array('rg_access'=>$msa_eventType),'`main_table`.`id` = `rg_access`.`subscription_id`', array('rg_access.id as reg_id','IF( rg_access.parent_customer_id> 1 ,"NO", "YES") as parent_customer_id')); 
       
       $collection->addFieldToFilter(array('rg_access.assign_customer_id', 'main_table.customer_id'),
                                        array(
                                            array('eq'=> $customer_id), 
                                            array('eq'=> $customer_id)
                                        )
                                     );
       
       
       echo $collection->getSelect();
       exit;
        $collection      = Mage::getModel('icc_premiumaccess/registry')->getCollection(); 
        $collection->addFieldToSelect('id');
       $_resource_table = Mage::getSingleton('core/resource')->getTableName('ecodes_premium_access'); 
       $collection->getSelect()->join(array('rg_access'=>$_resource_table),'`main_table`.`subscription_id` = `rg_access`.`id`',array('rg_access.product_name', 'rg_access.sku', 'rg_access.expiration', 'rg_access.seats_total', 'rg_access.registered_count' , 'rg_access.notes', 'rg_access.status')); 
       $collection->addFieldToFilter('main_table.assign_customer_id',array('eq'=>$customer_id));
       $collection->addFieldToFilter('main_table.parent_customer_id',array('gt'=>0));
         echo $collection->getSelect();
       exit;
        
        
        $collection = Mage::getModel('icc_premiumaccess/registry')->getCollection();
        
        
        //$collection->addFieldToSelect(array('product_name', 'sku', 'expiration', 'seats_total', 'notes', 'status'));


        $msa_eventType = Mage::getSingleton('core/resource')->getTableName('ecodes_premium_access'); 
        $collection->getSelect()->joinRight(array('rg_access'=>$msa_eventType),'`main_table`.`subscription_id` = `rg_access`.`id`',array('rg_access.product_name', 'rg_access.sku', 'rg_access.expiration', 'rg_access.seats_total', 'rg_access.notes', 'rg_access.status')); 

        $customercollection = Mage::getResourceModel('customer/customer_collection')
        ->addNameToSelect()
        ->addAttributeToSelect('email'); 

        $collection->getSelect()->join(
        array('epa' => $customercollection->getSelect()), 'main_table.assign_customer_id=epa.entity_id', array('entity_id','name','email') 
        );

        $collection->addFieldToFilter('main_table.subscription_id',array('eq'=>'26'));
        
        echo $collection->getSelect();
    }
    public function test2Action()
    {
    $customer_id = 162852; 
       $collection = Mage::getModel('icc_premiumaccess/premiumaccess')->getCollection();
       $msa_eventType = Mage::getSingleton('core/resource')->getTableName('ecodes_premium_access_registry');            
       $collection->getSelect()->joinLeft(array('rg_access'=>$msa_eventType),'`main_table`.`id` = `rg_access`.`subscription_id`',array('IF( rg_access.parent_customer_id> 1 ,"NO", "YES") as parent_customer_id')); 
       
       $collection->addFieldToFilter(array('rg_access.assign_customer_id', 'main_table.customer_id'),
                                        array(
                                            array('eq'=> $customer_id), 
                                            array('eq'=> $customer_id)
                                        )
                                     );
       echo $collection->getSelect();
    }
      
    public function test3Action()
    {   
        $collection2 = Mage::getModel('icc_premiumaccess/premiumaccess')->getCollection();
        $collection2->addFieldToSelect(' * ');
        $msa_eventType = Mage::getSingleton('core/resource')->getTableName('ecodes_premium_access_registry'); 
        $collection2->getSelect()->joinRight(array('rg'=>$msa_eventType),'`main_table`.`id` = `rg`.`subscription_id`'); 
        $collection2->addFieldToFilter('rg.assign_customer_id',array('eq'=>162853));
                
            $collection1 = Mage::getModel('icc_premiumaccess/premiumaccess')->getCollection();    
                
            $collection1->getSelect()->union();
        
        echo $collection1->getSelect();
        exit;
        
    $collection = Mage::getModel('icc_premiumaccess/premiumaccess')->getCollection();
        
        
                $collection->addFieldToSelect('*');


                $msa_eventType = Mage::getSingleton('core/resource')->getTableName('ecodes_premium_access_registry'); 
                $collection->getSelect()->joinRight(array('rg'=>$msa_eventType),'`main_table`.`id` = `rg`.`subscription_id`'); 

               
                $collection->addFieldToFilter('rg.assign_customer_id',array('eq'=>162853));
                echo $collection->getSelect();
    }
    public function test4Action()
    {
    $customer_id = 162853; 
      
       $collection = Mage::getModel('icc_premiumaccess/registry')->getCollection();
        $msa_eventType = Mage::getSingleton('core/resource')->getTableName('ecodes_premium_access'); 
        $collection->getSelect()->joinRight(array('rg_access'=>$msa_eventType),'`main_table`.`subscription_id` = `rg_access`.`id`',array('rg_access.status')); 
        $collection->addFieldToFilter('main_table.subscription_id',array('eq'=>'1'));
        $collection->addFieldToFilter('rg_access.status',array('eq'=>'1'));
        echo $collection->getSelect();
        
    }
    
    public function  test5Action(){
         // This is the template name from your etc/config.xml 
        $template_id = 'premium_access_purchase';

        // Who were sending to...
        //$email_to = 'demo@example.com';
        $customer_name  =  'asasddadas';
        $product_name   = 'asddsadad';
        $Total_user     = 'asddsadsadsa'; 
        $expiration     = 'sadadasd';
        $myecode_url    = Mage::getUrl('ecodes/account/products');

        // Load our template by template_id
        $email_template  = Mage::getModel('core/email_template');
        
        
        
        
        $email_to= "abhijeet.jadhav@infobeans.com";
        // Here is where we can define custom variables to go in our email template!
        $email_template_variables = array(
            'customer_name' => $customer_name,
            'product_name' => $product_name,
            'users' => $Total_user,
            'duration' => $expiration,
            'myecode_url' => $myecode_url
            // Other variables for our email template.
        );

        // I'm using the Store Name as sender name here.
        $sender_name = Mage::getStoreConfig(Mage_Core_Model_Store::XML_PATH_STORE_STORE_NAME);
        // I'm using the general store contact here as the sender email.
        $sender_email = Mage::getStoreConfig('trans_email/ident_general/email');
        //$emailTemplate->setSenderName('anil jadhav');
        //$emailTemplate->setSenderEmail('anil.kasar@infobeans.com'); 
        
        $sender = array('name'=> 'anil' , 'email'=> 'anil.kasar@infobeans.com');

        //Send the email!
        $email_template->sendTransactional($template_id, $sender, $email_to, $customer_name, $email_template_variables);
    }
    
    public function crontestAction()
    {
        $model = Mage::getModel('icc_premiumaccess/premiumaccess');
        
        echo $model;
        echo "<br/>";
    }
    
    public function premiumAction(){
        $model = Mage::getModel('icc_premiumaccess/premiumaccess')->getCollection();
        $model->addFieldToFilter('customer_id',array('eq'=>'162853'));
        $model->addFieldToFilter('status',array('eq'=>1));
        //echo $model->getSelect(); exit;
        $prem = array();
        foreach ($model as $m):
            array_push($prem,$m->getId());
        endforeach;
        print_r($prem);
        echo "<br/>"; 
        $model = Mage::getModel('icc_premiumaccess/registry')->getCollection();
        $model->addFieldToFilter('assign_customer_id',array('eq'=>'162853'));
        $model->addFieldToFilter('status',array(array('eq'=>0),array('eq'=>1)));
        //echo $model->getSelect(); exit;
       print_r($model->getData());
       $premi = array();
       foreach ($model as $m):
            array_push($premi,$m->getSubscriptionId());
        endforeach;
        print_r($premi);
        echo "<br/>"; 
       $merge = array_merge($prem,$premi);
        print_r($merge);
        echo "<br/>"; 
       //$uniq = array_unique($merge, SORT_REGULAR);
       //$print_r($uniq);
       $array = array_unique (array_merge ($prem, $premi));
       print_r($array);
       echo "<pre>";
       $model = Mage::getModel('icc_premiumaccess/premiumaccess')->getCollection();
       $model->addFieldToFilter('id',array('in'=>$array));
       print_r($model->getData());
    }
    

    
  /*  
SELECT `main_table`.* FROM `ecodes_premium_access` AS `main_table` WHERE (customer_id = '162853')
UNION
SELECT `main_table`.* FROM `ecodes_premium_access` AS `main_table` left JOIN `ecodes_premium_access_registry` AS `rg` 
   ON `rg`.`subscription_id` = `main_table`.`id` WHERE (rg.assign_customer_id = 162853)

   * SELECT `main_table`.* FROM `ecodes_premium_access` AS `main_table` WHERE (customer_id = '162853')
UNION
SELECT `main_table`.* FROM `ecodes_premium_access` AS `main_table` left JOIN `ecodes_premium_access_registry` AS `rg` 
   ON `rg`.`subscription_id` = `main_table`.`id` WHERE (rg.assign_customer_id = 162853)
   * 
   *    */
}
