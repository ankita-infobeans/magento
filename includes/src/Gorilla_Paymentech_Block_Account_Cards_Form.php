<?php
class Gorilla_Paymentech_Block_Account_Cards_Form extends Mage_Customer_Block_Account_Dashboard
{
    public function getMethodCode()
    {
        return Mage::getSingleton('paymentech/gateway')->getCode();
    }
    
    public function isSaveOptional()
    {
        return Mage::getStoreConfig('paymentech/save_optional');
    }    
    
    public function hasVerification()
    {
        return true;
        return Mage::getModel('paymentech/gateway')->getConfigData('cctypes');
    }
    
    /**
     * Retrieve payment configuration object
     *
     * @return Mage_Payment_Model_Config
     */
    protected function _getConfig()
    {
        return Mage::getSingleton('payment/config');
    }

    /**
     * Retrieve availables credit card types
     *
     * @return array
     */
    public function getCcAvailableTypes()
    {
        $types = $this->_getConfig()->getCcTypes();
        $availableTypes = explode(',',Mage::getModel('paymentech/gateway')->getConfigData('cctypes'));
        if ($availableTypes) {
            foreach ($types as $code=>$name) {
                if (!in_array($code, $availableTypes)) {
                    unset($types[$code]);
                }
            }
        }
        
        return $types;
    }
    
    /**
     * Retrieve list of months translation
     *
     * @return array
     */
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
    
    public function getCountryOptions()
    {
        $options    = false;
        $useCache   = Mage::app()->useCache('config');
        if ($useCache) {
            $cacheId    = 'DIRECTORY_COUNTRY_SELECT_STORE_' . Mage::app()->getStore()->getCode();
            $cacheTags  = array('config');
            if ($optionsCache = Mage::app()->loadCache($cacheId)) {
                $options = unserialize($optionsCache);
            }
        }

        if ($options == false) {
            $options = $this->getCountryCollection()->toOptionArray();
            if ($useCache) {
                Mage::app()->saveCache(serialize($options), $cacheId, $cacheTags);
            }
        }
        return $options;
    }
    
    public function getCountryCollection()
    {
        if (!$this->_countryCollection) {
            $this->_countryCollection = Mage::getSingleton('directory/country')->getResourceCollection()
                ->loadByStore();
        }
        return $this->_countryCollection;
    }
    
    public function getFormData($key)
    {      
        $formData = Mage::registry('form_data');
        
        if (isset($formData['payment'][$key]))
        {
            return $formData['payment'][$key];
        }
    }
    
    public function getBackUrl()
    {
        return $this->getUrl('*/*/cards');
    }
    
    protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }
}