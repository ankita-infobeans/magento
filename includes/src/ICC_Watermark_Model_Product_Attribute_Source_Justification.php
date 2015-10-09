<?php

class ICC_Watermark_Model_Product_Attribute_Source_Justification extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{

    public function getAllOptions()
    {
        if(!$this->_options) {
            $this->_options = array(
                array(
                    'value' => '',
                    'label' => ''
                ),
                array(
                    'value' => 'Left',
                    'label' => 'Left'
                ),
                array(
                    'value' => 'Center',
                    'label' => 'Center'
                ),
                array(
                    'value' => 'Right',
                    'label' => 'Right'
                )
            );
        }
        return $this->_options;
    }

}