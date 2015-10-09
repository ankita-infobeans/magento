<?php

/**
 *
 */
class Gorilla_ChasePaymentech_Model_Mysql4_Profile extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('chasepaymentech/profile','id');
    }

    /**
     * Sets the default credit card information
     *
     * @param Mage_Core_Model_Abstract $object
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        parent::_beforeSave($object);

        if ($object->getflagAsDefault()) {
            $adapter = $this->_getWriteAdapter();
            $adapter->query($adapter->quoteInto("UPDATE {$this->getMainTable()} SET is_default = 0 WHERE customer_id = ?", $object->getCustomerId()));
            $object->setIsDefault(true);
        }

        return $this;
    }

    /**
     * Fetch dealer IDs for a given customer and attach to the customer object
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return Gorilla_ChasePaymentech_Model_Mysql4_Profile
     */
    public function loadInfoByCustomer($customer)
    {
        $read = $this->_getReadAdapter();
        if ($read) {
            $select = $read->select()
                ->from($this->getMainTable(), array('id','customer_ref_num','is_default'))
                ->where('customer_id = ?', $customer->getId());
            if ($data = $read->fetchRow($select)) {
                $customer->setChasePaymentechCustomerRefNum($data['customer_ref_num'])
                    ->setChasePaymentechProfileId($data['id']);
            }
        }

        return $this;
    }
}
