<?php


//$wsdl = "https://wsvar.paymentech.net/PaymentechGateway/wsdl/PaymentechGateway.wsdl";

//$wsdl = "http://iccdev.t3infosystems.com:8080/ICC.GP.MagentoIntegrationService/MagentoIntegration.svc?wsdl";
$wsdl = "http://icc.t3infosystems.com:8080/ICC.GP.MagentoIntegrationService/MagentoIntegration.svc?wsdl";


$client = new SoapClient($wsdl);
//$client = new SoapClient(null, array(
//    'location'=>$url,
//    'uri'=>''
//));

/*
  $url = "https://orbitalvar1.paymentech.net/authorize/";
  $client = new SoapClient(null, array(
  'soap_version'=>'SOAP_1_2',
  'Content-type'=> 'application/PTI54',
  'version'=>'2',
  'location' => $url,
  'uri'=>'Authorize',
  'trace' => 1));
 */



$ret = "";
try {
$ret = $client->__getFunctions();
echo "<pre>";
print_r($ret);
echo "</pre>";

//$ret = $client->__call("NewOrder", array( $soapstuff));
$foo = new GetProductBySKU();
$foo->sku = '100XLG';
//$ret = $client->GetProductBySKU($foo);	 //100XLG


$ret = new getOfflineOrderSummary();
$ret->CustomerId = "0111693";

$ret = $client->GetOfflineOrderSummary($ret);
//$ret= $client->newOrder($soapstuff);
//    $ret = $client->ProfileAdd($po);
echo "<pre>";
    print_r($ret);
echo "</pre>";

} catch (SoapFault $sf) {
//Log SOAP fault from connection
    echo "__getLastRequest";
    echo "<pre>";
    echo print_r($client->__getLastRequest, true);
    echo "</pre>";
    echo "__getLastRequestHeaders";
    echo "<pre>";
    echo print_r($client->__getLastRequestHeaders(), true);
    echo "</pre>";
    echo "__getLastResponse";
    echo "<pre>";
    echo print_r($client->__getLastResponse(), true);
    echo "</pre>";
    echo("-----soapfault-------------<br>\n");

    echo "<pre>";
    echo print_r($sf, true);
    echo "</pre>";
}

class getOfflineOrderSummary
{
	public $CustomerNumber;
	
}


class GetProductBySKU{
	public $sku;

}

