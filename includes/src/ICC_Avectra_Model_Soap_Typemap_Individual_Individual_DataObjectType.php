<?php

class ICC_Avectra_Model_Soap_Typemap_Individual_Individual_DataObjectType implements ICC_Avectra_Model_Soap_Typemap_Interface
{

    protected static $_fields = array(
        'ind_cst_key',
        'ind_first_name',
        'ind_last_name',
        'ind_industry_ext',
        'ind_trade_ext',
        'ind_specialty_ext'
    );

    public static function toXML($object)
    {

        $ret = "<ns1:Individual>";

        foreach(self::$_fields as $field) {
            if(isset($object->$field)) {
                $ret .= "<ns1:$field>{$object->$field}</ns1:$field>";
            }
        }

        $ret .=  "</ns1:Individual>";

        return $ret;
    }
}