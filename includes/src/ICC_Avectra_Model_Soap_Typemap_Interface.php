<?php

interface ICC_Avectra_Model_Soap_Typemap_Interface
{

    /**
     * Convert outbound object to XML for SOAP request
     * @param $object XML object such as SimpleXMLElement
     * @return string
     */
    public static function toXml($object);

    /**
     * @todo At this time we only need typemaps for toXml
     */
    //abstract public function fromXml($xml);

}