<?php

class ICC_ResetDownloads_Block_Adminhtml_Customer_Edit_renderer extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{	

    public function render(Varien_Object $row)
    {
        $numberOfDownloadBought =  $row->getData($this->getColumn()->getIndex());
        if($numberOfDownloadBought==0)
            return "Unlimited";
        return $numberOfDownloadBought;
    }
}