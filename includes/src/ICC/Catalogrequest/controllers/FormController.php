<?php

//trial 4
class ICC_Catalogrequest_FormController extends Mage_Core_Controller_Front_Action
{

	const EMAIL_TEMPLATE_XML_PATH = 'customer/testemail/email_template';
	function submitAction()
	{


		$data = $this->getRequest()->getParams();
		$formatteddata = $this->processData($data);

		//print_r($data);
		//die;

		if($formatteddata)
		$this->sendEmail($formatteddata);



		return $this;

	}

	private function processData($data)
	{
		$var = array();
		$errors = array();
		if(empty($data['firstname']))
		{
			$errors[] = "Please enter First Name";

		}
		if(empty($data['lastname']))
		{
			$errors[] = "Please enter Last Name";

		}
		if(empty($data['street'][0]))
		{
			$errors[] = "Please enter Address";

		}
		if(empty($data['postcode']))
		{
			$errors[] = "Please enter Postal Code";

		}
		if(empty($data['country']))
		{
			$errors[] = "Please select country";

		}



		if(empty($data['product_catalog']) && empty($data['future_mailings']))
		{
			$errors[] = "Please select one of the receive options";
		}


		$var['firstname']       = $data['firstname'];
		$var['lastname']        = $data['lastname'];
		$var['title']           = $data['title'];
		$var['company']         = $data['company'];
		$var['cust_email']           = $data['cust_email'];
		$var['street1']         = $data['street'][0];
		$var['street2']         = $data['street'][1];
		if(empty($data['region_id']))
		$var['region']          = $data['region'];
		else{
			$var['region'] = Mage::getModel('directory/region')->load($data['region_id'])->getName();


		}
		$var['city']            = $data['city'];
		$var['postcode']        = $data['postcode'];
		$var['country']         = $data['country'];

		if($data['product_catalog'] == 1)
		$var['product_catalog']         = "YES";
		else
		$var['product_catalog']         = "NO";


		if($data['future_mailings'] == 1)
		$var['future_mailings']         ="YES";
		else
		$var['future_mailings']         ="NO";
	
		if(count($errors)>0)
		{
			$this->fail($errors);
			return false;
		}


		return $var;


	}
	private function success()
	{
		Mage::getSingleton('customer/session')->addSuccess("Your request has been successfully submitted and will be processed by ICC. Please allow up to 15 business days for delivery of your Catalog(s).");
		$this->_redirectSuccess(Mage::getUrl('customer-service/request-catalog')."?___store");
		return;

	}

	private function fail($errors)
	{
		foreach($errors as $error)
		{
			Mage::getSingleton('customer/session')->addError("Error processing request : ".$error);
		}

		$this->_redirectError(Mage::getUrl('customer-service/request-catalog')."?___store");
		return;


	}
	private function sendEmail($formatteddata)
	{

		//$emailTemplate  = Mage::getModel('core/email_template')->loadDefault('catalogrequest_form_email_template');
		$translate = Mage::getSingleton('core/translate');
		/* @var $translate Mage_Core_Model_Translate */
		$translate->setTranslateInline(false);


		$sendToEmail = Mage::getStoreConfig('catalog/printed_catalog/email_printed_catalog');





		$sender = array('name' => "ICCSafe Website",
				'email' => "catalog@iccsafe.org");

			

		$mailTemplate = Mage::getModel('core/email_template');

		$mailTemplate->setTemplateSubject("Request for Printed Catalog");
			
			
		$mailTemplate->sendTransactional(
				'catalogrequest_form_email_template'
				, $sender
				, $sendToEmail
				, ''
				, $formatteddata
				);



				$translate->setTranslateInline(true);

				//	echo "<pre>";
				//		print_r($mailTemplate);
				//		die;

				//$emailTemplate->send($sendToEmail,'Print Request', $formatteddata);

				$this->success();

				return;

	}



}