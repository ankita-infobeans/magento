<?php

class ICC_Ecodes_Model_Downloadable extends Mage_Core_Model_Abstract
{
    const XML_PATH_REPORT_THRESHOLD = 'catalog/ecodes_downloadable/serial_code_pool_notification_threshold';
    const XML_PATH_REPORT_RECIPIENT = 'catalog/ecodes_downloadable/serial_code_pool_notification_email';
    const XML_PATH_EMAIL_TEMPLATE   = 'catalog/ecodes_downloadable/notification_item_template';
    const XML_PATH_EMAIL_IDENTITY   = 'catalog/ecodes_downloadable/serial_code_pool_notification_sender';
    
    public function _construct()
    {
        $this->setIdFieldName('id');
        $this->_init('ecodes/downloadable');
    }

    /**
     * @return ICC_Ecodes_Model_Downloadable
     */
    public function remainingSerialsReport()
    {
	/** added for log tracking by anil 28 jul **/
	$currDate = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));
	$fileName = date("Y-m-d", Mage::getModel('core/date')->timestamp(time()));
	Mage::log("Controller Name : Ecode/Downloadable , Action Name : remainingSerialsReport , Start Time : $currDate",null,$fileName);
        /** end **/
        $threshold = Mage::getStoreConfig(self::XML_PATH_REPORT_THRESHOLD);
        $errors = array();
        if(!is_numeric($threshold) || $threshold < 0)
        {
            $error = "Threshold was not a positive integer.";
            $errors[] = $error;
            Mage::log("Error while attempting to run ".__METHOD__.". ".$error);
        }else{
            $notifications = $this->getCollection()->prepareForRemainingReport($threshold);
            if($notifications->count())
            {
                try{
                    $this->sendNotificationEmail($notifications);
                }catch(Exception $e){
                    Mage::logException($e);
                }
            }
        }
	/** added for log tracking by anil 28 jul start **/
	$currDate = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));
        Mage::log("Controller Name : Ecode/Downloadable , Action Name : remainingSerialsReport , End Time : $currDate",null,$fileName);
	/** end **/
        return $this;
    }

    /**
     * @param $notifications
     * @return ICC_Ecodes_Model_Downloadable
     */
    public function sendNotificationEmail($notifications)
    {
        $storeId = Mage::app()->getStore()->getId();

        $templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE, $storeId);
        $email = Mage::getStoreConfig(self::XML_PATH_REPORT_RECIPIENT, $storeId);

        /* @var Mage_Core_Model_Email_Info */
        $emailInfo = Mage::getModel('core/email_info');
        $emailInfo->addTo($email);

        /* @var $emailTemplate Mage_Core_Model_Email_Template */
        $emailTemplate = Mage::getModel('core/email_template');
        // Handle "Bcc" recepients of the current email
        $emailTemplate->addBcc($emailInfo->getBccEmails());

        // Set required design parameters and delegate email sending to Mage_Core_Model_Email_Template.
        $designConfig = array('area' => ($storeId == 0) ? 'adminhtml' : 'frontend', 'store' => $storeId);
        $emailTemplate->setDesignConfig($designConfig)
            ->sendTransactional(
                $templateId,
                Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $storeId),
                $emailInfo->getToEmails(),
                $emailInfo->getToNames(),
                array('notifications' => $notifications),
                $storeId
        );

        return $this;
    }

    /* ============================================= CSV VALIDATION ==================================================== */
    
    private $_definedHeaders = array(
        'document_id', 
        'gp_sku', 
        'product_title', 
        'serial_number'
    );
    
    protected $_errors = array();
    protected $_failedRows = array();
    protected $_passedRows = array();
    protected $_gpSkus = array();
    protected $_processSuccess = false;
    
    public function processedSuccess()
    {
        return $this->_processSuccess;
    }
    
    public function setProcessSuccess($bool)
    {
        $this->_processSuccess = $bool;
    }
    
    public function getDefindedFileHeaders()
    {
        return $this->_definedHeaders;
    }
    
    public function addError($message, $type = 'messages')
    {
        if( ! isset($this->_errors[$type])) $this->_errors[$type] = array();
        $this->_errors[$type][] = $message;
    }
    
    public function getErrors()
    {
        return $this->_errors;
    }
    
    public function getErrorMessages()
    {
        $_errors = $this->getErrors();
        ksort($_errors); // sort by type as per the CO
        $return_array = array();
        foreach($_errors as $type => $message_array) { // want to remove the type if we just want messages
            $return_array = array_merge((array)$return_array, (array)$message_array );
        }
        return $return_array;
    }
    
    public function hasErrors()
    {
        $_errors = $this->getErrors();
        return (bool) count($_errors);
    }
    
    public function getPassedRows()
    {
        return $this->_passedRows;
    }
    
    public function getNumberPassedRows()
    {
        return count( $this->getPassedRows() );
    }
    
    public function getTotalNumberRows()
    {
        return ( $this->getNumberFailedRows() + $this->getNumberPassedRows() );
    }
    
    public function addPassedRow($row)
    {
        if(is_string($row)) {
            $row = str_getcsv($row);
        }
        $this->_passedRows[] = $row;
    }
    
    public function getNumberFailedRows()
    {
        return count( $this->getFailedRows() );
    }
    
    public function getFailedRows()
    {
        return $this->_failedRows;
    }
    
    public function addFaildedRow($row)
    {
        if(is_string($row)) {
            $row = str_getcsv($row);
        }
        $this->_failedRows[] = $row;
    }
    
    public function hasPassedRows()
    {
        return (bool) count($this->getPassedRows());
    }
    
    public function getGpSkus()
    {
        if(empty($this->_gpSkus)) $this->setGpSkus();
        return $this->_gpSkus;
    }
    
    public function setGpSkus()
    {
        if(empty($this->_gpSkus)) 
        {
            $_downloadProds = Mage::getModel('catalog/product')->getCollection()
                    ->addFieldToFilter('attribute_set_id', 15) // 
                    ->addAttributeToSelect('gp_sku');
            $_downloadProds->load();
            foreach($_downloadProds as $prod) {
                if( $prod->getGpSku() )  $this->_gpSkus[ $prod->getGpSku() ] = $prod->getSku();
            }
        }
    }
    
    public function validateSerialCsvFile($file_path, $file_name)
    {
        if( ! is_readable($file_path)) {
            $this->addError( sprintf('Sorry, I could not find temporary file: %s', $file_path), 'missing_file' );
            return $this;
        }
        
        $file_path_extension = explode('.', $file_name);
        $ext = array_pop($file_path_extension);
        if(strtolower($ext) != 'csv') {
            $this->addError( sprintf('Sorry, I expected a file of extension .csv and %s was received.', $ext), 'file_extension' );
            return $this; // wrong file type - do not proceed with importation
        }
        $csv_array = file($file_path);
        if($csv_array === false) {
            $this->addError( 'Could not read file. The file may contain corrupted values.', 'missing_file' );
            return $this;
        }
        $this->validateSerialCsvArray($csv_array);
        return $this;
    }
    
    /**
     *
     * @param array $file_path
     * @return array | bool
     */
    public function validateSerialCsvArray(array $csv_array)
    {
        $_columnHeaders = str_getcsv(array_shift($csv_array)); // defaults to ',' and enclosure '"'
        for($i=0; $i < count($_columnHeaders); $i++) {
            $_columnHeaders[$i] = trim($_columnHeaders[$i]); // strip the white space to ensure matches
        }
        
        $this->validateColumnHeaders($_columnHeaders);
        if($this->hasErrors()) 
            return $this; // if we have errors at this point we should not process any further
        
        $this->setColumnHeaderPositions($_columnHeaders);
        $_numSerialRows = count($csv_array);
        $this->setGpSkus();
        
        for($i=0; $i < $_numSerialRows; $i++) {
            if($this->validateSerialRow($csv_array[$i], $i)) {
                $this->addPassedRow($csv_array[$i]);
            } else {
                $this->addFaildedRow($csv_array[$i]);
            }
        }
        return $this;
    }
    
    public function setColumnHeaderPositions($headers)
    {
        $_positionHeaders = array_flip($headers);
        foreach($_positionHeaders as $_header => $_position) {
            $this->setData($_header . '_position', $_position);
        }
    }
    
    /**
     *
     * @param array $headers
     * @return array | bool
     */
    public function validateColumnHeaders(array $fileHeaders)
    {   
        $_definedHeders = $this->getDefindedFileHeaders();
        $_correctNumberHeaders = count($_definedHeders);
        if( count($fileHeaders) !== $_correctNumberHeaders ) {
            $this->addError( 
                sprintf( 
                    'Column header error: there were %d headers and we were looking for %d.', 
                    count($fileHeaders),
                    $_correctNumberHeaders
                ),
                'file_headers'
            );
        }
        
        foreach($_definedHeders as $_header)
        {
            if( ! in_array($_header, $fileHeaders)) {
                $this->addError( 
                        sprintf(
                            'Column header error: "%s" is not present or misspelled.', 
                            $_header
                        ),
                        'file_headers'
                );  
            }
        }
    }
    
    public function validateSerialRow($row, $rowNumber)
    {
        $rowNumber += 2; // adjust for 0 offset and for fist row being popped (the headers)
        $_isValid = true;
        $_rowVals = (is_string($row)) ? (str_getcsv($row)) : $row;
        
        // validate the fields that we can
        $_gpSku = $_rowVals[ $this->getData('gp_sku_position') ];
        $_validGpSku = $this->validateGpSku($_gpSku);
        if( ! $_validGpSku ) {
            $this->addError( 
                sprintf(
                    'Great Plains Sku error on line: %s. The Great Plains Sku "%s" is not associated to any product.', 
                    $rowNumber, 
                    $_gpSku
                ),
                'gp_sku'
            );
            $_isValid = false;
        }
        
        $_serial = $_rowVals[ $this->getData('serial_number_position') ];
        $_validSerialNumber = $this->validateSerial($_serial);
        
        if( ! $_validSerialNumber ) {
            $this->addError(
                sprintf(
                    'Serial Number error on line: %s. The Serial Number "%s" has already been added.', 
                    $rowNumber, 
                    $_serial
                ),
                'serial_number'
            );
            $_isValid = false;
        }
        return $_isValid;
    }
    
    public function validateGpSku($sku)
    {
        $_gpArr = $this->getGpSkus();
        $_gpSkus = array_keys($_gpArr);
        return in_array($sku, $_gpSkus);
    }
    
    public function validateSerial($serial)
    {
        if(empty($serial)) return false;
        $_d = Mage::getModel('ecodes/downloadable');
        $_d->load($serial, 'serial');
        return ! (bool) $_d->getId();
    }
    
    public function processPassedRows()
    {
        $rows = $this->getPassedRows();
        $conn = $this->getCollection()->getConnection();
        $conn->beginTransaction();
        $_gpSkus = $this->getGpSkus();
        try {
            foreach($rows as $row)
            {
                $_rowGpSku = $row[ $this->getData('gp_sku_position') ];
                $_d = Mage::getModel('ecodes/downloadable');
                $_d->setEnabled(true);
                $_d->setCreatedAt(time());
                $_d->setMageSku( $_gpSkus[$_rowGpSku] );
                $_d->setGpSku($_rowGpSku);
                $_d->setDocumentId($row[ $this->getData('document_id_position') ]);
                $_d->setProductTitle($row[ $this->getData('product_title_position') ]);
                $_d->setSerial($row[ $this->getData('serial_number_position') ]);
                $_d->save();
            }
            $conn->commit();
            $this->setProcessSuccess(true);
        } catch (Exception $e) {
            $this->addError($e->getMessage());
            $conn->rollBack();
            $this->setProcessSuccess(false);
        }
        return $this;
    }

}
