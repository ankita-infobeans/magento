<?php
class ICC_Checkout_Block_Onepage_Billing extends Mage_Checkout_Block_Onepage_Billing{
	
	/**
     * Initialize billing address step
     *
     */
	protected function _construct()
	{
		$this->getCheckout()->setStepData('billing', array(
				'label'     => Mage::helper('checkout')->__('Billing Address'),
				'is_show'   => $this->isShow()
		));
	
		if ($this->isCustomerLoggedIn()) {
			$this->getCheckout()->setStepData('billing', 'allow', true);
		}
		//parent::_construct();
	}
	
	private $__has_invalid_address;
	private $__has_valid_address;
	
	public function getHasInvalidAddress() {
		return $this->__has_invalid_address;
	}
	public function setHasInvalidAddress($bool) {
		$this->__has_invalid_address = (bool) $bool;
	}
	
	public function getHasValidAddress() {
		return $this->__has_valid_address;
	}
	public function setHasValidAddress($bool) {
		$this->__has_valid_address = (bool) $bool;
	}
	
	public function hasInvalidAddress() {
		if(is_null( $this->getHasInvalidAddress() )) $this->testAndSetValidAddresses();
		return $this->getHasInvalidAddress();
	}
	
	public function hasValidAddress() {
		if(is_null( $this->getHasValidAddress() )) $this->testAndSetValidAddresses();
		return $this->getHasValidAddress();
	}
	
	private function testAndSetValidAddresses()
	{
		$valid_addresses = array();
		$invalid_addresses = array();
		foreach ($this->getCustomer()->getAddresses() as $address) {
			if( $address->validate() === true) {
				$valid_addresses[] = $address; // at least one address is invalid
			} else {
				$invalid_addresses[] = $address;
			}
		}
		$this->setHasInvalidAddress( (bool) count( $invalid_addresses ));
		$this->setHasValidAddress( (bool) count( $valid_addresses ));
	}
	
	public function customerHasAddresses()
	{
		return $this->hasValidAddress();
	}
	
	public function getAddressesHtmlSelect($type) {
	
		if ($this->isCustomerLoggedIn()) {
			$options = array();
			foreach ($this->getCustomer()->getAddresses() as $address) {
				if( $address->validate() === true)
				{
					$options[] = array(
							'value' => $address->getId(),
							'label' => $address->format('oneline')
					);
				}
			}
	
			$addressId = $this->getAddress()->getCustomerAddressId();
			if (empty($addressId)) {
				if ($type=='billing') {
					$address = $this->getCustomer()->getPrimaryBillingAddress();
				} else {
					$address = $this->getCustomer()->getPrimaryShippingAddress();
				}
				if ($address) {
					$addressId = $address->getId();
				}
			}
	
			$select = $this->getLayout()->createBlock('core/html_select')
			->setName($type.'_address_id')
			->setId($type.'-address-select')
			->setClass('address-select')
			->setExtraParams('onchange="'.$type.'.newAddress(!this.value)"')
			->setValue($addressId)
			->setOptions($options);
	
			$select->addOption('', Mage::helper('checkout')->__('New Address'));
	
			return $select->getHtml();
		}
		return '';
	
	}
	
}
?>