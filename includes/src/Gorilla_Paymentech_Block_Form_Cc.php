<?php
class Gorilla_Paymentech_Block_Form_Cc extends Mage_Payment_Block_Form
{
    /**
     * Prepare the form template
     */
    public function _prepareLayout()
    {
        $this->setTemplate('paymentech/form/cc.phtml');
    }
    
    /**
     * Check to see if we're inside the admin panel
     * 
     * @return bool
     */
    public function isAdmin()
    {
        if (Mage::app()->getStore()->isAdmin())
        {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Retrieve the customer for this quote
     * 
     * @return Mage_Customer_Model_Customer 
     */
    protected function getCustomer()
    {
        if($this->isAdmin())
        {
            return Mage::getModel('customer/customer')->load(Mage::getSingleton('adminhtml/session_quote')->getCustomerId()); // Get customer from admin panel quote
        } else {
            return Mage::getModel('customer/session')->getCustomer(); // Get customer from frontend quote
        }
    }
    
    /**
     * Logged in check
     * 
     * @return bool
     */
    public function isLoggedIn()
    {
        if (!$this->isAdmin())
        {
            if (Mage::helper('customer')->isLoggedIn())
            {
                return true;
            }            
            
            if (Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress()->getSaveInAddressBook())
            {
                return true;
            }
            
            return false;
            
        } else {
            return true; // If this is the admin panel, we just assume we're logged in
        }
        
    }    
    
    /**
     * Check to see if saving the CC is optional or not
     * 
     * @return bool 
     */
    public function isSaveOptional()
    {
        return true;
        if ($this->getMethod()) 
        {
           
           // $configData = $this->getMethod()->getConfigData('save_optional');
            //return $configData;
        }
        return false;
    }
    
    /**
     * Determine if this is a guest checkout
     */
    public function isGuest()
    {
        if (Mage::getSingleton('checkout/session')->getQuote()->getCheckoutMethod() == Mage_Checkout_Model_Type_Onepage::METHOD_GUEST)
        {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Get a list of stored credit cards
     * 
     * @return array $cards | bool 
     */
   
    
    
    public function getStoredCards()
    {        
        if (!$this->getData('stored_cards'))
        {
            $cards = array(); // Array to hold card objects
            
            $customer = Mage::getModel('customer/session')->getCustomer();
            
            
            $cim_profile = Mage::getModel('paymentech/profile')
                    ->getCustomerProfile($customer->getEntityId());
            
           
            //print_r($cim_profile->getData());
           // $cim_profile = Mage::getModel('paymentech/profile')->getCustomerProfile($customer->getEntityId());
           // foreach($cim_profile as $single)
           // {
           //     print_r($single->getData());
           // }
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
                   // print_r($payment_profiles);

                    // Assign card objects to array
                    foreach ($payment_profiles as $payment_profile)
                    {
                        
                      
                        try{
                            
                    
                        $card = new Varien_Object();
                        $card->setCcNumber($payment_profile->ccAccountNum)
                                ->setCustomerRefNum($payment_profile->customerRefNum)
                                        ->setProfileId($payment_profile->id)
                                ->setName($payment_profile->customerName)
                                ->setAddress($payment_profile->customerAddress1)
                                ->setCity($payment_profile->customerCity)
                                ->setState($payment_profile->customerState)
                                ->setZip($payment_profile->customerZIP)
                                ->setCountry("US")
                                ;

                        $cards[] = $card;
                        }
                        catch(Exception $e){}
                    }   
                

            }
           
            if (!empty($cards))
            {
                $this->setData('stored_cards', $cards);
            } else {            
                $this->setData('stored_cards',false);
            }
        }
        
        return $this->getData('stored_cards');
        
    }
    
    public function getCcAvailableTypes()
    {
        $types = $this->_getConfig()->getCcTypes();
        if ($method = $this->getMethod()) {
            $availableTypes = $method->getConfigData('cctypes');
            if ($availableTypes) {
                $availableTypes = explode(',', $availableTypes);
                foreach ($types as $code=>$name) {
                    if (!in_array($code, $availableTypes)) {
                        unset($types[$code]);
                    }
                }
            }
        }
        return $types;
    }

    
     
    
    public function getMonths()
    {
        $raw_data = Mage::app()->getLocale()->getTranslationList('month');
        
        if ($this->getCimMode() == 'Edit')
        {
            $formatted_data = array('XX' => 'XX');   
        } else {
            $formatted_data = array('' => 'Month');
        }        
        
        foreach ($raw_data as $key => $value) {
            $monthNum = ($key < 10) ? '0'.$key : $key;
            $formatted_data[$monthNum] = $monthNum . ' - ' . $value;
        }
        return $formatted_data;
    }
    
    /**
     * Retrieve credit card expire months
     *
     * @return array
     */
    public function getCcMonths()
    {
        $months = $this->getData('cc_months');
        if (is_null($months)) {
            $months[0] =  $this->__('Month');
            $months = $this->getMonths();
            $this->setData('cc_months', $months);
        }
        return $months;
    }
    
      protected function _getConfig()
    {
        return Mage::getSingleton('payment/config');
    }
    
    
    /**
     * Retrieve credit card expire years
     *
     * @return array
     */
    public function getCcYears()
    {
        $years = $this->getData('cc_years');
        if (is_null($years)) {
            $years = $this->_getConfig()->getYears();
            if ($this->getCimMode() == 'Edit')
            {
                $years = array('XX'=>$this->__('XXXX'))+$years;
            } else {
                $years = array(0=>$this->__('Year'))+$years;
            }
            $this->setData('cc_years', $years);
        }
        return $years;
    }
    
    public function getCountryHtmlSelect($type)
    {
        $countryId = $this->getFormData('cc_country_id');
        if (is_null($countryId)) {
            $countryId = Mage::helper('core')->getDefaultCountry();
        }
        
        $select = $this->getLayout()->createBlock('core/html_select')
            ->setName($type.'[cc_country_id]')
            ->setId($type.':country_id')
            ->setTitle(Mage::helper('paymentech')->__('Country'))
            ->setClass('validate-select required-entry')
            ->setValue($countryId)
            ->setOptions($this->getCountryOptions());

        return $select->getHtml();
    }
}