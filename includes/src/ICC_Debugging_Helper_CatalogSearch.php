<?php
/**
 * Created by Ariel Allon @ Gorilla
 *    aallon@gorillagroup.com
 * Creation date: 9/21/12 11:59 AM
 */

class ICC_Debugging_Helper_CatalogSearch extends Mage_CatalogSearch_Helper_Data
{
    const XML_PATH_LOG_SEARCHES         = 'dev/log/log_searches';
    const XML_PATH_SEARCH_LOG_FILENAME  = 'dev/log/search_log_file_name';

    const PADDING                       = '                                     ';

    private $_logged = false;

    /**
     * Add option of logging search queries.
     *
     * @return Mage_CatalogSearch_Model_Query|void
     */
    public function getQuery()
    {
        $query = parent::getQuery();


        $logSearches = Mage::getStoreConfig(self::XML_PATH_LOG_SEARCHES);
        if ($logSearches && !$this->_logged) {

            $numResults = $query->getNumResults();
            if (!empty($numResults) || $numResults === 0 || $numResults === "0") {

                $queryData = 'Query: "' . $query->getQueryText() . '" ';
                $queryData .= "\n" . self::PADDING . '# Results: ' . $numResults . ' ';
                $queryData .= "\n" . self::PADDING . '[Last updated: ' . $query->getUpdatedAt() . ']';

                $file = Mage::getStoreConfig(self::XML_PATH_SEARCH_LOG_FILENAME);

                Mage::log($queryData, null, $file);
                $this->_logged = true;
            }
        }

        return $query;
    }
}
