<?php
/**
 * Free Resource category model
 *
 * @category    ICC
 * @package     ICC_Freeresources
 */
class ICC_Freeresources_Model_Freeresource_Category
    extends Mage_Core_Model_Abstract {
    const STATUS_PENDING  = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;
    /**
     * Entity code.
     * Can be used as part of method name for entity processing
     */
    const ENTITY    = 'freeresource_category';
    const CACHE_TAG = 'freeresource_category';
    /**
     * Prefix of model events names
     * @var string
     */
    protected $_eventPrefix = 'freeresource_category';

    /**
     * Parameter name in event
     * @var string
     */
    protected $_eventObject = 'category';
    /**
     * constructor
     * @access public
     * @return void

     */
    public function _construct(){
        parent::_construct();
        $this->_init('icc_freeresources/freeresource_category');
    }
    /**
     * before save free resource category
     * @access protected
     * @return ICC_Freeresources_Model_Freeresource_Category

     */
    protected function _beforeSave(){
        parent::_beforeSave();
        $now = Mage::getSingleton('core/date')->gmtDate();
        if ($this->isObjectNew()){
            $this->setCreatedAt($now);
        }
        $this->setUpdatedAt($now);
        return $this;
    }
    /**
     * validate category
     * @access public
     * @return array|bool

     */
    public function validate() {
        $errors = array();

        if (!Zend_Validate::is($this->getTitle(), 'NotEmpty')) {
            $errors[] = Mage::helper('review')->__('Category title can\'t be empty');
        }

        if (!Zend_Validate::is($this->getName(), 'NotEmpty')) {
            $errors[] = Mage::helper('review')->__('Your name can\'t be empty');
        }

        if (!Zend_Validate::is($this->getCategory(), 'NotEmpty')) {
            $errors[] = Mage::helper('review')->__('Category can\'t be empty');
        }

        if (empty($errors)) {
            return true;
        }
        return $errors;
    }
    
     public function toOptionArray() {
        $collections = Mage::getModel('icc_freeresources/freeresource')->getCollection();
        $values = array ();
        foreach ($collections as $key => $collection) {
            $values[$key]['label'] = $collection->getTitle();
            $values[$key]['value']  = $collection->getEntityId();
        }
        return $values;
    }
    /**
     * Get list of all available values
     * @access public
     * @return array

     */
    public function getAllOptions() {
        return $this->toOptionArray();
    }
}
