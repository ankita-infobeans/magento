<?php

class ICC_Ecodes_Block_Customer_Eproducts_Cdroms extends Mage_Core_Block_Template 
{
    const CD_ROM_ATTRIBUTE_SET_ID = 17;
    
    public function getEcodeCdSerials()
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $json_encoded_ecode_cds = $customer->getEcodesCdSerials();
        if(empty($json_encoded_ecode_cds))
        {
            $ecodes_cds = array();
        }
        else
        {
            $ecodes_cds = Zend_Json_Decoder::decode($json_encoded_ecode_cds);
        }
        return $ecodes_cds;
    }
    
}