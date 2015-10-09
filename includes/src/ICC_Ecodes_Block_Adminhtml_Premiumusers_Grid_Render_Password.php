<?php
class ICC_Ecodes_Block_Adminhtml_Premiumusers_Grid_Render_Password extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract{
 
    public function render(Varien_Object $row)
    {
        $premiumuser = Mage::getModel('ecodes/premiumusers')->load($row->getId(), 'id');

        if($premiumuser->getPass() != null) {
            $helper = Mage::helper('ecodes');
            return $helper->decryptPassword($premiumuser->getPass());
        }
    }
}