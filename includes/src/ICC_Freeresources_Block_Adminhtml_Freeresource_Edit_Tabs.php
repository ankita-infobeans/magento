<?php
/**
 * Free Resource admin edit tabs
 *
 * @category    ICC
 * @package     ICC_Freeresources
 */
class ICC_Freeresources_Block_Adminhtml_Freeresource_Edit_Tabs
    extends Mage_Adminhtml_Block_Widget_Tabs {
    /**
     * Initialize Tabs
     * @access public
     */
    public function __construct() {
        parent::__construct();
        $this->setId('freeresource_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('icc_freeresources')->__('Free Resource'));
    }
    /**
     * before render html
     * @access protected
     * @return ICC_Freeresources_Block_Adminhtml_Freeresource_Edit_Tabs
     */
    protected function _beforeToHtml(){
        $this->addTab('form_freeresource', array(
            'label'        => Mage::helper('icc_freeresources')->__('Free Resource Information'),
            'title'        => Mage::helper('icc_freeresources')->__('Free Resource Information'),
            'content'     => $this->getLayout()->createBlock('icc_freeresources/adminhtml_freeresource_edit_tab_form')->toHtml(),
        ));
        return parent::_beforeToHtml();
    }
    /**
     * Retrieve free resource entity
     * @access public
     * @return ICC_Freeresources_Model_Freeresource
     */
    public function getFreeresource(){
        return Mage::registry('current_freeresource');
    }
}