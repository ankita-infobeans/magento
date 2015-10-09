<?php
class ICC_Premiumaccess_Block_Adminhtml_Premiumaccess_Renderer_RemoveDate extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
   public function render(Varien_Object $row)   
   {
       $value = $row->getData($this->getColumn()->getIndex());
       if($value != '0000-00-00 00:00:00'){
            //$locale = Mage::app()->getLocale();
            //$date = $locale->date( $value, $locale->getDateFormat(), $locale->getLocaleCode(), false )->toString( $locale->getDateTimeFormat() ) ;
            $date  = date('M d, Y h:i:s A');
            return $date;
       }
   }
}
