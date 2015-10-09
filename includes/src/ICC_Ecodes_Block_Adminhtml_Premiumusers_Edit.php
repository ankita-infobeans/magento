<?php
class ICC_Ecodes_Block_Adminhtml_Premiumusers_Edit
    extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'ecodes';
        $this->_controller = 'adminhtml_premiumusers';
        $this->setDestElement('edit_form');

        parent::__construct();
    }

    /**
     * Retrieve text for header element depending on loaded page
     *
     * @return string
     */
    public function getHeaderText()
    {
        
            return "Edit User Record";
        

    }
}



