<?php
class ICC_Avectra_Model_Config_Source_Logouturl
{
    private $__liveUrl = 'https://av.iccsafe.org/eweb/Logout.aspx?&RedirectURL={URL}';
    private $__testUrl = 'https://av.iccsafe.org/nficctest/eweb/Logout.aspx?&RedirectURL={URL}';
    
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
