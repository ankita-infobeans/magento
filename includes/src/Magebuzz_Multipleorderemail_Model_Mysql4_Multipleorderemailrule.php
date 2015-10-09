<?php
/**
 * @category    Magebuzz
 * @package     Magebuzz_Multipleorderemail
 */
class Magebuzz_Multipleorderemail_Model_Mysql4_Multipleorderemailrule extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        $this->_init('multipleorderemail/multipleorderemailrule', 'rule_id');
    }

    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        $oldStores = $this->listStoreIds($object->getId());   
        $newStores = (array)$object->getStoreId();    
        $table  = $this->getTable('multipleorderemail/multipleorderemailstore');
        $insert = array_diff($newStores, $oldStores);
        $delete = array_diff($oldStores, $newStores);

        if ($delete) {
            $where = array(
                'multipleorderemail_id = ?'     => (int) $object->getId(),
                'store_id IN (?)' => $delete
            );
            $this->_getWriteAdapter()->delete($table, $where);
        }

        if ($insert) {
          $data = array();
            foreach ($insert as $storeId) {
                $data[] = array(
                    'multipleorderemail_id'  => (int) $object->getId(),
                    'store_id' => (int) $storeId
                );
            }
            $this->_getWriteAdapter()->insertMultiple($table, $data);
        }
        return parent::_afterSave($object);
    }

    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        if ($object->getId()) {
            $stores = $this->listStoreIds($object->getId());
            $object->setData('store_id', $stores);
        }
        return parent::_afterLoad($object);
    }

    public function listStoreIds($ruleId)
    {
        $adapter = $this->_getReadAdapter();
        $select  = $adapter->select()
            ->from($this->getTable('multipleorderemail/multipleorderemailstore'), 'store_id')
            ->where('multipleorderemail_id = ?',(int)$ruleId);

        return $adapter->fetchCol($select);
    }
  
    public function getlistRuleIds($stores)
    {
        $adapter = $this->_getReadAdapter();
        $select  = $adapter->select()
            ->from($this->getTable('multipleorderemail/multipleorderemailstore'), 'multipleorderemail_id')
            ->where('store_id IN (?) ',$stores);
        return $adapter->fetchCol($select);
    }
}