<?php
/**
 * Free Resource category resource collection model
 *
 * @category    ICC
 * @package     ICC_Freeresources
 */
class ICC_Freeresources_Model_Resource_Freeresource_Category_Freeresource_Collection
    extends ICC_Freeresources_Model_Resource_Freeresource_Collection {
    /**
     * Entities alias
     *
     * @var array
     */
    protected $_entitiesAlias        = array();
    /**
     * construct
     * @access protected
     */
    protected function _construct() {
        $this->_init('icc_freeresources/freeresource');
        $this->_setIdFieldName('category_id');
    }
    /**
     * init select
     * @access protected
     * @return ICC_Freeresources_Model_Resource_Freeresource_Category_Freeresource_Collection
     */
    protected function _initSelect() {
        parent::_initSelect();
        $this->_joinFields();
        return $this;
    }

    /**
     * Add entity filter
     * @access public
     * @param int $entityId
     * @return ICC_Freeresources_Model_Resource_Freeresource_Category_Freeresource_Collection
     */
    public function addEntityFilter($entityId) {
        $this->getSelect()->where('ct.freeresource_id = ?', $entityId);
        return $this;
    }

    /**
     * join fields to entity
     * @accessprotected
     * @return ICC_Freeresources_Model_Resource_Freeresource_Category_Freeresource_Collection
     */
    protected function _joinFields() {
        $categoryTable = Mage::getSingleton('core/resource')->getTableName('icc_freeresources/freeresource_category');
        $this->getSelect()
            ->join(array('ct' => $categoryTable),
                'ct.freeresource_id = main_table.entity_id',
                array(
                    'main_table_title'=>'main_table.title',
                    'ct_title'=>'title',
                    'ct_category_id'=>'category_id',
                )
            );
        return $this;
    }

    /**
     * Retrive all ids for collection
     * @access public
     * @param mixed $limit
     * @param mixed $offset
     * @return array
     */
    public function getAllIds($limit = null, $offset = null) {
        $idsSelect = clone $this->getSelect();
        $idsSelect->reset(Zend_Db_Select::ORDER);
        $idsSelect->reset(Zend_Db_Select::LIMIT_COUNT);
        $idsSelect->reset(Zend_Db_Select::LIMIT_OFFSET);
        $idsSelect->reset(Zend_Db_Select::COLUMNS);
        $idsSelect->columns('ct.category_id');
        return $this->getConnection()->fetchCol($idsSelect);
    }
    /**
     * Retrieves column values
     * @access public
     * @param string $colName
     * @return array
     */
    public function getColumnValues($colName) {
        $col = array();
        foreach ($this->getItems() as $item) {
            $col[] = $item->getData($colName);
        }
        return $col;
    }
    /**
     * Render SQL for retrieve product count
     * @access public
     * @return string
     */
    public function getSelectCountSql() {
        $select = parent::getSelectCountSql();
        $select->reset(Zend_Db_Select::COLUMNS)
            ->columns('COUNT(main_table.entity_id)')
            ->reset(Zend_Db_Select::HAVING);

        return $select;
    }
    /**
     * Add attribute to filter
     * @access public
     * @param Mage_Eav_Model_Entity_Attribute_Abstract|string $attribute
     * @param array $condition
     * @param string $joinType
     * @return ICC_Freeresources_Model_Resource_Freeresource_Category_Freeresource_Collection
     */
    public function addFieldToFilter($attribute, $condition = null, $joinType = 'inner') {
        switch($attribute) {
            case 'ct.category_id':
            case 'ct.title':
                $conditionSql = $this->_getConditionSql($attribute, $condition);
                $this->getSelect()->where($conditionSql);
                break;

            default:
                parent::addFieldToFilter($attribute, $condition, $joinType);
                break;
        }
        return $this;
    }
}