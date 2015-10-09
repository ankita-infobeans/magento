<?php

class ICC_Downloadable_Helper_File extends Mage_Downloadable_Helper_File
{

    protected function _moveFileFromTmp($baseTmpPath, $basePath, $file)
    {
        $ioObject = new Varien_Io_File();
        $destDirectory = dirname($this->getFilePath($basePath, $file));
        try {
            $ioObject->open(array('path'=>$destDirectory));
        } catch (Exception $e) {
            $ioObject->mkdir($destDirectory, 0777, true);
            $ioObject->open(array('path'=>$destDirectory));
        }

        if (strrpos($file, '.tmp') == strlen($file)-4) {
            $file = substr($file, 0, strlen($file)-4);
        }

		// DO NOT rename file if it exists. Overwrite it. Custumer request in ticket#2012120510000418 — Magento is losing connection to files 
		
        $destFile = $file;
        //$destFile = dirname($file) . $ioObject->dirsep()
        //          . Mage_Core_Model_File_Uploader::getNewFileName($this->getFilePath($basePath, $file));
		//die ("destFile: $destFile<br>file: $file<br>basePath: $basePath<br>dirname: ".dirname($file));

        Mage::helper('core/file_storage_database')->copyFile(
            $this->getFilePath($baseTmpPath, $file),
            $this->getFilePath($basePath, $destFile)
        );

        $result = $ioObject->mv(
            $this->getFilePath($baseTmpPath, $file),
            $this->getFilePath($basePath, $destFile)
        );
        return str_replace($ioObject->dirsep(), '/', $destFile);
    }
	
}
