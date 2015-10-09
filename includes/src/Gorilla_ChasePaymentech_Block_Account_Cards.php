<?php
class Gorilla_ChasePaymentech_Block_Account_Cards extends Mage_Customer_Block_Account_Dashboard
{
    /**
     * Get a list of credit cards for the account
     *
     * @return array $cards|bool
     */
    public function getCards()
    {
        if (!$this->getData('cards')) {
            $customer = Mage::getModel('customer/session')->getCustomer();
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
     * @param $card
     * @return string
     */
    public function getEditUrl($card)
    {
        return Mage::getUrl('*/*/edit/id/' . $card->getId(), array('_secure' => true));
    }

    /**
     * @param $card
     * @return string
     */
    public function getDeleteUrl($card)
    {
        return Mage::getUrl('*/*/delete/id/'. $card->getId(), array('_secure' => true));
    }
}
