<?php
class ICC_Watermark_Model_Config_Source_Font
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $fonts = array(
            'Courier',
            'Courier-Bold',
            'Courier-Oblique',
            'Courier-BoldOblique',
            'Zapf Dingbats',
            'Helvetica-Bold',
            'Helvetica',
            'Helvetica-BoldOblique',
            'Symbol',
            'Times Roman',
            'Times Bold',
            'Times Italic',
            'Times Bold Italic'
        );

        $options = array();
        foreach($fonts as $font){
            $options[] = array('value' => $font,'label' => $font);
        }
        return $options;
    }
}