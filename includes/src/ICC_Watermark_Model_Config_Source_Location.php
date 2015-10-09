<?php
class ICC_Watermark_Model_Config_Source_Location
{
    protected $_locations = array(
//        Gorilla_StampPDF_Helper_Data::LOCATION_BOTTOM_CENTER,
//        Gorilla_StampPDF_Helper_Data::LOCATION_BOTTOM_LEFT,
//        Gorilla_StampPDF_Helper_Data::LOCATION_BOTTOM_RIGHT,
//        Gorilla_StampPDF_Helper_Data::LOCATION_MIDDLE_CENTER,
//        Gorilla_StampPDF_Helper_Data::LOCATION_MIDDLE_LEFT,
//        Gorilla_StampPDF_Helper_Data::LOCATION_MIDDLE_RIGHT,
//        Gorilla_StampPDF_Helper_Data::LOCATION_TOP_CENTER,
//        Gorilla_StampPDF_Helper_Data::LOCATION_TOP_LEFT,
//        Gorilla_StampPDF_Helper_Data::LOCATION_TOP_RIGHT
    );

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = array();
        foreach($this->_locations as $loc){
            $options[] = array('value' => $loc, 'label' => uc_words(str_replace('_', ' ',$loc)));
        }
        return $options;
    }
}