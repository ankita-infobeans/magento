<?php

class ICC_TEC_Model_Observer 
{
    public function addToRoster(Varien_Event_Observer $observer)
    {   
       
        $attribute_collection = Mage::getModel('eav/entity_attribute_set')
                ->getCollection()
                ->addFieldToFilter('attribute_set_name', array('exam','event'));
        $attribute_set_ids = array();
        foreach($attribute_collection as $attr ) {
            $attribute_set_ids[] = $attr->getId();
        }
        
        $invoice = $observer['invoice'];
        $order = $invoice->getOrder();
        
        $items = $order->getAllItems();
        foreach($items as $item) {
            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            if(in_array($product->getAttributeSetId(), $attribute_set_ids)) {
                $this->addRosterNames($item, $order);
            }
        }
        
    }
    
    private function addRosterNames($item, $order)
    {   // sales_order_item_cancel
        $item_prod_options = $item->getProductOptions();
        if( ! isset($item_prod_options['options'])) {
            return;
        }

        // Get option_id for this item's Special Interest and Email
        $names = $this->getItemNames($item_prod_options['options']);
        $email = $this->getItemEmail($item_prod_options['options']);
        $needs = $this->getItemNeeds($item_prod_options['options']);
        $jobTitle = $this->getItemJobTitle($item_prod_options['options']);
       
        foreach($names as $name)
        {
            $roster = Mage::getModel('icc_tec/roster');
            $roster->setFullname($name);
            $roster->setEmail($email);
            $roster->setOrderStatus($order->getStatus());
            $roster->setSpecialInterest($needs);
            $roster->setJobTitle($jobTitle);
            $roster->setOrderItemId($item->getId());
            $roster->setOrderId($order->getId());
            $roster->setProductId($item->getProductId());
            $roster->setPaymentType($order->getPayment()->getMethod());
            $roster->setPaymentAmount((float)$item->getRowTotal());
            $roster->setCreatedAt(time());
            
            $this->setCustomOptions($roster, $item_prod_options);
            
            $roster->save();
        }
    }

    protected function getOrderEmailAndSpecialNeeds($item_prod_options)
    {
        $buy_options = $item_prod_options['info_buyRequest']['options'];
        $product_id = $item_prod_options['info_buyRequest']['product'];
        $email_option_collection = Mage::getModel('catalog/product_option')->getCollection()
                        ->addFieldToFilter('product_id', $product_id)
                        ->addFieldToFilter('sku', 'email');
        $email_option_id = $email_option_collection->getSize()
            ? $email_option_collection->getFirstItem()->getOptionId()
            : '';

        $needs_option_collection = Mage::getModel('catalog/product_option')->getCollection()
                        ->addFieldToFilter('product_id', $product_id)
                        ->addFieldToFilter('sku', 'needs');
        $needs_option_id = $needs_option_collection->getSize()
            ? $needs_option_collection->getFirstItem()->getOptionId()
            : '';

        $to_return = array();
        $to_return['email'] = $email_option_id 
                                ? $buy_options[$email_option_id]
                                : '';
        $to_return['needs'] = $needs_option_id
                                ? $buy_options[$needs_option_id]
                                : '';

        return $to_return;
    }

    protected function setCustomOptions(&$roster, $item_prod_options)
    {
        $options_array = $item_prod_options["options"];
        foreach ( $options_array as $option_array )
        {
            // Check if this option is specific to event rosters
            //$roster = Mage::getModel('icc_tec/roster');
//            if ($option = ICC_TEC_Model_Roster::get_custom_option_label($option_array['label']))
            if ($option = $roster->get_custom_option_label($option_array['label']))
            {
                $mutator = "set".$option;
                $roster->$mutator($option_array['print_value']);
            }
        }
    }
    
    protected function getItemNames($options)
    {
        foreach($options as $option)
        {
            if( strtolower($option['label']) == 'registrants' )
            {
                $br_vals = nl2br( trim( $option['value'] ));
                $names = explode('<br />', $br_vals);
            }
        }
        return $names;
    }
    
    protected function getItemEmail($options)
    {
        foreach($options as $option) {
            if( strtolower($option['label']) == 'email' ) {
                return trim( $option['value'] );
            }
        }
    }
    
    protected function getItemNeeds($options)
    {
        foreach($options as $option) {
            if( strtolower($option['label']) == 'special needs' ) {
                return trim( $option['value'] );
            }
        }
    }
    
    protected function getItemJobTitle($options)
    {
        foreach($options as $option) {
            if( strtolower($option['label']) == 'job title' ) {
                return trim( $option['value'] );
            }
        }
    }
}