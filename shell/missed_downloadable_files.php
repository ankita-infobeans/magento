<?php 

//Script is checking  links for downloadable products from table 'downloadable_link'
// in the directory /var/www/html/media/downloadable/files/links
// and save all missing links to file /log/missed_downloadable_products.csv

require_once 'abstract.php';

class Mage_Shell_Missed extends Mage_Shell_Abstract
{

	private $_pathToFiles = '/var/www/html/media/downloadable/files/links';
	
        private function _getConnection($type = 'core_read'){
            return Mage::getSingleton('core/resource')->getConnection($type);
        }
        
        private function _getTableName($tableName){
            return Mage::getSingleton('core/resource')->getTableName($tableName);
        }
        
        private function _getLinksArray() {            
            return $this->_getConnection('core_read')->fetchAll(
                "SELECT product_id,link_file FROM " . $this->_getTableName('downloadable_link') . " order by product_id asc;"
            );
        }
        
        private function _getFullPathToFile($link) {
            return $this->_pathToFiles . ($link['0'] == '/' ? '' : '/') . $link;
        }
        
	public function run() {
            echo "Start checking....".PHP_EOL;
            $rows = $this->_getLinksArray();
            if(!empty($rows)){                
                $fp = fopen(Mage::getBaseDir('log').'/missed_downloadable_products.csv', 'w');
                fputcsv($fp, array('Product id', 'Link to file', 'Full path'));
                foreach($rows as $row) {
                    $filePath=$this->_getFullPathToFile($row['link_file']);
                    if (!(file_exists($filePath))) {   
                        fputcsv($fp, array($row['product_id'] , $row['link_file'], $filePath));
                        Mage::log($row['product_id'] . ' => ' . $row['link_file'], null, 'missed_files.log');
                    }            
                }
                fclose($fp);
            }
            echo "Checking was done!".  PHP_EOL;
	}

}

$missedFiles = new Mage_Shell_Missed();
$missedFiles->run();


