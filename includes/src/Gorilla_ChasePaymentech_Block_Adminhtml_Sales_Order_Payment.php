<?php

class Gorilla_ChasePaymentech_Block_Adminhtml_Sales_Order_Payment extends Mage_Adminhtml_Block_Sales_Order_Payment
{
    public function setPayment($payment)
    {
		$paymentInfoBlock = Mage::helper('payment')->getInfoBlock($payment);
		$paymentInfoBlock->setTemplate('chasepaymentech/sales/order/payment-info.phtml');
		$this->setChild('info', $paymentInfoBlock);
        return $this;
    }

	protected function _beforeToHtml()
    {
        if (!$this->getParentBlock()) {
            Mage::throwException(Mage::helper('adminhtml')->__('Invalid parent block for this block'));
        }
        parent::_beforeToHtml();
    }

    protected function _toHtml()
    {
        return $this->getChildHtml('info');
    }
}
