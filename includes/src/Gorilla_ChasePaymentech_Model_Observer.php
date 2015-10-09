<?php


class Gorilla_ChasePaymentech_Model_Observer extends Mage_Core_Model_Observer
{
    /**
     * Event: customer_delete_before
     *
     * @param $observer
     */
    public function deleteCustomer($observer)
    {
        $refNum = $observer->getCustomer()->getChasePaymentechCustomerRefNum();
        Mage::getModel('chasepaymentech/profile')->deleteCustomer($refNum);
    }

    /**
     * Event: customer_load_after
     *
     * Attach the customer's CIM id to the customer profile when loaded
     *
     * @param type $observer
     * @return Gorilla_AuthorizenetCim_Model_Observer
     */
    public function loadPaymentechInfo($observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        Mage::getModel('chasepaymentech/profile')->loadInfoByCustomer($customer);
        return $this;
    }

    /**
     * Event: checkout_submit_all_after
     *
     * Cleanup session data
     */
    public function cleanUpCheckoutSession()
    {
        Mage::getSingleton('checkout/session')
            ->unsChasePaymentechCustomerRefNum()
            ->unsChasePaymentechProfileId();
    }

    /**
     * Event: sales_convert_order_to_quote
     *
     * Converting an order to a quote brings over payment information that we
     * really don't want to copy to a new order. This removes that.
     *
     * @param $observer
     * @return Gorilla_ChasePaymentech_Model_Observer
     */
    public function cleanUpPaymentInformation($observer)
    {
        $quote = $observer->getQuote();
        $quote->getPayment()->unsAdditionalInformation();
        return $this;
    }
}
