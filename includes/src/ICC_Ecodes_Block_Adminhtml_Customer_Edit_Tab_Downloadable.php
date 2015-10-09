<?php

class ICC_Ecodes_Block_Adminhtml_Customer_Edit_Tab_Downloadable
    extends Enterprise_Checkout_Block_Adminhtml_Manage_Accordion
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    /**
     * ######################## TAB settings #################################
     */
    /**
     * Return Tab label
     *
     * @return string
     */
    public function getTabLabel()
    {
        return Mage::helper('ecodes')->__('eCode Serials');
    }

    /**
     * Return Tab title
     *
     * @return string
     */
    public function getTabTitle()
    {
        return Mage::helper('ecodes')->__('eCode Serials');
    }

    /**
     * Check if can show tab
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
}