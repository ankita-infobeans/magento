<?php
/**
 * Free Resource resource model
 *
 * @category    ICC
 * @package     ICC_Freeresources
 */
class ICC_Freeresources_Model_Resource_Freeresource
    extends Mage_Core_Model_Resource_Db_Abstract {
    /**
     * constructor
     * @access public
     */
    public function _construct(){
        $this->_init('icc_freeresources/freeresource', 'entity_id');
    }
    /**
     * check url key
     * @access public
     * @param string $urlKey
     * @param int $storeId
     * @param bool $active
     * @return mixed

     */
//    public function checkUrlKey($urlKey, $storeId, $active = true){
//        $stores = array(Mage_Core_Model_App::ADMIN_STORE_ID, $storeId);
//        $select = $this->_initCheckUrlKeySelect($urlKey, $stores);
//        if ($active) {
//            $select->where('e.status = ?', $active);
//        }
//        $select->reset(Zend_Db_Select::COLUMNS)
//            ->columns('e.entity_id')
//            ->limit(1);
//
//        return $this->_getReadAdapter()->fetchOne($select);
//    }

    /**
     * Check for unique URL key
     * @access public
     * @param Mage_Core_Model_Abstract $object
     * @return bool

     */
//    public function getIsUniqueUrlKey(Mage_Core_Model_Abstract $object){
//        if (Mage::app()->isSingleStoreMode() || !$object->hasStores()) {
//            $stores = array(Mage_Core_Model_App::ADMIN_STORE_ID);
//        }
//        else {
//            $stores = (array)$object->getData('stores');
//        }
//        $select = $this->_initCheckUrlKeySelect($object->getData('url_key'), $stores);
//        if ($object->getId()) {
//            $select->where('e.entity_id <> ?', $object->getId());
//        }
//        if ($this->_getWriteAdapter()->fetchRow($select)) {
//            return false;
//        }
//        return true;
//    }
    /**
     * Check if the URL key is numeric
     * @access public
     * @param Mage_Core_Model_Abstract $object
     * @return bool

     */
    protected function isNumericUrlKey(Mage_Core_Model_Abstract $object){
        return preg_match('/^[0-9]+$/', $object->getData('url_key'));
    }
    /**
     * Checkif the URL key is valid
     * @access public
     * @param Mage_Core_Model_Abstract $object
     * @return bool

     */
    protected function isValidUrlKey(Mage_Core_Model_Abstract $object){
        return preg_match('/^[a-z0-9][a-z0-9_\/-]+(\.[a-z0-9_-]+)?$/', $object->getData('url_key'));
    }
    /**
     * format string as url key
     * @access public
     * @param string $str
     * @return string

     */
    public function formatUrlKey($str) {
        $urlKey = preg_replace('#[^0-9a-z]+#i', '-', Mage::helper('catalog/product_url')->format($str));
        $urlKey = strtolower($urlKey);
        $urlKey = trim($urlKey, '-');
        return $urlKey;
    }
    /**
     * init the check select
     * @access protected
     * @param string $urlKey
     * @param array $store
     * @return Zend_Db_Select

     */
//    protected function _initCheckUrlKeySelect($urlKey, $store){
//        $select = $this->_getReadAdapter()->select()
//            ->from(array('e' => $this->getMainTable()))
//            ->where('e.url_key = ?', $urlKey);
//        return $select;
//    }
    /**
     * validate before saving
     * @access protected
     * @param $object
     * @return ICC_Freeresources_Model_Resource_Freeresource

     */
//    protected function _beforeSave(Mage_Core_Model_Abstract $object){
//        $urlKey = $object->getData('url_key');
//        if ($urlKey == '') {
//            $urlKey = $object->getFreeResource();
//        }
//        $urlKey = $this->formatUrlKey($urlKey);
//        $validKey = false;
//        while (!$validKey) {
//            $entityId = $this->checkUrlKey($urlKey, $object->getStoreId(), false);
//            if ($entityId == $object->getId() || empty($entityId)) {
//                $validKey = true;
//            }
//            else {
//                $parts = explode('-', $urlKey);
//                $last = $parts[count($parts) - 1];
//                if (!is_numeric($last)){
//                    $urlKey = $urlKey.'-1';
//                }
//                else {
//                    $suffix = '-'.($last + 1);
//                    unset($parts[count($parts) - 1]);
//                    $urlKey = implode('-', $parts).$suffix;
//                }
//            }
//        }
//        $object->setData('url_key', $urlKey);
//        return parent::_beforeSave($object);
//    }
}
