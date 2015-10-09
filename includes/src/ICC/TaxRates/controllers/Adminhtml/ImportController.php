<?php

require_once 'Mage'.DS.'Adminhtml'.DS.'controllers'.DS.'Tax'.DS.'RuleController.php';

class ICC_TaxRates_Adminhtml_ImportController
    extends Mage_Adminhtml_Tax_RuleController
{
    
    CONST tax_code_index    = 0;
    CONST country_index     = 1;
    CONST region_index      = 2;
    CONST zipcode_index     = 3;
    CONST rate_index        = 4;
    
    protected $prefixZipIncr    = array();
    protected $taxRuleData      = array();
    protected $regionTable      = array();
    protected $regionDirectory  = null;
    
    
    public function csvAction()
    {
        $this->_title($this->__('Sales'))
             ->_title($this->__('Tax'))
             ->_title($this->__('Import Tax Rules CSV'));

        $this->_initAction()
             ->_addContent($this->getLayout()->createBlock('icc_taxrates/import'))
             ->renderLayout();
    }

    public function importPostAction()
    {
        if ($this->getRequest()->isPost() && !empty($_FILES['import_rates_file']['tmp_name'])) {
            try {
                $this->_importRates();

                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('tax')->__('The tax rate has been imported.'));
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            } catch (Exception $e) {
                ///Mage::getSingleton('adminhtml/session')->addError(Mage::helper('tax')->__('Invalid file upload attempt'));
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('tax')->__('Invalid file upload attempt'));
        }
        $this->_redirect('*/*/csv');
    }
    
    protected function profileImport()
    {
        //$this->logImport("memory_get_usage(): " . (memory_get_usage() / 1048576) . " MB" );
        $this->logImport("memory: " . round( (memory_get_usage() / 1048576), 3) . " MB.\tpeak: "  . round( (memory_get_peak_usage() / 1048576), 3) . " MB" );
    }
    
    protected function logImport($message)
    {
        Mage::log($message, null, 'tax_import.log');
    }
    
    protected function _importRates()
    {
        ini_set('memory_limit', '1536M');

        $fileName   = $_FILES['import_rates_file']['tmp_name'];
        $csvObject  = new Varien_File_Csv();
        $csvData    = $csvObject->getData($fileName);

        $product_tax_class_id = 2;
        $retail_customer_id = 3;
        $tax_exempt_customer_id = 5;

        // Need to append current time to tax codes/rules to avoid uniqueness collisions in tables
        $timestamp = time();

        // The first row of data is the names of the columns, so start at the
        // second element of the array
        unset($csvData[0]);

        $this->taxRuleData = array();
        // Insert every tax rate into a tax rule
        $i = 0;
        foreach ($csvData as $zip_data_array)
        {
            $this->insert_tax_rate_into_rule($zip_data_array);
$i++; 
if ($i%1000 == 0) { 
    $this->logImport("Called insert_tax_rate_into_rule $i times."); 
}
        }
$this->profileImport();
        unset($csvData);
        
        // The customer tax class depends on the 2nd digit in the tax code
        $customer_tax_classes = array(
            0 => $tax_exempt_customer_id,
            1 => $retail_customer_id
        );
        
        // Insert values into the tax_calculation_rate table, marked as temp
        // so they don't conflict with the existing rates
        $x = 0;
        foreach($this->taxRuleData as $prefix => &$subsets)
        {
            $customer_tax_class = substr($prefix, 1, 1);
            
            // Examples of prefix_subsets are 000_1, 000_2, 000_3, etc.
            foreach ($subsets as $subset => &$zipcodes)
            {
                $tax_rates = array();
                foreach ($zipcodes as $zipcode => &$rate_data)
                {
                    // Create a tax_calculation_rate row for this code
                    $tax_rate = Mage::getModel('tax/calculation_rate');
                    $tax_rate->setCode($rate_data[self::tax_code_index]."_".$timestamp);
                    $tax_rate->setTaxCountryId($rate_data[self::country_index]);
                    $region_id = $this->getRegionId($rate_data[self::region_index], $rate_data[self::country_index]);
                    $tax_rate->setTaxRegionId($region_id);
                    $tax_rate->setTaxPostcode($rate_data[self::zipcode_index]);
                    $tax_rate->setRate($rate_data[self::rate_index]);
                    $tax_rate->save();
                    $tax_rates[] = $tax_rate->tax_calculation_rate_id;
                    
                    //minimuze memory usage. we don't need this data anymore since it's been saved.
                    $rate_data = null;
$x++;
if ($x%200 == 0) {
    $this->logImport('Rate building. x='.$x.' | count(tax_rates)='.count($tax_rates));
    $this->profileImport();
}
                }

                // Insert row into tax_calculation_rule for the rule which pertains
                // to all of these newly created tax_calculation_rates
                $code = $prefix."_".$subset;
                $ruleData = array(
                    'tax_rate'              => $tax_rates,
                    'tax_product_class'     => array($product_tax_class_id),
                    'tax_customer_class'    => array($customer_tax_classes[$customer_tax_class]),
                    'code'                  => $code."_".$timestamp,
                    'priority'              => 0,
                    'position'              => 0
                );
$this->logImport('Create rule "'.$code.'_'.$timestamp.'" with '.count($tax_rates).' rates');
$this->profileImport();
                Mage::getModel('tax/calculation_rule')->setData($ruleData)->save();
                
                //minimuze memory usage. we don't need this data anymore since it's been saved.
                $zipcodes = null;
$this->profileImport();
            }
            
            //minimuze memory usage. we don't need this data anymore since it's been saved.
            $subsets = null;
$this->profileImport();
        }
        unset($this->taxRuleData);
        
$this->logImport('about to delete old rules and rates');
$this->profileImport();
        // Delete all old calculation_rules (Magento will delete tax_calculation rows)
        $to_delete = Mage::getModel('tax/calculation_rule')->getCollection()->addFieldToFilter('code', array('nlike' => '%_'.$timestamp));
$this->profileImport();
        foreach ($to_delete->getItems() as $rule)
        {
            $rule->delete();
        }
$this->logImport('deleted rules. now going to delete rates');
$this->profileImport();
        // Delete all old calculation_rates
        // Uese ICC_TaxRates_Model_Rate, a version of native Magento's tax rates model optimized for this importer's deleting
$this->profileImport();
        $this->batchDeleteRates($timestamp);
$this->logImport('old rules and rates deleted!');

        /* Update rules for downloadable products from California */
        Mage::getModel('taxrates/downloadable')->run();
    }
    
    protected function batchDeleteRates($timestamp, $batchSize = 200)
    {
        $done = false;
        while (!$done) {
            $rates = Mage::getModel('taxrates/rate')->getCollection()
                                                    ->addFieldToFilter('code', array('nlike' => '%_'.$timestamp))
                                                    ->setPageSize($batchSize);
            if ($rates->count() < 1) {
                $done = true;
                break;
            }
            
            foreach ($rates as $rate) {
                $rate->delete();
            }
        }
    }
    
    
    /**
     * Adds the given tax rate data (as an array) to the structured, master taxRuleData array
     * 
     * @param array $tax_rate_data 
     */
    protected function insert_tax_rate_into_rule($tax_rate_data)
    {        
        $code = $tax_rate_data[self::tax_code_index];
        // The prefix is the first 3 digits in the tax code
        $prefix = substr($code, 0, 3);
        $zipcode = $tax_rate_data[self::zipcode_index];
        $subset = $this->checkAndIncrPrefixZip($prefix, $zipcode);
        
        // create prefix level if not set
        if (!isset($this->taxRuleData[$prefix])) {
            $this->taxRuleData[$prefix] = array();
        }
        
        // create subset of that prefix if not set
        if (!isset($this->taxRuleData[$prefix][$subset])) {
            $this->taxRuleData[$prefix][$subset] = array();
        }
        
        // insert this rate's data
        // should never colide with another zipcode due to the subsetting
        $this->taxRuleData[$prefix][$subset][$zipcode] = $tax_rate_data;
    }
    
    /**
     * (Add to and) lookup the number of times we've seen this prefix/zip combination.
     * 
     * @param string $prefix
     * @param string $zip
     * @return int
     */
    protected function checkAndIncrPrefixZip($prefix, $zip)
    {
        $key = $prefix . "|" . $zip;
        $cur = 0;
        if (isset($this->prefixZipIncr[$key])) {
            $cur = ++$this->prefixZipIncr[$key];
        } else {
            $this->prefixZipIncr[$key] = $cur;
        }
        return $cur;
    }
    
    /**
     * Lookup region ID by values of $region and $country.
     * Memoized.
     * 
     * @param string $region - must match values in Magento's region table.
     * @param type $country - must match value in Magento's region table
     * @return int - Region ID 
     */
    protected function getRegionId($region, $country)
    {
        $key = $region . "|" . $country;
        if (!isset($this->regionTable[$key])) {
            $this->regionTable[$key] = $this->getRegionDirectory()
                                            ->loadByCode($region, $country)
                                            ->getRegionId();
        } 
        return $this->regionTable[$key];
    }
    
    /**
     * Loads the Magento region directory model.
     * 
     * @return Mage_Directory_Model_Region 
     */
    protected function getRegionDirectory()
    {
        if (is_null($this->regionDirectory)) {
            $this->regionDirectory = Mage::getModel('directory/region');
        }
        return $this->regionDirectory;
    }
}

?>
