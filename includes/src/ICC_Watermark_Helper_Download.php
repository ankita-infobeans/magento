<?php
class ICC_Watermark_Helper_Download extends Mage_Downloadable_Helper_Download
{
    const DIR_WATERMARK     = '/pdf_watermark';
    const DIR_WATERMARK_VOLUME_LICENCE = '/pdf_watermark_volumelicence';
    const DIR_CUSTOM_STAMPS = '/pdf_watermark/stamp_files';

    protected $_isTemp = false;
    protected $_fileName;
    /**
     * @var array Stamp configurations
     */
    protected $_stamps;
    /**
     * @var string Custom stamp file content in custom stamps directory
     */
    protected $_stampFileContent;

    protected $_product;

    protected $_linkPurchasedItem;

    /**
     * @var Mage_Customer_Model_Customer
     */
    protected $_customer;

    protected $_placeholders = array(
        '%COPYRIGHT_HOLDER%',
        '%USER_NAME%',
        '%DATE%',
        '%IMAGE_DIR%',
        '%STAMP_IMAGE%',
        '%ORDER_NUMBER%',
        '%ORGANIZATION_NAME%'
    );

    /**
     * Set resource file for download
     *
     * @param string $resourceFile
     * @param string $linkType
     * @return Mage_Downloadable_Helper_Download
     */
    public function setResource($resourceFile, $linkType = self::LINK_TYPE_FILE,$checkGift=Null)
    {
       // echo '*********<br>'.$checkGift.'<br>****************';exit('Priyesh1');
		$product = Mage::registry('downloadable_product');
		
        if($linkType == self::LINK_TYPE_FILE && pathinfo($resourceFile,PATHINFO_EXTENSION) == 'pdf' && isset($product) ){


            if(!$product->getResource()->getAttributeRawValue($product->getId(),'has_copyright',Mage::app()->getStore())){
                return parent::setResource($resourceFile,$linkType);
            }

            //Try to stamp pdf file. If an exception is thrown, or stamppdf
            //does not create the new pdf for some reason, the original resource file will be used.
            //If the stamped file was created successfully, it will be used as the resource file.
            try {

                $stampHelper = Mage::helper('gorilla_stamppdf');

                $stamp = Mage::getModel('gorilla_stamppdf/stamp');

                // Use custom stamp file content for download or product,otherwise use stamp configurations
                if($stampFileContent = $this->getStampFileContent()) {
                    $stampFileContent = $this->replacePlaceholders($stampFileContent);
                    $stamp->setStampFileContent($stampFileContent);
                }
                else {
                    //echo $product->getData('volume_license').'Mehta';exit;
                    if($product->getData('volume_license') == '1' && $checkGift == '0'){
                        // echo $product->getData('volume_license').'Mehta';exit;
                        $stampConfigurations = $this->getVolumeLicenceStampConfigurations();
                    }else{
                        $stampConfigurations = $this->getStampConfigurations();
                    }
                    
                    //$stampConfigurations = $this->getStampConfigurations();
                    foreach($stampConfigurations as $conf) {
                        switch($conf['type']) {
                            case 'text':
                                $stamp->addTextMessage($conf['text'], $conf['opts']);
                                break;
                            case 'image':
                                $stamp->addImageMessage($conf['path'], $conf['opts']);
                                break;
                        }
                    }
                }

                $name            = pathinfo($resourceFile, PATHINFO_BASENAME);
                $tempFile        = tempnam(realpath(sys_get_temp_dir()),$name) . 'pdf';
                $outFile         = $stampHelper->stampPdf($stamp, $resourceFile,$tempFile);

                if(!is_file($outFile)){
                    throw new Exception(sprintf('stamppdf did not successfully create the file %s',$outFile));
                }
                $this->_fileName = $name;
                $this->_isTemp   = true;
                $resourceFile    = $outFile;

            } catch(Exception $e) {
                Mage::logException($e);
            }
        }

        if (self::LINK_TYPE_FILE == $linkType) {
            //check LFI protection
            /** @var $helper Mage_Core_Helper_Data */
            $helper = Mage::helper('core');
            $helper->checkLfiProtection($resourceFile);
        }


        $this->_resourceFile    = $resourceFile;
        $this->_linkType        = $linkType;

        return $this;
    }

    /**
     * Return custom stamp file content for given product. Stamp files will be in the format {link_filename}.txt or {product_id}.txt.
     * All will be in the stamps media directory following the standard media folder structure using two folders.  For example, if the file is my_pdf.pdf,
     * it the stamp configurations file should be uploaded to media/pdf_watermark/stamp_files/m/y/my_pdf.pdf.txt.  The product id is 12345 then it should be uploaded to
     * media/pdf_watermark/stamp_files/1/2/12345.txt.
     *
     * @return string|false
     */
    public function getStampFileContent()
    {
  
        if($this->_stampFileContent == null) {
 

            $dir = Mage::getBaseDir('media') . self::DIR_CUSTOM_STAMPS;
  

            // Set in ICC_Watermark_DownloadController::linkAction
            $linkPurchasedItem = Mage::registry('link_purchased_item');

            // Check for PDF-specfic StampPDF stamp file
            $pdfStampFile = $dir . $linkPurchasedItem->getLinkFile() . '.txt';
            if($pdfStampFile && is_readable($pdfStampFile)) {
                $this->_stampFileContent = file_get_contents($pdfStampFile);
                return $this->_stampFileContent;
            }

            // Check for product-specific StampPDF stamp file
            $productId = (string)$linkPurchasedItem->getProductId();
            $pdfStampFile = $dir . '/' . substr($productId, 0, 1) . '/' . substr($productId, 1, 1) . '/' . $productId . '.txt';

            if($pdfStampFile && is_readable($pdfStampFile)) {
                $this->_stampFileContent = file_get_contents($pdfStampFile);
                return $this->_stampFileContent;
            }
        }

        return $this->_stampFileContent;
    }
    
    /**
     * Get stamp configurations for current downloadable volume licence product
     *
     */
    public function getVolumeLicenceStampConfigurations()
    {

        if($this->_stamps != null) {
            return $this->_stamps;
        }

        $product = Mage::registry('downloadable_product');

        $this->_stamps = array();

        // Get image stamp configurations
        if($imagePath = $this->getWatermarkVolumeLicenceFilePath()) {
            $scale = trim($product->getWatermarkImgScale());
            if(!$scale) {
                $scale = Mage::getStoreConfig('icc_watermark/image/scale') ? Mage::getStoreConfig('icc_watermark/image/scale') : 1;
            }


            $bottom = $product->getData('watermark_img_bottom');
            if(!$bottom) {
                $bottom = Mage::getStoreConfig('icc_watermark/image/bottom');
            }

            for($i=1; $i <= 2; $i++) {
                $stamp = array(
                    'type' => 'image',
                    'path' => $imagePath,
                    'opts' => array(
                        'Scale'       => $scale,
                        'Position'    => Mage::getStoreConfig('icc_watermark/image/position_' . $i),
                        'PageBox'     => 'cropbox'
                        //'Underlay'    => 'Yes'
                    )
                );

                $justification = trim($product->getData('watermark_img_justification_' . $i));
                if(!$justification) {
                    $justification = Mage::getStoreConfig('icc_watermark/image/justification_' . $i);
                }
                if($justification) {
                    $stamp['opts']['Justification'] = $justification;
                }

                if($bottom) {
                    $stamp['opts']['Bottom'] = $bottom;
                }

                $left = trim($product->getData('watermark_img_left_' . $i));
                if(!$left) {
                    $left = Mage::getStoreConfig('icc_watermark/image/left_' . $i);
                }
                if($left) {
                    $stamp['opts']['Left'] = $left;
                }

                $right = trim($product->getData('watermark_img_right_' . $i));
                if(!$right) {
                    $right = Mage::getStoreConfig('icc_watermark/image/right_' . $i);
                }
                if($right) {
                    $stamp['opts']['Right'] = $right;
                }

                $startPage = trim($product->getData('watermark_img_start_page_' . $i));
                if(!$startPage) {
                    $startPage = Mage::getStoreConfig('icc_watermark/image/start_page_' . $i);
                }
                if($startPage) {
                    $stamp['opts']['StartPage'] = $startPage;
                }

                $pageIncrement = trim($product->getData('watermark_img_page_increment_' . $i));
                if(!$pageIncrement) {
                    $pageIncrement = Mage::getStoreConfig('icc_watermark/image/page_increment_' . $i);
                }
                if($pageIncrement) {
                    $stamp['opts']['PageIncrement'] = $pageIncrement;
                }

                $endPage = trim($product->getData('watermark_img_end_page_' . $i));
                if(!$endPage) {
                    $endPage = Mage::getStoreConfig('icc_watermark/image/end_page_' . $i);
                }
                if($endPage) {
                    $stamp['opts']['EndPage'] = $endPage;
                }

                $this->_stamps[] = $stamp;
            }

        }

        // Copyright stamps
        $copyrightText   = Mage::getStoreConfig('icc_watermark/copyright/copyright_volumelicence');
        $copyrightText = $this->replacePlaceholders($copyrightText);

        $fontSize = Mage::getStoreConfig('icc_watermark/copyright/font_size');
        $font     = Mage::getStoreConfig('icc_watermark/copyright/font');

        $top = trim($product->getData('copyright_top'));
        if(!$top) {
            $top = Mage::getStoreConfig('icc_watermark/copyright/top');
        }

        for($i=1; $i <= 2; $i++) {
            $stamp = array(
                'type' => 'text',
                'text' => $copyrightText,
                'opts' => array(
                    'Font'     => $font,
                    'Size'     => $fontSize,
                    'Position' => Mage::getStoreConfig('icc_watermark/copyright/position_' . $i),
                    'WordWrap' => 'Yes',
                    'PageBox'  => 'cropbox',
                    'Top'      => $top
                )
            );

            $justification = trim($product->getData('copyright_justification_' . $i));
            if(!$justification) {
                $justification = Mage::getStoreConfig('icc_watermark/copyright/justification_' . $i);
            }
            if($justification) {
                $stamp['opts']['Justification'] = $justification;
            }

            $left = trim($product->getData('copyright_left_' . $i));
            if(!$left) {
                $left = Mage::getStoreConfig('icc_watermark/copyright/left_' . $i);
            }
            if($left) {
                $stamp['opts']['Left'] = $left;
            }

            $right = trim($product->getData('copyright_right_' . $i));
            if(!$right) {
                $right = Mage::getStoreConfig('icc_watermark/copyright/right_' . $i);
            }
            if($right) {
                $stamp['opts']['Right'] = $right;
            }

            $startPage = trim($product->getData('copyright_start_page_' . $i));
            if(!$startPage) {
                $startPage = Mage::getStoreConfig('icc_watermark/copyright/start_page_' . $i);
            }
            if($startPage) {
                $stamp['opts']['StartPage'] = $startPage;
            }

            $pageIncrement = trim($product->getData('copyright_page_increment_' . $i));
            if(!$pageIncrement) {
                $pageIncrement = Mage::getStoreConfig('icc_watermark/copyright/page_increment_' . $i);
            }
            if($pageIncrement) {
                $stamp['opts']['PageIncrement'] = $pageIncrement;
            }

            $endPage = trim($product->getData('copyright_end_page_' . $i));
            if(!$endPage) {
                $endPage = Mage::getStoreConfig('icc_watermark/copyright/end_page_' . $i);
            }
            if($endPage) {
                $stamp['opts']['EndPage'] = $endPage;
            }

            $this->_stamps[] = $stamp;
        }


        //Start Gorilla Ticket#2013041910000307 - Stamp order number on page 30
        $this->_stamps[] = array(
            'type' => 'text',
            'text' => $this->getOrderNumber(),
            'opts' => array(
                'font'          => 'Times Roman',
                'size'          => '4',
                'bottom'        => '390',
                'left'          => '594', // margin 1/4 inch = 612 - 18 http://docs.appligent.com/stamppdf-batch/stamp-files/text-stamps/#Positioning_parameters
                'angle'         => '-90',
                'pageRange'     => '30',
                'Position'      => 'Angle',
                'Justification' => 'Left'
            )
        );
        //Gorilla End

        return $this->_stamps;
    }

    /**
     * Get stamp configurations for current downloadable product
     *
     */
    public function getStampConfigurations()
    {

        if($this->_stamps != null) {
            return $this->_stamps;
        }

        $product = Mage::registry('downloadable_product');

        $this->_stamps = array();

        // Get image stamp configurations
        if($imagePath = $this->getWatermarkFilePath()) {
            $scale = trim($product->getWatermarkImgScale());
            if(!$scale) {
                $scale = Mage::getStoreConfig('icc_watermark/image/scale') ? Mage::getStoreConfig('icc_watermark/image/scale') : 1;
            }


            $bottom = $product->getData('watermark_img_bottom');
            if(!$bottom) {
                $bottom = Mage::getStoreConfig('icc_watermark/image/bottom');
            }

            for($i=1; $i <= 2; $i++) {
                $stamp = array(
                    'type' => 'image',
                    'path' => $imagePath,
                    'opts' => array(
                        'Scale'       => $scale,
                        'Position'    => Mage::getStoreConfig('icc_watermark/image/position_' . $i),
                        'PageBox'     => 'cropbox'
                        //'Underlay'    => 'Yes'
                    )
                );

                $justification = trim($product->getData('watermark_img_justification_' . $i));
                if(!$justification) {
                    $justification = Mage::getStoreConfig('icc_watermark/image/justification_' . $i);
                }
                if($justification) {
                    $stamp['opts']['Justification'] = $justification;
                }

                if($bottom) {
                    $stamp['opts']['Bottom'] = $bottom;
                }

                $left = trim($product->getData('watermark_img_left_' . $i));
                if(!$left) {
                    $left = Mage::getStoreConfig('icc_watermark/image/left_' . $i);
                }
                if($left) {
                    $stamp['opts']['Left'] = $left;
                }

                $right = trim($product->getData('watermark_img_right_' . $i));
                if(!$right) {
                    $right = Mage::getStoreConfig('icc_watermark/image/right_' . $i);
                }
                if($right) {
                    $stamp['opts']['Right'] = $right;
                }

                $startPage = trim($product->getData('watermark_img_start_page_' . $i));
                if(!$startPage) {
                    $startPage = Mage::getStoreConfig('icc_watermark/image/start_page_' . $i);
                }
                if($startPage) {
                    $stamp['opts']['StartPage'] = $startPage;
                }

                $pageIncrement = trim($product->getData('watermark_img_page_increment_' . $i));
                if(!$pageIncrement) {
                    $pageIncrement = Mage::getStoreConfig('icc_watermark/image/page_increment_' . $i);
                }
                if($pageIncrement) {
                    $stamp['opts']['PageIncrement'] = $pageIncrement;
                }

                $endPage = trim($product->getData('watermark_img_end_page_' . $i));
                if(!$endPage) {
                    $endPage = Mage::getStoreConfig('icc_watermark/image/end_page_' . $i);
                }
                if($endPage) {
                    $stamp['opts']['EndPage'] = $endPage;
                }

                $this->_stamps[] = $stamp;
            }

        }

        // Copyright stamps
        $copyrightText   = Mage::getStoreConfig('icc_watermark/copyright/copyright');
        $copyrightText = $this->replacePlaceholders($copyrightText);

        $fontSize = Mage::getStoreConfig('icc_watermark/copyright/font_size');
        $font     = Mage::getStoreConfig('icc_watermark/copyright/font');

        $top = trim($product->getData('copyright_top'));
        if(!$top) {
            $top = Mage::getStoreConfig('icc_watermark/copyright/top');
        }

        for($i=1; $i <= 2; $i++) {
            $stamp = array(
                'type' => 'text',
                'text' => $copyrightText,
                'opts' => array(
                    'Font'     => $font,
                    'Size'     => $fontSize,
                    'Position' => Mage::getStoreConfig('icc_watermark/copyright/position_' . $i),
                    'WordWrap' => 'Yes',
                    'PageBox'  => 'cropbox',
                    'Top'      => $top
                )
            );

            $justification = trim($product->getData('copyright_justification_' . $i));
            if(!$justification) {
                $justification = Mage::getStoreConfig('icc_watermark/copyright/justification_' . $i);
            }
            if($justification) {
                $stamp['opts']['Justification'] = $justification;
            }

            $left = trim($product->getData('copyright_left_' . $i));
            if(!$left) {
                $left = Mage::getStoreConfig('icc_watermark/copyright/left_' . $i);
            }
            if($left) {
                $stamp['opts']['Left'] = $left;
            }

            $right = trim($product->getData('copyright_right_' . $i));
            if(!$right) {
                $right = Mage::getStoreConfig('icc_watermark/copyright/right_' . $i);
            }
            if($right) {
                $stamp['opts']['Right'] = $right;
            }

            $startPage = trim($product->getData('copyright_start_page_' . $i));
            if(!$startPage) {
                $startPage = Mage::getStoreConfig('icc_watermark/copyright/start_page_' . $i);
            }
            if($startPage) {
                $stamp['opts']['StartPage'] = $startPage;
            }

            $pageIncrement = trim($product->getData('copyright_page_increment_' . $i));
            if(!$pageIncrement) {
                $pageIncrement = Mage::getStoreConfig('icc_watermark/copyright/page_increment_' . $i);
            }
            if($pageIncrement) {
                $stamp['opts']['PageIncrement'] = $pageIncrement;
            }

            $endPage = trim($product->getData('copyright_end_page_' . $i));
            if(!$endPage) {
                $endPage = Mage::getStoreConfig('icc_watermark/copyright/end_page_' . $i);
            }
            if($endPage) {
                $stamp['opts']['EndPage'] = $endPage;
            }

            $this->_stamps[] = $stamp;
        }


        //Start Gorilla Ticket#2013041910000307 - Stamp order number on page 30
        $this->_stamps[] = array(
            'type' => 'text',
            'text' => $this->getOrderNumber(),
            'opts' => array(
                'font'          => 'Times Roman',
                'size'          => '4',
                'bottom'        => '390',
                'left'          => '594', // margin 1/4 inch = 612 - 18 http://docs.appligent.com/stamppdf-batch/stamp-files/text-stamps/#Positioning_parameters
                'angle'         => '-90',
                'pageRange'     => '30',
                'Position'      => 'Angle',
                'Justification' => 'Left'
            )
        );
        //Gorilla End

        return $this->_stamps;
    }


    public function replacePlaceholders($text)
    {

        foreach($this->_placeholders as $search) {
            if(strpos($text, $search) !== false) {
                switch($search) {
                    case '%COPYRIGHT_HOLDER%':
                        $text = str_replace($search, $this->getCopyrightHolder(), $text);
                        break;
                    case '%USER_NAME%':
                        $customer = $this->getCustomer();
                        $text = str_replace($search, $customer->getName(), $text);
                        break;
                    case '%DATE%':
                        $text = str_replace($search, $this->getDate(), $text);
                        break;
                    case '%IMAGE_DIR%':
                        $text = str_replace($search, $this->getWatermarkImageDir(), $text);
                        break;
                    case '%STAMP_IMAGE%':
                        $text = str_replace($search, $this->getWatermarkFilePath(), $text);
                        break;
                    case '%ORDER_NUMBER%':
                        $text = str_replace($search, $this->getOrderNumber(), $text);
                        break;
                    case '%ORGANIZATION_NAME%':
                        $orgName = $customer->getOrgName();
                       //echo $orgName;exit('Priyesh1');
                        if(empty($orgName)){
                            $orgName = '-';
                        }
                        $text = str_replace($search,$orgName , $text);
                        break;
                }
            }
        }

        return $text;
    }


    public function getProduct()
    {
        if($this->_product == null) {
            $this->_product = Mage::registry('downloadable_product');
        }
        return $this->_product;
    }

    public function getLinkPurchasedItem()
    {
        if($this->_linkPurchasedItem == null) {
            $this->_linkPurchasedItem = Mage::registry('link_purchased_item');
        }
        return $this->_linkPurchasedItem;
    }

    public function getCustomer()
    {
        if($this->_customer == null) {
           $this->_customer = Mage::getSingleton('customer/session')->getCustomer();
        }
        return $this->_customer;
    }

    public function getCopyrightHolder()
    {
        $product = $this->getProduct();
        $copyrightHolder = $product->getIccCopyrightHolder()
            ? $product->getIccCopyrightHolder()
            : Mage::getStoreConfig('icc_watermark/copyright/default_copyright_holder');
        return $copyrightHolder;
    }

    public function getDate()
    {
        $linkPurchaseItem = $this->getLinkPurchasedItem();
        $date = Mage::helper('core')->formatDate($linkPurchaseItem->getCreatedAt(),Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM,true);
        return $date;
    }


    public function getOrderNumber()
    {
        $orderItemId = Mage::registry('link_purchased_item')->getOrderItemId();
        $linkPurchased = Mage::getModel('downloadable/link_purchased')->load($orderItemId,'order_item_id');
        $orderIncrementId = $linkPurchased->getOrderIncrementId();
        return $orderIncrementId;
    }

    public function getFilename()
    {
        $handle = $this->_getHandle();
        if ($this->_linkType == self::LINK_TYPE_FILE) {
            //Returning a nice file name rather than a randomly generated one for stamped files.
            return $this->_fileName ? $this->_fileName : pathinfo($this->_resourceFile, PATHINFO_BASENAME);
        }
        elseif ($this->_linkType == self::LINK_TYPE_URL) {
            if (isset($this->_urlHeaders['content-disposition'])) {
                $contentDisposition = explode('; ', $this->_urlHeaders['content-disposition']);
                if (!empty($contentDisposition[1]) && strpos($contentDisposition[1], 'filename=') !== false) {
                    return substr($contentDisposition[1], 9);
                }
            }
            if ($fileName = @pathinfo($this->_resourceFile, PATHINFO_BASENAME)) {
                return $fileName;
            }
        }
        return $this->_fileName;
    }

    public function output()
    {
    
      
        $restrictionError = null;
        if ($this->_linkType == self::LINK_TYPE_FILE){
            try {
                $pdfRestrictionHelper = Mage::helper('icc_pdfrestriction');
                if($pdfRestrictionHelper) {
                    $restrictionError = Mage::helper('icc_pdfrestriction')->setRestrictions($this->_resourceFile);
                }
            } catch(Exception $e) {
                Mage::logException($e);
            }

        }

        Mage::log($this->_linkType.' --- '.self::LINK_TYPE_URL.' --- '.$restrictionError, null , 'mylog.log');
        
        if(($this->_linkType == self::LINK_TYPE_URL) || ($restrictionError === false || $restrictionError != null)){
            parent::output();
        }
        //Remove temp file
        if($this->_isTemp){
            @unlink($this->_resourceFile);
        } 

    }


    public function getWatermarkImageDir()
    {
        return Mage::getBaseDir('media') . self::DIR_WATERMARK . '/';
    }
    
    public function getWatermarkIVolumeLicenceImageDir()
    {
        return Mage::getBaseDir('media') . self::DIR_WATERMARK_VOLUME_LICENCE . '/';
    }
    
    /**
     * Get path to watermark image for volume licence product
     * @return bool|string
     */
    public function getWatermarkVolumeLicenceFilePath()
    {
        $filePath = false;

        $watermarkDir = $this->getWatermarkIVolumeLicenceImageDir();
       
        // Get file path from product, if any
        if($product = $this->getProduct()) {
            if($product->getWatermarkImg()) {
                if($this->_fileExists($watermarkDir . $product->getWatermarkImg())) {
                    $filePath = $watermarkDir . $product->getWatermarkImg();
                    return $filePath;
                }
                else { // @todo Log error
                    return false; // Do not do stamp image if it should have a different stamp
                }
            }
        }
        // Use global watermark image, if any
        if($file = Mage::getStoreConfig('icc_watermark/image/image_volumelicence')) {

//            if( $this->_fileExists($watermarkDir . 'stores/' . Mage::app()->getStore()->getId() . $file) ) {
//                $filePath = $watermarkDir . 'stores/' . Mage::app()->getStore()->getId() . $file;
//            } elseif ( $this->_fileExists($watermarkDir . 'websites/' . Mage::app()->getWebsite()->getId() . $file) ) {
//                $filePath = $watermarkDir . 'websites/' . Mage::app()->getWebsite()->getId() . $file;
//            } elseif ( $this->_fileExists($watermarkDir . 'default/' . $file) ) {
//                $filePath = $watermarkDir . 'default/' . $file;
            if ( $this->_fileExists($watermarkDir . $file) ) {
                $filePath = $watermarkDir . $file;
            }
            else { // Log error if configured stamp file not found

            }

        }

        return $filePath;
    }
    
    /**
     * Get path to watermark image for product
     * @return bool|string
     */
    public function getWatermarkFilePath()
    {
        $filePath = false;

        $watermarkDir = $this->getWatermarkImageDir();

        // Get file path from product, if any
        if($product = $this->getProduct()) {
            if($product->getWatermarkImg()) {
                if($this->_fileExists($watermarkDir . $product->getWatermarkImg())) {
                    $filePath = $watermarkDir . $product->getWatermarkImg();
                    return $filePath;
                }
                else { // @todo Log error
                    return false; // Do not do stamp image if it should have a different stamp
                }
            }
        }

        // Use global watermark image, if any
        if($file = Mage::getStoreConfig('icc_watermark/image/image')) {

//            if( $this->_fileExists($watermarkDir . 'stores/' . Mage::app()->getStore()->getId() . $file) ) {
//                $filePath = $watermarkDir . 'stores/' . Mage::app()->getStore()->getId() . $file;
//            } elseif ( $this->_fileExists($watermarkDir . 'websites/' . Mage::app()->getWebsite()->getId() . $file) ) {
//                $filePath = $watermarkDir . 'websites/' . Mage::app()->getWebsite()->getId() . $file;
//            } elseif ( $this->_fileExists($watermarkDir . 'default/' . $file) ) {
//                $filePath = $watermarkDir . 'default/' . $file;
            if ( $this->_fileExists($watermarkDir . $file) ) {
                $filePath = $watermarkDir . $file;
            }
            else { // Log error if configured stamp file not found

            }

        }

        return $filePath;
    }


    /**
     * First check this file on FS
     * If it doesn't exist - try to download it from DB
     *
     * @param string $filename
     * @return bool
     */
    protected function _fileExists($filename) {
        if (file_exists($filename)) {
            return true;
        } else {
            return Mage::helper('core/file_storage_database')->saveFileToFilesystem($filename);
        }
    }
}
