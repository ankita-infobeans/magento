<?php
class Gorilla_Paymentech_Block_Account_Cards extends Mage_Customer_Block_Account_Dashboard
{
    /**
     * Get a list of credit cards for the account
     * 
     * @return array $cards|bool 
     */
    public function getCards()
    {        
        if (!$this->getData('cards'))
        {
            $cards = array(); // Array to hold card objects
            
            $customer = Mage::getModel('customer/session')->getCustomer();
            
           // echo $customer->getEntityId();
            $cim_profile = Mage::getModel('paymentech/profile')
                    ->getCustomerProfile($customer->getEntityId());

            if ($cim_profile)
            {              
               
                
                    /**
                     * The Soap XML response may be a single stdClass or it may be an
                     * array. We need to adjust it to make it uniform. 
                     */            
                    if (is_array($cim_profile))
                    {
                        $payment_profiles = $cim_profile;
                    } else {
                        $payment_profiles = array($cim_profile);
                    }
    
                    // Assign card objects to array
                    foreach ($payment_profiles as $payment_profile)
                    {
                        try{

                        if(isset($payment_profile->customerCountryCode))
                        {
                            $country = $payment_profile->customerCountryCode;
                        }else{
                            $country = "US";
                        }
                        $card = new Varien_Object();
                        $card->setCcNumber($payment_profile->ccAccountNum)
                                ->setId($payment_profile->id)
                                ->setCustomerRefNum($payment_profile->customerRefNum)
                                ->setName($payment_profile->customerName)
                                ->setAddress($payment_profile->customerAddress1)
                                ->setCity($payment_profile->customerCity)
                                ->setState($payment_profile->customerState)
                                ->setZip($payment_profile->customerZIP)
                                ->setCountry($country)
                                ;
                        

                        $cards[] = $card;
                        }catch(Exception $e){}
                    }   
                

            }
           
            if (!empty($cards))
            {
                $this->setData('cards', $cards);
            } else {            
                $this->setData('cards',false);
            }
        }
        
        return $this->getData('cards');
        
    }
    
    public function getEditUrl($card)
    {
        return Mage::getUrl('*/*/edit/id/' . $card->getId(), array('_secure' => true));
    }
    
    public function getDeleteUrl($card)
    {
        return Mage::getUrl('*/*/delete/id/'. $card->getId(), array('_secure' => true));
    }
}