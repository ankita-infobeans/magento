<?php
class ICC_Avectra_Model_Config_Source_Url
{
    private $__liveUrl = 'https://av.iccsafe.org/xweb/Secure/netFORUMXML.asmx?WSDL';
    private $__testUrl = 'https://av.iccsafe.org/nficctest/xweb/secure/netforumxml.asmx?WSDL';
    
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
