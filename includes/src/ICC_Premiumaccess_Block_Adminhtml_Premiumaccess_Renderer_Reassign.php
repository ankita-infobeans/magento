<?php
class ICC_Premiumaccess_Block_Adminhtml_Premiumaccess_Renderer_Reassign extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
   public function render(Varien_Object $row)   
   {
       $value = $row->getData($this->getColumn()->getIndex());
       if($value){
            $collection = Mage::getModel("icc_premiumaccess/reports")->load($value);
            if($collection){
                return $collection->getEmail();
            }else{
                return "None";
            }
       }


   }
}

