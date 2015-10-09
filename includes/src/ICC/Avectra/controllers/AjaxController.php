<?php
class ICC_Avectra_AjaxController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $email = $this->getRequest()->getParam('customer_email');
        if($email){
            $fields = array('response' => false, 'customer_exists' => 1);
            $account = Mage::getModel('icc_avectra/account');
            $avectra = Mage::getModel('icc_avectra/avectraCommunication');
            $customer =  $account->getByEmail($email);
            if($customer){
                if($avectra_key = $customer->getAvectraKey()){
                    $user_info = $avectra->getUserInfo($avectra_key);
                } else{
                    $user_info = $avectra->getUserInfoByEmail($email);
                }
            } else {
                $fields['customer_exists'] = 0;
                $user_info = $avectra->getUserInfoByEmail($email);
            }

            if($user_info){
                $data = false;
                if(isset($user_info->IndividualObject)){
                    $data = $user_info->IndividualObject;
                } else if (isset($user_info->Individual)){
                    $data = $user_info->Individual;
                }

                if($data){
                    $orgs = $avectra->getUserAffiliatedOrganizations($avectra_key, true);
                    if ($orgs) {
                        $org_name = $orgs[0]->Organization->org_name;
                        $fields['orgname'] = (array)$org_name;
                    }
                    $fields['response'] = true;
                    $fields['firstname'] = (array)$data->ind_first_name;
                    $fields['lastname'] = (array)$data->ind_last_name;
                    $fields['avectrakey'] = (array)$data->ind_cst_key;
                    $fields['emailavectrakey'] = isset($data->cst_eml_key);
                    $fields['password'] = substr(md5(mt_rand()), 0, 10);
                }
            }
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($fields));
        }
        
        return;
    }

    public function accountCustomerAction()
    {
        $avectra_key = $this->getRequest()->getParam('avectra_key');
        $action = $this->getRequest()->getParam('action', 'create');
        $data = array('success' => false);
        if($avectra_key){
            $account = Mage::getModel('icc_avectra/account');
            if($action == 'update'){
                $customer = $account->updateUser($avectra_key);
            } else {
                $customer = $account->createNewUser($avectra_key);
            }
            if($customer->getId()){
                $data['customer_id'] = $customer->getId();
                $data['success'] = true;
            }
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($data));

        return;
    }

    public function updateCustomerAction()
    {

    }
    
    /*
     * Update customer First and Last Name on magento DB
     * Input file contains email, first and last name
     */
     public function customerUpdateMagentoAction()
    {   
        ini_set('max_execution_time', 0);
        $filename = "wsdl/customer-update.csv";
        if (($handle = fopen($filename, 'r')) !== FALSE)
        {
            while (($emails = fgetcsv($handle, 1000, ",")) !== FALSE) 
            {
              if (!empty($emails[0]) && !empty($emails[1]) && !empty($emails[2])){
                  $customer = Mage::getModel("customer/customer");
                  $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
                  $customer->loadByEmail($emails[0]);
                  $customer->setFirstname($emails[1]);
                  $customer->setLastname($emails[2]);
                  try{
                      $customer->save();
                      Mage::log('Customer Updated :' . $emails[0] , null, 'customerUpdateMagento.log', true);
                  }catch (Exception $e) {
                      Mage::log('Customer Update Failed :' . $emails[0] . $e->getMessage(), null, 'customerUpdateMagento.log', true);
                 }
                  echo $emails[0] .'--'. $emails[1] .'--'. $emails[2];echo '<br>';
              }
            }
        }
    }
    
    /*
     * Update customer Name on magento DB
     * Input file contains email, first and last name
     */
     public function customerUpdateAvectraAction()
    {   ini_set('max_execution_time', 0);
        $filename = "wsdl/customer-update.csv";
        if (($handle = fopen($filename, 'r')) !== FALSE)
        {
            while (($emails = fgetcsv($handle, 1000, ",")) !== FALSE) 
            {
                if (!empty($emails[0]) && !empty($emails[1]) && !empty($emails[2])){
                    $account = Mage::getModel('icc_avectra/account');
                    $avectra = Mage::getModel('icc_avectra/avectraCommunication');
                    $customer =  $account->getByEmail($emails[0]);                      
                    if($customer){                        
                        if($avectra_key = $customer->getAvectraKey()){                           
                            $flag = $avectra->updateAvectra($avectra_key);
                            if ( $flag ){
                                Mage::log('Customer Update on Avectra : ' . $emails[0] , null, 'customerUpdateAvectra.log', true);
                            }else {
                                Mage::log('Customer Update Failed on Avectra : ' . $emails[0] , null, 'customerUpdateAvectra.log', true);
                            }
                        }
                    }
                }
            }
        }
    }

    /*
    * Fetch the customer data from Billing address
    */
    public function getICCCustomerBillingAction(){
               $collection = Mage::getResourceModel('customer/customer_collection')
                        ->addNameToSelect()
                        ->joinAttribute('billing_firstname', 'customer_address/firstname', 'default_billing', null, 'left')
                        ->joinAttribute('billing_lastname', 'customer_address/lastname', 'default_billing', null, 'left')
                        ->addAttributeToSelect('email')  
                        ->addFieldToFilter('firstname', 'ICC');
        $customerData = $collection->getData();   
        foreach($customerData as $customer){
            if(isset($customer['billing_firstname']) && $customer['billing_firstname'] != 'ICC'){                          
                   echo $customer['email'].','.$customer['billing_firstname'].','.$customer['billing_lastname'];echo '<br>';
            }
        }
     }
   
    /*
    * Fetch the ICC Customer data from customer EAV
    */
    public function getICCCustomerAction(){
               $collection = Mage::getResourceModel('customer/customer_collection')
                        ->addNameToSelect()
                        ->addAttributeToSelect('email')  
                        ->addFieldToFilter('firstname', 'ICC')
                        ->addFieldToFilter('lastname', 'Customer');
        $customerData = $collection->getData();
        echo '<H3>The Count of ICC Customer on Store Database are :'.count($customerData); echo '</H3>';echo '<br>';
        foreach($customerData as $customer){
                   echo $customer['email'].','.$customer['firstname'].','.$customer['lastname'];echo '<br>';
        }
     }


    /*
     * Fetch the customer data from avetcra server
     */
    public function CustomerDataFromAvectraAction()
    {  
        $filename = "wsdl/ICC_Customers-emails.csv";
        if (($handle = fopen($filename, 'r')) !== FALSE)
        {
            while (($emails = fgetcsv($handle, 1000, ",")) !== FALSE)
            {
                    $email = $emails['0'];
                    if($email){
                    $fields = array('response' => false, 'customer_exists' => 1);
                    $account = Mage::getModel('icc_avectra/account');
                    $avectra = Mage::getModel('icc_avectra/avectraCommunication');
                    $customer =  $account->getByEmail($email);

                    if($customer){
                        if($avectra_key = $customer->getAvectraKey()){
                            $user_info = $avectra->getUserInfo($avectra_key);
                        } else{
                            $user_info = $avectra->getUserInfoByEmail($email);
                        }
                    } else {
                        $fields['customer_exists'] = 0;
                        $user_info = $avectra->getUserInfoByEmail($email);
                    }

                    if($user_info){
                        $data = false;
                        if(isset($user_info->IndividualObject)){
                            $data = $user_info->IndividualObject;
                        } else if (isset($user_info->Individual)){
                            $data = $user_info->Individual;
                        }
                       if ($data->ind_first_name != 'ICC'){
                        echo $email.','.$data->ind_first_name.','.$data->ind_last_name;echo '<br>';
                         Mage::log( $email . ' : ' . $data->ind_first_name . ' ' . $data->ind_last_name, null, 'avectra-customer-data-new.log', true);
                       }
                    }
                }              
            }
        }
    }  
}
