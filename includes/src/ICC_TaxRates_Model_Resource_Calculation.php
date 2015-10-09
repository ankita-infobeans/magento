<?php
class ICC_TaxRates_Model_Resource_Calculation extends Mage_Tax_Model_Resource_Calculation
{
    /**
     * Rates cache
     *
     * @var unknown
     */
    protected $_ratesCache              = array();

    /**
     * Primery key auto increment flag
     *
     * @var bool
     */
    protected $_isPkAutoIncrement    = false;

    /**
     * Resource initialization
     *
     */
    protected function _construct()
    {
        $this->_setMainTable('tax/tax_calculation');
    }

    /**
     * Return combined percent value
     *
     * @param float|int $percent
     * @param float|int $rate
     * @return int
     */
    protected function _collectPercent($percent, $rate)
    {
        return (100 + $percent) * ($rate / 100);
    }

    /**
     * Create search templates for postcode
     *
     * @param string $postcode
     * @return array  $strArr
     */
    protected function _createSearchPostCodeTemplates($postcode)
    {
        $len = Mage::helper('tax')->getPostCodeSubStringLength();
        $strlen = strlen($postcode);
        if ($strlen > $len) {
            $postcode = substr($postcode, 0, $len);
            $strlen = $len;
        }

        $strArr = array((string)$postcode, $postcode . '*');
        if ($strlen > 1) {
            for ($i = 1; $i < $strlen; $i++) {
                $strArr[] = sprintf('%s*', substr($postcode, 0, - $i));
            }
        }

        return $strArr;
    }

    /**
     * Returns tax rates for request - either pereforms SELECT from DB, or returns already cached result
     * Notice that productClassId due to optimization can be array of ids
     *
     * @param Varien_Object $request
     * @return array
     */
    protected function _getRates($request)
    {
        // Extract params that influence our SELECT statement and use them to create cache key
        $storeId = Mage::app()->getStore($request->getStore())->getId();
        $customerClassId = $request->getCustomerClassId();
        $countryId = $request->getCountryId();
        $regionId = $request->getRegionId();
        //$postcode = $request->getPostcode();
        //for 5+4 zip code tax issue
        $postcode = substr($request->getPostcode(),0,5);

        // Process productClassId as it can be array or usual value. Form best key for cache.
        $productClassId = $request->getProductClassId();
        $ids = is_array($productClassId) ? $productClassId : array($productClassId);
        foreach ($ids as $key => $val) {
            $ids[$key] = (int) $val; // Make it integer for equal cache keys even in case of null/false/0 values
        }
        $ids = array_unique($ids);
        sort($ids);
        $productClassKey = implode(',', $ids);

        // Form cache key and either get data from cache or from DB
        $cacheKey = implode('|', array($storeId, $customerClassId, $productClassKey, $countryId, $regionId, $postcode));

        if (!isset($this->_ratesCache[$cacheKey])) {
            // Make SELECT and get data
            $select = $this->_getReadAdapter()->select();
            $select
                ->from(array('main_table' => $this->getMainTable()),
                array('tax_calculation_rate_id',
                      'tax_calculation_rule_id',
                      'customer_tax_class_id',
                      'product_tax_class_id'
                    )
                )
                ->where('customer_tax_class_id = ?', (int)$customerClassId);
            if ($productClassId) {
                $select->where('product_tax_class_id IN (?)', $productClassId);
            }
            $ifnullTitleValue = $this->_getReadAdapter()->getCheckSql(
                'title_table.value IS NULL',
                'rate.code',
                'title_table.value'
            );
            $ruleTableAliasName = $this->_getReadAdapter()->quoteIdentifier('rule.tax_calculation_rule_id');
            $select
                ->join(
                    array('rule' => $this->getTable('tax/tax_calculation_rule')),
                    $ruleTableAliasName . ' = main_table.tax_calculation_rule_id',
                    array('rule.priority', 'rule.position', 'rule.calculate_subtotal'))
                ->join(
                    array('rate' => $this->getTable('tax/tax_calculation_rate')),
                    'rate.tax_calculation_rate_id = main_table.tax_calculation_rate_id',
                    array(
                        'value' => 'rate.rate',
                        'rate.tax_country_id',
                        'rate.tax_region_id',
                        'rate.tax_postcode',
                        'rate.tax_calculation_rate_id',
                        'rate.code'
                ))
                ->joinLeft(
                    array('title_table' => $this->getTable('tax/tax_calculation_rate_title')),
                   "rate.tax_calculation_rate_id = title_table.tax_calculation_rate_id "
                   . "AND title_table.store_id = '{$storeId}'",
                    array('title' => $ifnullTitleValue))
                ->where('rate.tax_country_id = ?', $countryId)
                ->where("rate.tax_region_id IN(?)", array(0, (int)$regionId));
            $postcodeIsNumeric = is_numeric($postcode);
            $postcodeIsRange = is_string($postcode) && preg_match('/^(.+)-(.+)$/', $postcode, $matches);
            if ($postcodeIsRange) {
                $zipFrom = $matches[1];
                $zipTo = $matches[2];
            }

            if ($postcodeIsNumeric || $postcodeIsRange) {
                $selectClone = clone $select;
                $selectClone->where('rate.zip_is_range IS NOT NULL');
            }
            $select->where('rate.zip_is_range IS NULL');

            if ($postcode != '*' || $postcodeIsRange) {
                $select
                    ->where("rate.tax_postcode IS NULL OR rate.tax_postcode IN('*', '', ?)",
                        $postcodeIsRange ? $postcode : $this->_createSearchPostCodeTemplates($postcode));
                if ($postcodeIsNumeric) {
                    $selectClone
                        ->where('? BETWEEN rate.zip_from AND rate.zip_to', $postcode);
                } else if ($postcodeIsRange) {
                    $selectClone->where('rate.zip_from >= ?', $zipFrom)
                        ->where('rate.zip_to <= ?', $zipTo);
                }
            }

            /**
             * @see ZF-7592 issue http://framework.zend.com/issues/browse/ZF-7592
             */
            if ($postcodeIsNumeric || $postcodeIsRange) {
                $select = $this->_getReadAdapter()->select()->union(
                    array(
                        '(' . $select . ')',
                        '(' . $selectClone . ')'
                    )
                );
            }

            $select->order('priority ' . Varien_Db_Select::SQL_ASC)
                   ->order('tax_calculation_rule_id ' . Varien_Db_Select::SQL_ASC)
                   ->order('tax_country_id ' . Varien_Db_Select::SQL_DESC)
                   ->order('tax_region_id ' . Varien_Db_Select::SQL_DESC)
                   ->order('tax_postcode ' . Varien_Db_Select::SQL_DESC)
                   ->order('value ' . Varien_Db_Select::SQL_DESC);

            $this->_ratesCache[$cacheKey] = $this->_getReadAdapter()->fetchAll($select);
        }

        return $this->_ratesCache[$cacheKey];
    }

    /**
     * Get rate ids applicable for some address
     *
     * @param Varien_Object $request
     * @return array
     */
    function getApplicableRateIds($request)
    {
        $countryId = $request->getCountryId();
        $regionId = $request->getRegionId();
        //$postcode = $request->getPostcode();
        //for 5+4 zip code tax issue
        $postcode = substr($request->getPostcode(),0,5);


        $select = $this->_getReadAdapter()->select()
            ->from(array('rate' => $this->getTable('tax/tax_calculation_rate')), array('tax_calculation_rate_id'))
            ->where('rate.tax_country_id = ?', $countryId)
            ->where("rate.tax_region_id IN(?)", array(0, (int)$regionId));

        $expr = $this->_getWriteAdapter()->getCheckSql(
            'zip_is_range is NULL',
            $this->_getWriteAdapter()->quoteInto(
                "rate.tax_postcode IS NULL OR rate.tax_postcode IN('*', '', ?)",
                $this->_createSearchPostCodeTemplates($postcode)
            ),
            $this->_getWriteAdapter()->quoteInto('? BETWEEN rate.zip_from AND rate.zip_to', $postcode)
        );
        $select->where($expr);
        $select->order('tax_calculation_rate_id');
        return $this->_getReadAdapter()->fetchCol($select);
    }

    /**
     * Calculate rate
     *
     * @param array $rates
     * @return int
     */
    protected function _calculateRate($rates)
    {
        $result      = 0;
        $currentRate = 0;
        $countedRates = count($rates);
        for ($i = 0; $i < $countedRates; $i++) {
            $rate       = $rates[$i];
            $rule       = $rate['tax_calculation_rule_id'];
            $value      = $rate['value'];
            $priority   = $rate['priority'];

            while (isset($rates[$i + 1]) && $rates[$i + 1]['tax_calculation_rule_id'] == $rule) {
                $i++;
            }

            $currentRate += $value;

            if (!isset($rates[$i + 1]) || $rates[$i + 1]['priority'] != $priority) {
                if (!empty($rates[$i]['calculate_subtotal'])) {
                    $result += $currentRate;
                } else {
                    $result += $this->_collectPercent($result, $currentRate);
                }
                $currentRate = 0;
            }
        }

        return $result;
    }

}
