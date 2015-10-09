<?php
/**
 * Free Resource model
 *
 * @category    ICC
 * @package     ICC_Freeresources
 */
class ICC_Freeresources_Model_Freeresource
    extends Mage_Core_Model_Abstract {
    /**
     * Entity code.
     * Can be used as part of method name for entity processing
     */
    const ENTITY    = 'icc_freeresources_freeresource';
    const CACHE_TAG = 'icc_freeresources_freeresource';
    /**
     * Prefix of model events names
     * @var string
     */
    protected $_eventPrefix = 'icc_freeresources_freeresource';

    /**
     * Parameter name in event
     * @var string
     */
    protected $_eventObject = 'freeresource';
    /**
     * constructor
     * @access public
     * @return void
     */
    public function _construct(){
        parent::_construct();
        $this->_init('icc_freeresources/freeresource');
    }
    /**
     * before save free resource
     * @access protected
     * @return ICC_Freeresources_Model_Freeresource
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
     * save freeresource relation
     * @access public
     * @return ICC_Freeresources_Model_Freeresource
     */
    protected function _afterSave() {
        return parent::_afterSave();
    }
}
