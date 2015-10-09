<?php

class ICC_Demographics_Block_Form extends Mage_Core_Block_Template {

	public function getSaveUrl() {
		return $this->getUrl( 'customer/account/saveDemographics' );
	}
        
        public function showForm() 
        {   
            $session = Mage::getSingleton('customer/session');
            $customer = $session->getCustomer();
            if(!$customer || !$session->isLoggedIn())
            {
                return false; // need a customer to avoid no object errors
            }
            if($customer->getHasUpdatedDemo()) //has_updated_demo 
            {
                // they have already saved off the form
                return false; // do not show
            }
            return true; // they have not yet saved it
        }
        
        /**
         * Method for retriving demographics saved industry value from avectra server
         * @return Saved Industry Value
         */
        public function getSavedIndustryExt() 
        {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $avectraKey = $customer->getAvectraKey();
            $avectraAccount = Mage::getModel('icc_avectra/account');
            $userData = $avectraAccount->getUserInfo($avectraKey);
            return $this->getIndustry(strtoupper($userData->Individual->ind_industry_ext));                        
        }
        
         /**
         * Industry Options Mapper Method
         * @param industry option key
         * @return Saved Industry Value
         */
        public function getIndustry( $industryExtValue ) {          
            if ( $industryExtValue ) {
                    $industryExtMapper = array(
                                "Agriculture, Forestry or Fishing" => "CF126727-6CF7-4B18-AA1E-4977BA59D905",
                                "Mining" => "A451452A-AEAF-44C2-87EB-7D000CCECE41",
                                "Construction" => "FDF26934-9EB9-4E7B-9DA1-DF9F1F3156BD",
                                "Manufacturing" => "8703AE56-F6B4-49C5-8F5D-5A023D4457FA",
                               "Transportation, Communications, Electric, Gas and Sanitary Services" => "6033CD68-EF83-4650-8E3E-29E17589C89C",
                                "Wholesale Trade" => "EA776800-8D7F-477F-8364-FB9ED8D23727",
                                "Retail Trade" => "22700E3E-3561-4133-A18B-5A8993A4CB94",
                                "Finance, Insurance or Real Estate" => "4F4BAA88-71E0-40B8-9A38-DC56F3E93D32",
                                "Services" => "BDF8AE2F-D7B6-4221-8BEE-7E6EDAD17030",
                                "Public Administration" => "6C7DDE5E-AFCF-4014-BB9B-357B0BD52B61",                        
                            );
                    return array_search($industryExtValue, $industryExtMapper);             
            } else {
                    return 'Industry Data Not Saved..';
            }
        }
        
        /**
         * Method for retriving demographics saved trade value from avectra server
         * @return Saved Trade Value
         */
        public function getSavedTrade() 
        {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $avectraKey = $customer->getAvectraKey();
            $avectraAccount = Mage::getModel('icc_avectra/account');
            $userData = $avectraAccount->getUserInfo($avectraKey);
            return $this->getTrade(strtoupper($userData->Individual->ind_trade_ext));            
        }
        
         /**
         * Trade Options Mapper Method
         * @param industry option key
         * @return Saved Trade Value
         */
        public function getTrade( $tradeValue ) {  
            if ( $tradeValue ) {
                    $tradeMapper = array(
                                "Architect" => "A44165C6-2DA9-4F05-9971-843C05675A6B",
                                "Designer" => "1872E9EA-FA59-4809-AD8D-6E9D0474EC89",
                                "Construction Manager" => "4D972F3F-5647-43EE-8502-F6DA2E77BFFD",
                                "Contractor" => "53ED2040-9251-4B46-8DF6-66F0819BFD4E",
                                "Building Official" => "A3053B5F-586D-4518-8EE4-82A732214203",
                                "Fire Official" => "7CAE9B5B-0FE7-449D-A5E4-276DA2C3F459",
                                "Chief Code Official" => "66A3F78C-B004-4E6B-98F6-00A1AA347738",
                                "Inspector" => "8BF54A1E-0B85-4837-9659-AA42B181637D",
                                "Plans Examiner" => "F7F2633A-266C-4281-A485-8BFF8A7BB4DF",
                                "Permit Tech" => "C6E6F669-5DAD-4906-9D2A-F988C8B226F2",                        
                                "Tradesman" => "1A1128EA-86F2-4746-83A8-1EE1F8A3C299",  
                                "Journeyman" => "EB891909-A875-40D0-BDCE-75880939030C",  
                                "Consultant" => "AF26A86F-42EF-4496-84EF-53CD32C44409",  
                            );
                    return array_search($tradeValue, $tradeMapper);             
            } else {
                    return 'Trade Data Not Saved..';
            }
        } 
        
        /**
         * Method for retriving demographics saved technical Speciality value from avectra server
         * @return Saved Technical Speciality Value
         */
        public function getSavedTechnicalSpecialty() 
        {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $avectraKey = $customer->getAvectraKey();
            $avectraAccount = Mage::getModel('icc_avectra/account');
            $userData = $avectraAccount->getUserInfo($avectraKey);
            return $this->getTechnicalSpecialty(strtoupper($userData->Individual->ind_specialty_ext));                        
        }
        
         /**
         * Technical Speciality Options Mapper Method
         * @param industry option key
         * @return Saved Technical Speciality Value
         */
        public function getTechnicalSpecialty( $technicalSpecialtyValue ) {    
            if ( $technicalSpecialtyValue ) {
                    $technicalSpecialtyMapper = array(
                                "Architect" => "A44165C6-2DA9-4F05-9971-843C05675A6B",
                                "Backflow" => "2557D45F-CF82-4390-9228-42D8205930B8",
                                "Carpentry" => "0B472E5A-D927-4744-81C5-855742417488",
                                "Civil" => "59E624BB-9235-40D3-A227-AC8D068BD1BB",
                                "Curtain Wall" => "A9FB9B6D-51A1-4144-BFE4-08439F7D36F8",
                                "Electrical" => "F7ED8EBE-C25D-4833-9811-1392FDE35958",
                                "Energy" => "51074658-8EC8-4D17-93BE-881C10B5C5B4",
                                "Fenestration" => "171A0B9D-04C0-45FB-AC99-B57D5C73F78F",
                                "Fire Prevention" => "E31AD8E2-6C7A-4B21-A89F-A0ABF751025D",
                                "Gas" => "0AAC78AC-9FB9-4DFD-AD7B-590326F45D67",                        
                                "HVAC" => "9A315B47-AEDC-4F8D-96AB-9E3AE2542ED3",  
                                "Masonry" => "482C7FD9-A682-44BC-B92F-CACFEE7DF874",  
                                "Mechanical" => "3243B924-38CD-47A6-933E-50090CB29DD2",  
                                "Plumbing" => "1C8809EA-8F55-4481-979B-5568A6AB3774",  
                                "Roofing" => "13A8252C-8D6F-4893-90A0-870EFF1E7534",  
                                "Solar" => "338837A8-22D9-4707-9D20-27876BE666E2",  
                                "Structural" => "C1B2E68B-069B-41D0-B96A-906EF59B0F40",  
                                "Sustainability" => "2F596FB9-3EF5-4498-B1DA-2A0BB535900B",  
                                "Swimming Pool &amp; Spa" => "E8680E73-8E34-4D5D-90E3-FFBC63DFC0C2",  
                                "Underground Tanks" => "0759E520-2C76-459F-A189-30E28F159146",  
                                "Wall Finish" => "CD1DA53A-3632-4793-BB7B-6F45C5DB388B",  
                            );
                    return array_search($technicalSpecialtyValue, $technicalSpecialtyMapper);  
            } else {
                    return 'Technical Speciality Data Not Saved..';
            }
        }         

}

