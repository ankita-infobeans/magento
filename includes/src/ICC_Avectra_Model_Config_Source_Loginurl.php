<?php
class ICC_Avectra_Model_Config_Source_Loginurl
{
    private $__liveUrl = 'https://av.iccsafe.org/eweb/startpage.aspx?site=icc-cart&URL_success={URL}%3find_token%3d%7btoken%7d';
    private $__testUrl = 'https://av.iccsafe.org/nficctest/eweb/startpage.aspx?site=icc-cart&URL_success={URL}%3find_token%3d%7btoken%7d';
    
     /**
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => $this->__liveUrl,
                'label' => 'Live Url',
            ),
            array(
                'value' => $this->__testUrl,
                'label' => 'Test Url',
            ),
        );
    }
}
