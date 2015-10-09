<?php
class ICC_Ecodes_Block_Adminhtml_Premiumusers_Edit_Tabs
    extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('premiumusers_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('ecodes')->__('Manage PremiumACCESS Users'));
        
    }

    protected function _beforeToHtml()
    {
        $this->addTab('users_section',array(
            'label'     => Mage::helper('ecodes')->__('User'),
            'title'     => Mage::helper('ecodes')->__('User'),
            'content'   => $this->getLayout()->createBlock('ecodes/adminhtml_premiumusers_edit_tab_users')->toHtml()
        ));
        return parent::_beforeToHtml();
    }
}
