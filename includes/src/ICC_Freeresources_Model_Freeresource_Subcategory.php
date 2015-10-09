<?php
/**
 * Free Resource sub category model
 *
 * @category    ICC
 * @package     ICC_Freeresources
  */
class ICC_Freeresources_Model_Freeresource_Subcategory
    extends Mage_Core_Model_Abstract {
    const STATUS_PENDING  = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;
    /**
     * Entity code.
     * Can be used as part of method name for entity processing
     */
    const ENTITY    = 'freeresource_subcategory';
    const CACHE_TAG = 'freeresource_subcategory';
    /**
     * Prefix of model events names
     * @var string
     */
    protected $_eventPrefix = 'freeresource_subcategory';

    /**
     * Parameter name in event
     * @var string
     */
    protected $_eventObject = 'subcategory';
    /**
     * constructor
     * @access public
     * @return void
     */
    public function _construct(){
        parent::_construct();
        $this->_init('icc_freeresources/freeresource_subcategory');
    }
    /**
     * before save free resource subcategory
     * @access protected
     * @return ICC_Freeresources_Model_Freeresource_Subcategory
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
     * validate subcategory
     * @access public
     * @return array|bool
     */
    public function validate() {
        $errors = array();

        if (!Zend_Validate::is($this->getTitle(), 'NotEmpty')) {
            $errors[] = Mage::helper('review')->__('Subcategory title can\'t be empty');
        }

        if (!Zend_Validate::is($this->getName(), 'NotEmpty')) {
            $errors[] = Mage::helper('review')->__('Your name can\'t be empty');
        }

        if (!Zend_Validate::is($this->getSubcategory(), 'NotEmpty')) {
            $errors[] = Mage::helper('review')->__('Subcategory can\'t be empty');
        }

        if (empty($errors)) {
            return true;
        }
        return $errors;
    }
    
     public function toOptionArray() {
        $collections = Mage::getModel('icc_freeresources/freeresource_category')->getCollection();
        $values = array ();
        foreach ($collections as $key => $collection) {
            $values[$key]['label'] = $collection->getTitle();
            $values[$key]['value']  = $collection->getCategoryId();
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