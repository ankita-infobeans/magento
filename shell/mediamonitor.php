<?php

require dirname(dirname(__FILE__)) . '/app/Mage.php';

if (!Mage::isInstalled()) {
    echo "Application is not installed yet, please complete install wizard first.";
    exit;
}
$initializationCode = 'admin';
$initializationType = 'store';
$scope = 'frontend';
Mage::app($initializationCode, $initializationType);
Mage::getConfig()->init();
Mage::getConfig()->loadEventObservers($scope);
Mage::app()->addEventArea($scope);

abstract class Shell_Mediamonitor_Abstract
{

    protected $_reportType = "abstract";
    protected $_reportFileName;

    abstract function _getCollection();

    abstract public function log($row);

    abstract public function _checkFile($path);

    abstract public function _getPathToFile($row);

    public function checkFiles()
    {
        $collection = $this->_getCollection();
        foreach ($collection as $row) {
            $path = $this->_getPathToFile($row);
            if (!$this->_checkFile($path)) {
                $this->log($row);
            }
        }
        $this->_afterCheck();
    }

    protected function _afterCheck()
    {
        return $this;
    }

    public function getReportFilename()
    {
        if (!$this->_reportFileName) {
            $dir = Mage::getBaseDir("var") . DS . "mediamonitor" . DS;
            if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
                Mage::throwException("Can not create directory " . $dir);
            }
            $filename = $dir . date("YmdHis") . "{$this->_reportType}.csv";
            $this->_reportFileName = $filename;
        }
        return $this->_reportFileName;
    }

    protected function _prepareFileObject()
    {

        $filename = $this->getReportFilename();
        /**
         * For php 5.4
         */
//        $this->_fileObject = new SplFileObject($filename, "w+");
//        $this->_fileObject->setFlags(SplFileObject::READ_CSV);
//        $this->_fileObject->setCsvControl(";");
        /**
         * For php < 5.4
         */
        $this->_fileObject = fopen($filename, "w+");
        return $this;
    }

}

class Shell_Mediamonitor_Mediagallery extends Shell_Mediamonitor_Abstract
{

    /**
     *
     * @var SplFileObject
     */
    protected $_fileObject;
    private $_enclosure = '"';
    private $_delimiter = ";";
    protected $_reportType = "mediagallery";
    protected $_emailSubject;

    public function __construct()
    {
//        $this->_prepareFileObject();
        $this->_emailSubject = Mage::helper("core")->__("Report: Meidamonitor. Images.");
    }

    public function _checkFile($path)
    {
        return file_exists($path);
    }

    public function _getCollection()
    {
        $resource = Mage::getModel("core/resource");
        /* @var $resource Mage_Core_Model_Resource */
        $conn = $resource->getConnection("core_read");
        /* @var $conn Varien_Db_Adapter_Pdo_Mysql */

        /**
         * Check media gallery
         */
        $galleryTableName = $resource->getTableName("catalog/product_attribute_media_gallery");
        $galleryValueTableName = $resource->getTableName("catalog/product_attribute_media_gallery_value");
        $sql = $conn->select()
                ->from(array("e" => $resource->getTableName("catalog/product")), '')
                ->join(array("g" => $galleryTableName), "g.entity_id = e.entity_id", '')
                ->columns(array(
            "SKU"  => "e.sku",
            "path" => "g.value"
                ))
        ;
        $collection = $conn->fetchAll($sql);
        return $collection;
    }

    public function _getPathToFile($row)
    {
        $baseDir = $this->getMediaDir();
        $path = $baseDir . $row['path'];
        return $path;
    }

    public function log($row)
    {
        $data = array(
            $row['SKU'],
            $this->_getPathToFile($row)
        );
        /**
         * Only for php 5.4
         */
//        $this->_fileObject->fputcsv($data, $this->_delimiter, $this->_enclosure);

        fputcsv($this->getFileObject(), $data, $this->_delimiter, $this->_enclosure);
        return $this;
    }

    /**
     *
     * @return string
     */
    public function getMediaDir()
    {
        return Mage::getSingleton('catalog/product_media_config')->getBaseMediaPath();
    }

    public function getFileObject()
    {
        if (!$this->_fileObject) {
            $this->_prepareFileObject();
        }
        return $this->_fileObject;
    }

//    protected function _afterCheck()
//    {
//        $mail = new Shell_Mediamonitor_Mail(array(
//            "to"      => "alykhouzov@gorillagroup.com",
//            "subject" => $this->_emailSubject
//        ));
//        $mail->attachFile($this->getReportFilename());
//        $mail->send();
//        return $this;
//    }
}

class Shell_Mediamonitor_Downloadable extends Shell_Mediamonitor_Mediagallery
{

    protected $_reportType = "downloadable";

    public function __construct()
    {
        parent::__construct();
        $this->_emailSubject = $this->_emailSubject = Mage::helper("core")->__("Report: Meidamonitor. Downloadable files.");
    }

    public function _getCollection()
    {
        $resource = Mage::getModel("core/resource");
        /* @var $resource Mage_Core_Model_Resource */
        $conn = $resource->getConnection("core_read");
        /* @var $conn Varien_Db_Adapter_Pdo_Mysql */

        /**
         * Check downloadable product links
         */
        $galleryTableName = $resource->getTableName("catalog/product_attribute_media_gallery");
        $galleryValueTableName = $resource->getTableName("catalog/product_attribute_media_gallery_value");
        $sql = $conn->select()
                ->from(array("e" => $resource->getTableName("catalog/product")), '')
                ->join(array("g" => "downloadable_link"), "g.product_id = e.entity_id", '')
                ->columns(array(
//                    "SKU"  => new Zend_Db_Expr("GROUP_CONCAT(e.sku)"),
            "SKU"  => "e.sku",
            "path" => "g.link_file"
                ))
//                ->group("path")
        ;
        $collection = $conn->fetchAll($sql);
        return $collection;
    }

    /**
     *
     * @return string
     */
    public function getMediaDir()
    {
        return Mage::getBaseDir("media") . DS . "downloadable" . DS . "files" . DS . "links" . DS;
    }

    public function _getPathToFile($row)
    {
        $baseDir = $this->getMediaDir();
        $path = $baseDir . ltrim($row['path'], "/");
        return $path;
    }

}

class Shell_Mediamonitor_Mail
{

    /**
     *
     * @var Zend_Mail
     */
    private $_mail;
    private $_config = array(
//        "to" => array("rcarroll@iccsafe.org", "support@iccsafe.org"),
    );

    public function __construct($config = array())
    {
        $this->_mail = new Zend_Mail("UTF-8");
        $this->_config['subject'] = Mage::helper("core")->__("Report: Media Monitor.");
        $this->_config = $config + $this->_config;
    }

    public function send()
    {
        if (!empty($this->_config['to'])) {
            $this->_mail->addTo($this->_config['to']);
        }
//        $this->_mail->addBcc("alykhouzov@gorillagroup.com");
        $this->_mail->setSubject($this->_config['subject']);
        $this->_addAttchmets();
//        $this->_mail->setBodyText("See Reports in Attachments.");
        $this->_mail->setFrom(Mage::getStoreConfig("trans_email/ident_general/email"));
        $this->_mail->send();
    }

    protected function _addAttchmets()
    {
        
    }

    public function attachFile($filename, $mimetype = "text/csv")
    {
//        $filepath = Mage::getBaseDir("var") . DS . "20140225081232mediagallery.csv";
        $content = is_string($filename) && realpath($filename) ? file_get_contents($filename) : $filename;
        $at = new Zend_Mime_Part($content);
        $at->filename = basename($filename);
        $at->type = $mimetype;
        $at->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
        $at->encoding = Zend_Mime::ENCODING_BASE64;
        $this->_mail->addAttachment($at);
        return $this;
    }

    public function getMailObject()
    {
        return $this->_mail;
    }

}

$options = getopt("t:b::");
$to = null;
$bcc = null;
if (!empty($options['t'])) {
    $to = $options['t'];
}
if (!empty($options['b'])) {
    $bcc = $options['b'];
}
//var_dump($bcc, $to);
//exit;
try {
    $gallery = new Shell_Mediamonitor_Mediagallery();
    $gallery->checkFiles();
} catch (Exception $e) {
    Mage::logException($e);
}
try {
    $downloadable = new Shell_Mediamonitor_Downloadable();
    $downloadable->checkFiles();
} catch (Exception $e) {
    Mage::logException($e);
}
try {
    $mail = new Shell_Mediamonitor_Mail();
    if ($to) {
        $mail->getMailObject()
                ->addTo($to);
    }
    if ($bcc) {
        $mail->getMailObject()
                ->addTo($bcc);
    }
    $body = "Daily report of media monitoring.\n\n";
    if (filesize($gallery->getReportFilename())) {
        $mail->attachFile($gallery->getReportFilename());
    } else {
        $body .= "No report for Images.\n\n";
    }
    if (filesize($downloadable->getReportFilename())) {
        $mail->attachFile($downloadable->getReportFilename());
    } else {
        $body .= "No report for PDF files.\n\n";
    }
    $mail->getMailObject()->setBodyText($body);

    $mail->send();
} catch (Exception $e) {
    Mage::logException($e);
}
