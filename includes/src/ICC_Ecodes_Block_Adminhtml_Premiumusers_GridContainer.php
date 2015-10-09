<?php
class ICC_Ecodes_Block_Adminhtml_Premiumusers_GridContainer
    extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {
        parent::__construct();
        $this->_headerText = Mage::helper('ecodes')->__('Manage PremiumACCESS Users');
        $this->_blockGroup = 'ecodes';
        $this->_controller = 'adminhtml_premiumusers';
        $this->setHeaderHtml();

    }
}
