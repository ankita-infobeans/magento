<?php
class ICC_Volumelicense_Block_Adminhtml_Volumelicense_Renderer_Reassign extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
   public function render(Varien_Object $row)   
   {
       $value = $row->getData($this->getColumn()->getIndex());
       if($value){
            $collection = Mage::getModel("volumelicense/reports")->load($value);
            if($collection){
                return $collection->getEmail();
            }else{
                return "None";
            }
       }


   }
}

