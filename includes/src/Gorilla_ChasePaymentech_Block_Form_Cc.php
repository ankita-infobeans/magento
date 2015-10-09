<?php

/**
 *
 */
class Gorilla_ChasePaymentech_Block_Form_Cc extends Mage_Payment_Block_Form
{
    /**
     * Prepare the form template
     */
    public function _prepareLayout()
    {
        $this->setTemplate('chasepaymentech/form/cc.phtml');
    }

    /**
     * Check to see if we're inside the admin panel
     *
     * @return bool
     */
    public function isAdmin()
    {
        return Mage::app()->getStore()->isAdmin();
    }

    /**
     * Retrieve the customer for this quote
     *
     * @return Mage_Customer_Model_Customer
     */
    protected function getCustomer()
    {
        if($this->isAdmin()) {
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
        if (!$this->isAdmin()) {
            if (Mage::helper('customer')->isLoggedIn()) {
                return true;
            }
            if (Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress()->getSaveInAddressBook()) {
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
        if ($this->getMethod()) {
            return $this->getMethod()->getConfigData('save_optional');
        }
        return false;
    }

    /**
     * Determine if this is a guest checkout
     *
     * @return bool
     */
    public function isGuest()
    {
        if (Mage::getSingleton('checkout/session')->getQuote()->getCheckoutMethod() == Mage_Checkout_Model_Type_Onepage::METHOD_GUEST) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return Mage_Core_Model_Abstract
     */
    protected function _getConfig()
    {
        return Mage::getSingleton('payment/config');
    }

    /**
     * @return mixed
     */
    public function getStoredCards()
    {
        if (!$this->getData('cards')) {
            $customer = Mage::getModel('customer/session')->getCustomer();
            // echo $customer->getEntityId();
            $cards = Mage::getModel('chasepaymentech/profile')->getCustomerCards($customer->getId());

            if (!empty($cards)) {
                $this->setData('cards', $cards);
            } else {
                $this->setData('cards',false);
            }
        }

        return $this->getData('cards');
    }

    /**
     * Get a list of stored credit cards
     *
     * @return array $cards | bool
     */
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

    /**
     * @return array
     */
    public function getMonths()
    {
        $raw_data = Mage::app()->getLocale()->getTranslationList('month');
        if ($this->getCimMode() == 'Edit') {
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
            if ($this->getCimMode() == 'Edit') {
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
            ->setTitle($this->__('Country'))
            ->setClass('validate-select required-entry')
            ->setValue($countryId)
            ->setOptions($this->getCountryOptions());

        return $select->getHtml();
    }
}
