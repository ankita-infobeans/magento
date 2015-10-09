<?php
class ICC_Volumelicense_Block_Adminhtml_Volumelicense_Renderer_RemoveDate extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
   public function render(Varien_Object $row)   
   {
       $value = $row->getData($this->getColumn()->getIndex());
       if($value != '0000-00-00 00:00:00'){
            $locale = Mage::app()->getLocale();
            $date = $locale->date( $value, $locale->getDateFormat(), $locale->getLocaleCode(), false )->toString( $locale->getDateTimeFormat() ) ;
            return $date;
       }
   }
}
