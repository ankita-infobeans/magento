<?php
class ICC_Watermark_Model_Config_Source_Position
{
    protected $_positions = array(
        Gorilla_StampPDF_Helper_Data::POSITION_TOP,
        Gorilla_StampPDF_Helper_Data::POSITION_MIDDLE,
        Gorilla_StampPDF_Helper_Data::POSITION_BOTTOM,
        Gorilla_StampPDF_Helper_Data::POSITION_ANGLE,
        Gorilla_StampPDF_Helper_Data::POSITION_DIAG_TOPLEFT,
        Gorilla_StampPDF_Helper_Data::POSITION_DIAG_BOTTOMLEFT
    );

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = array();
        foreach($this->_positions as $val){
            $options[] = array('value' => $val, 'label' => uc_words(str_replace('_', ' ',$val)));
        }
        return $options;
    }
}