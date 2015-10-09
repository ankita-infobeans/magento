<?php

class ICC_Orderfiltering_Block_Adminhtml_Renderer_Producttype extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    public function render(Varien_Object $row) {
        
            //  $product_types = implode(", ",$row->getProductTypes());
        return '<span>' . $row->getItemTypeValues() . '</span>';    
    }

}

?>
