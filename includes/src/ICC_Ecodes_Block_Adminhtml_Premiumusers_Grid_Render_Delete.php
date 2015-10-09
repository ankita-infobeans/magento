<?php
class ICC_Ecodes_Block_Adminhtml_Premiumusers_Grid_Render_Delete extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        if (!$row->getData()) {
            return null;
        }
        return '<a title="'.Mage::helper('ecodes')->__('Delete').'" href="'.$this->getUrl('*/*/delete', array('id'=>$row->getId())).'">'.Mage::helper('ecodes')->__('Delete'). '</a>';
    }
}
