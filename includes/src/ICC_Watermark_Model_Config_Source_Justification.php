<?php
class ICC_Watermark_Model_Config_Source_Justification
{
    protected $_justifications = array(
        Gorilla_StampPDF_Helper_Data::JUSTIFICATION_RIGHT,
        Gorilla_StampPDF_Helper_Data::JUSTIFICATION_CENTER,
        Gorilla_StampPDF_Helper_Data::JUSTIFICATION_LEFT
    );

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = array();
        foreach($this->_justifications as $val){
            $options[] = array('value' => $val, 'label' => uc_words(str_replace('_', ' ',$val)));
        }
        return $options;
    }
}