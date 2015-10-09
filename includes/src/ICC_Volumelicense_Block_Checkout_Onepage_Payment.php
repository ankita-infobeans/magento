<?php
class ICC_Volumelicense_Block_Checkout_Onepage_Payment extends Mage_Checkout_Block_Onepage_Payment{
	
	protected function _construct()
	{
		$this->getCheckout()->setStepData('payment', array(
				'label'     => $this->__('Payment Method'),
				'is_show'   => $this->isShow()
		));
		//parent::_construct();
	}
}
?>