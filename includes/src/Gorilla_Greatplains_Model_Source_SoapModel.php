<?php

abstract class Gorilla_Greatplains_Model_Source_SoapModel {
	
	abstract protected function Process($data);
	
	abstract protected function getErrors();
	abstract protected function getData();
}

?>