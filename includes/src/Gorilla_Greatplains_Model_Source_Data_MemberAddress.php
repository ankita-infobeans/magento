<?php

class Gorilla_Greatplains_Model_Source_Data_MemberAddress
{

	public $ContactName;
	public $ShipToCompany = " ";
	public $Line1;
	// public $Line2;
	// public $Line3;
	public $State;
	public $City;
	public $Zip;
	public $Phone1;
	public $Country;
	public $CountryCode;
	public $UPSZone;

	public function __construct($data) {
			
		//Mage::log($data->debug());
			
		$this->ContactName  = $this->getAddressFullName($data);
		//	if($data->getCompany() == null)
		//$this->ShipToCompany = " ";
		//	else
		$this->ShipToCompany = $data->getCompany();
		$this->setStreet($data->getStreet());
		$this->State        = $data->getRegionCode();
		$this->City         = $data->getCity();
		$this->Zip          = $data->getPostcode();
		$this->Phone1       = preg_replace("/\D/", "",$data->getTelephone());




		$countryModel = Mage::getModel('directory/country')->loadByCode($data->getCountryId());

		$countryName = $countryModel->getName();



		$this->Country      = $countryName;
		$this->CountryCode  = $data->getCountryId();
		$this->UPSZone      = $this->getUPSZone($data->getCountryId(), $data->getRegionCode());

		return $this;

	}

	protected function setStreet($street)
	{
		if (!is_array($street)) {
			$street =array($street);
		}
		$this->Line1 = $street[0];
		$this->Line2 = "";
		$this->Line3 = "";
		if (count ( $street ) > 1) {
			$this->Line2 = $street[1];
		}
	}

	public function getAddressFullName($orderAddress) {
		$prefix = $orderAddress->getPrefix();
		$first  = $orderAddress->getFirstname();
		$middle = $orderAddress->getMiddlename();
		$last   = $orderAddress->getLastname();
		$suffix = $orderAddress->getSuffix();

		$name = "";
		$name .= (empty($prefix)) ? "" : $prefix . " ";
		$name .= (empty($first)) ? "" : $first . " ";
		$name .= (empty($middle)) ? "" : $middle . " ";
		$name .= (empty($last)) ? "" : $last . " ";
		$name .= (empty($suffix)) ? "" : $suffix;

		return trim($name);
	}

	public function getUPSZone($country, $region) {

		$noncontinental = array('AS', 'AF', 'AC', 'AA', 'AE',
				'AM', 'AP', 'AK', 'FM', 'GU',
				'HI', 'MH', 'MP', 'PW', 'PR', 'VI');

		switch ($country) {
			case "US":
				if (in_array($region, $noncontinental)) {
					return "USO";
				}
				return "USC";
				break;
			case "CA":
				return "CAN";
				break;
			case "MX":
				return "MEX";
				break;
			default:
				return "OTH";
		}
	}

}

?>