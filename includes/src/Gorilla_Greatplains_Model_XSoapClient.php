<?php 
class Gorilla_Greatplains_Model_XSoapClient extends SoapClient
{
	const XSI_NS = "http://www.w3.org/2001/XMLSchema-instance";
	const _NULL_ = "xxx_replacedduetobrokephpsoapclient_xxx";

	protected $mustParseNulls = false;



	public function __call($method, $params)
	{
		Mage::log("_call");
			
		//Mage::log($method);
			
		//Mage::log($params);
		//die;
		foreach($params as $k => $v)
		{
			Mage::log( "-------------------------afasdfagfagfr------------------------");
			Mage::Log("k is : $k");
			$data = $this->generateValidXmlFromObj($v);
			Mage::Log( $data);
			//	if(is_object($v))
			//		Mage::Log("v is object ".print_r($v,true));
			//		else
			//	Mage::Log("v is string ".$v);


			if($v === null)
			{
				Mage::Log("SPOTTED A NULL YO!!!");
				$this->mustParseNulls = true;
				$params[$k] = self::_NULL_;
			}
		}
		//die;
		return parent::__call($method, $data);
	}



   public static function generateValidXmlFromObj(stdClass $obj, $node_block='nodes', $node_name='node') {
        $arr = get_object_vars($obj);
        return self::generateValidXmlFromArray($arr, $node_block, $node_name);
    }

    public static function generateValidXmlFromArray($array, $node_block='nodes', $node_name='node') {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>';

        $xml .= '<' . $node_block . '>';
        $xml .= self::generateXmlFromArray($array, $node_name);
        $xml .= '</' . $node_block . '>';

        return $xml;
    }

    private static function generateXmlFromArray($array, $node_name) {
        $xml = '';

        if (is_array($array) || is_object($array)) {
            foreach ($array as $key=>$value) {
                if (is_numeric($key)) {
                    $key = $node_name;
                }

                $xml .= '<' . $key . '>' . self::generateXmlFromArray($value, $node_name) . '</' . $key . '>';
            }
        } else {
            $xml = htmlspecialchars($array, ENT_QUOTES);
        }

        return $xml;
    }
	



}
?>