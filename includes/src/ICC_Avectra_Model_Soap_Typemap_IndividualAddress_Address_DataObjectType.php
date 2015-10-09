<?php

class ICC_Avectra_Model_Soap_Typemap_IndividualAddress_Address_DataObjectType implements ICC_Avectra_Model_Soap_Typemap_Interface
{

    public static function toXML($object)
    {
        $ret = "<ns1:Address>
           <ns1:adr_line1>{$object->adr_line1}</ns1:adr_line1>";

        if(isset($object->adr_line2)) {
            $ret .= "<ns1:adr_line2>{$object->adr_line2}</ns1:adr_line2>";
        }

        if(isset($object->adr_state)) {
            $ret .= "<ns1:adr_state>{$object->adr_state}</ns1:adr_state>";
        }

        $ret .= "<ns1:adr_city>{$object->adr_city}</ns1:adr_city>
                 <ns1:adr_post_code>{$object->adr_post_code}</ns1:adr_post_code>
                 <ns1:adr_country>{$object->adr_country}</ns1:adr_country>
                 </ns1:Address>";

        return $ret;
    }
}