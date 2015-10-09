<?php
/**
 * Free Resource sub category resource collection model
 *
 * @category    ICC
 * @package     ICC_Freeresources
 */
class ICC_Freeresources_Model_Resource_Freeresource_Subcategory_Freeresource_Collection
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
        $this->_init('icc_freeresources/freeresource_subcategory');
        $this->_setIdFieldName('subcategory_id');
    }
    /**
     * init select
     * @access protected
     * @return ICC_Freeresources_Model_Resource_Freeresource_Subcategory_Freeresource_Collection
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
     * @return ICC_Freeresources_Model_Resource_Freeresource_Subcategory_Freeresource_Collection
     */
    public function addEntityFilter($entityId) {
        $this->getSelect()->where('ct.freeresource_id = ?', $entityId);
        return $this;
    }

    /**
     * Add status filter
     * @access public
     * @param mixed $status
     * @return ICC_Freeresources_Model_Resource_Freeresource_Subcategory_Freeresource_Collection
     */
    public function addStatusFilter($status = 1) {
        $this->getSelect()->where('ct.status = ?', $status);
        return $this;
    }

    /**
     * Set date order
     * @access public
     * @param string $dir
     * @return ICC_Freeresources_Model_Resource_Freeresource_Subcategory_Freeresource_Collection
     */
    public function setDateOrder($dir = 'DESC') {
        $this->setOrder('ct.created_at', $dir);
        return $this;
    }

    /**
     * join fields to entity
     * @accessprotected
     * @return ICC_Freeresources_Model_Resource_Freeresource_Subcategory_Freeresource_Collection
     */
    protected function _joinFields() {
        $categoryTable = Mage::getSingleton('core/resource')->getTableName('icc_freeresources/freeresource_category');
        $mainTable = Mage::getSingleton('core/resource')->getTableName('icc_freeresources/freeresource');
        $this->getSelect()
            ->join(array('fr' => $mainTable),
                'fr.entity_id = main_table.freeresource_id',
                array(
                    'fr_title'=>'fr.title',
                )
            )->join(array('ct' => $categoryTable),
                'ct.category_id = main_table.category_id',
                array(
                    'ct_title'=>'title',
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
        $idsSelect->columns('main_table.subcategory_id');
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
            ->columns('COUNT(main_table.subcategory_id)')
            ->reset(Zend_Db_Select::HAVING);

        return $select;
    }
    /**
     * Add attribute to filter
     * @access public
     * @param Mage_Eav_Model_Entity_Attribute_Abstract|string $attribute
     * @param array $condition
     * @param string $joinType
     * @return ICC_Freeresources_Model_Resource_Freeresource_Subcategory_Freeresource_Collection
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