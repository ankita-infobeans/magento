<?php
class ICC_Premiumaccess_Block_Adminhtml_Premiumaccess_Renderer_Linkinfo extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    public function render(Varien_Object $row) {
        $value = $row->getData($this->getColumn()->getIndex());
        if (strpos(Mage::helper('core/url')->getCurrentUrl(), 'exportCsv') !== false) {
            return $value;
        } else {
            return nl2br($value);
        }
    }

}