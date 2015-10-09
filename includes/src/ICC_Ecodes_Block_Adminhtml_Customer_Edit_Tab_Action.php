<?php

class ICC_Ecodes_Block_Adminhtml_Customer_Edit_Tab_Action
 extends Mage_Adminhtml_Block_Template
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
 
    public function __construct()
    {
        $this->setTemplate('ecodes/action.phtml');
 
    }
 
    public function getCustomtabInfo()
    {
 
        $customer = Mage::registry('current_customer');
        $customtab='My Custom tab Action Contents Here';
        return $customtab;
    }
 
    /**
     * Return Tab label
     *
     * @return string
     */
    public function getTabLabel()
    {
        return $this->__('PremiumACCESS Subscriptions');
    }
 
    /**
     * Return Tab title
     *
     * @return string
     */
    public function getTabTitle()
    {
        return $this->__('Action Tab');
    }
 
    /**
     * Can show tab in tabs
     *
     * @return boolean
     */
    public function canShowTab()
    {
        $customer = Mage::registry('current_customer');
        return (bool)$customer->getId();
    }
 
    /**
     * Tab is hidden
     *
     * @return boolean
     */
    public function isHidden()
    {
        return false;
    }
 
     /**
     * Defines after which tab, this tab should be rendered
     *
     * @return string
     */
    public function getAfter()
    {
        return 'tags';
    }
 
}