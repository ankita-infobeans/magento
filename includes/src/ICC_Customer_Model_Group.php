<?php
/**
 * Created by Ariel Allon @ Gorilla
 *    aallon@gorillagroup.com
 * Creation date: 9/20/12 1:02 PM
 */

class ICC_Customer_Model_Group extends Mage_Customer_Model_Group
{
    /**
     * Unfortunately, Magento does not consistently use a single path to request a customer's tax class id.
     * Therefore, we need to overwrite this function, which is called occasionally and circumvents the getTaxClassId()
     * function in Mage_Customer_Model_Customer.
     * If we are to use the Avectra Tax Exempt Status, this function never returns and we rely on the value set in
     * ICC_Customer_Model_Customer::getTaxClassId();
     *
     * @param null $groupId
     * @return mixed
     */
    public function getTaxClassId($groupId = null)
    {
        $useAvectraTaxExemptStatus = Mage::getModel('customer/customer')->useAvectraTaxExemptStatus();
        if ($useAvectraTaxExemptStatus) {
            return parent::getTaxClassId(null);
        }
        return parent::getTaxClassId($groupId);
    }
}