<?php
abstract class Gorilla_StampPDF_Model_Stamp_Message_Abstract implements Gorilla_StampPDF_Model_Stamp_Message
{

    /**
     * Name of Stamp (optional)
     *
     * @var string
     */
    protected $_name;

    /**
     * Permits later removal of stamp (optional)
     *
     * @var string
     */
    protected $_undoLabel;

    /**
     * Start page for stamp (optional)
     *
     * @var int
     */
    protected $_startPage;

    /**
     * End page for stamp (optional)
     *
     * @var int
     */
    protected $_endPage;

    /**
     * How many pages to skip between stamps (optional)
     *
     * @var int
     */
    protected $_pageIncrement;

    /**
     * Which pages to stamp (optional)
     *
     * @var string
     */
    protected $_pageRange;

    /**
     * Position and orientation on page (optional)
     *
     * @var string
     */
    protected $_position;

    /**
     * Left, right or center (optional)
     *
     * @var string
     */
    protected $_justification;

    /**
     * @var boolean
     */
    protected $_underlay      = false;

    /**
     * Set page boundaries used to position stamp (optional)
     *
     * @var string
     */
    protected $_pageBox;

    /**
     * Horizontal placement offset
     *
     * @var int
     */
    protected $_offsetX;

    /**
     * Vertical placement offset
     *
     * @var int
     */
    protected $_offsetY;

    /**
     * @var int
     */
    protected $_left;

    /**
     * @var int
     */
    protected $_right;

    /**
     * @var int
     */
    protected $_top;

    /**
     * @var int
     */
    protected $_bottom;

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->setOptions($options);
    }

    /**
     * @param array $options
     * @return Gorilla_StampPDF_Model_Stamp_Message_Abstract
     */
    public function setOptions(array $options)
    {
        foreach($options as $option => $value){
            $method = $this->methodize($option);
            if(method_exists($this,$method)){
                $this->$method($value);
            }
        }
        return $this;
    }

    /**
     * Render the Message Block
     *
     * @return string
     */
    public function render()
    {
        $options = array(
            'Type'          => $this->getType(),
            'Underlay'      => $this->_underlay ? "Yes" : 'No',
        );

        if($this->_name){
            $option['Name'] = $this->_name;
        }

        if($this->_undoLabel){
            $options['UndoLabel'] = $this->_undoLabel;
        }

        if($this->_left !== null){
            $options['Left'] = $this->_left;
        }

        if($this->_right !== null){
            $options['Right'] = $this->_right;
        }

        if($this->_top !== null){
            $options['Top'] = $this->_top;
        }

        if($this->_bottom !== null){
            $options['Bottom'] = $this->_bottom;
        }

        if($this->_offsetX !== null){
            $options['OffsetX'] = $this->_offsetX;
        }

        if($this->_offsetY !== null){
            $options['OffsetY'] = $this->_offsetY;
        }

        if($this->_startPage){
            $options['StartPage'] = $this->_startPage;
        }

        if($this->_endPage){
            $options['EndPage'] = $this->_endPage;
        }

        if($this->_pageIncrement){
            $options['PageIncrement'] = $this->_pageIncrement;
        }

        // custom by Ticket#2013041910000307
        if($this->_pageRange){
            $options['PageRange'] = $this->_pageRange;
        }

        if($this->_position){
            $options['Position'] = $this->_position;
        }

        if($this->_justification){
            $options['Justification'] = $this->_justification;
        }

        if($this->_pageBox){
            $options['PageBox'] = $this->_pageBox;
        }

        $options = array_merge($options,$this->_getOptions());

        $messageBlock = 'Begin_Message' . PHP_EOL . PHP_EOL;

        foreach($options as $option => $value){
            $messageBlock .= sprintf('%s (%s)',$option, $value) . PHP_EOL;
        }

        $messageBlock .= PHP_EOL . 'End_Message' . PHP_EOL;

        return $messageBlock;
    }

    /**
     * Get options specific to child message type
     *
     * @return array
     */
    abstract protected function _getOptions();

    /**
     * Get stamp message type
     *
     * @return string
     */
    abstract function getType();

    public function __toString()
    {
        return $this->render();
    }

    /**
     * @param int $page
     * @return Gorilla_StampPDF_Model_Stamp_Message_Abstract
     */
    public function setStartPage($page)
    {
        $this->_startPage = (int)$page;
        return $this;
    }

    /**
     * @param int $page
     * @return Gorilla_StampPDF_Model_Stamp_Message_Abstract
     */
    public function setEndPage($page)
    {
        $this->_endPage = (int)$page;
        return $this;
    }

    /**
     * Top, etc
     *
     * @param string $position
     * @return Gorilla_StampPDF_Model_Stamp_Message_Abstract
     */
    public function setPosition($position)
    {
        $valid = array('Diag-BottomLeft','Diag-TopLeft','Top','Bottom','VCenter','Angle');
        if(!in_array($position,$valid)){
            throw new InvalidArgumentException(sprintf('%s is not a valid Position value. Valid values are %s',
                $position,implode(', ',$valid)));
        }
        $this->_position = $position;
        return $this;
    }

    /**
     * Left, Right, or Center
     *
     * @param string $justification
     * @return Gorilla_StampPDF_Model_Stamp_Message_Abstract
     */
    public function setJustification($justification)
    {
        $valid = array('Left','Right','Center');
        if(!in_array($justification,$valid)){
            throw new InvalidArgumentException(sprintf('%s is not a valid Justification value. Valid values are %s',
                $justification,implode(', ',$valid)));
        }
        $this->_justification = $justification;
        return $this;
    }

    /**
     * @param int $increment
     * @return Gorilla_StampPDF_Model_Stamp_Message_Abstract
     */
    public function setPageIncrement($increment)
    {
        $this->_pageIncrement = (int)$increment;
        return $this;
    }

    // custom by Ticket#2013041910000307
    public function setPageRange($range)
    {
        $this->_pageRange = $range;
        return $this;
    }

    /**
     * @param boolean $flag
     * @return Gorilla_StampPDF_Model_Stamp_Message_Abstract
     */
    public function setUnderlay($flag)
    {
        $this->_underlay = (boolean)$flag;
        return $this;
    }

    /**
     * @param int $left
     * @return Gorilla_StampPDF_Model_Stamp_Message_Abstract
     */
    public function setLeft($left)
    {
        $this->_left = (int)$left;
        return $this;
    }

    /**
     * @param int $right
     * @return Gorilla_StampPDF_Model_Stamp_Message_Abstract
     */
    public function setRight($right)
    {
        $this->_right = (int)$right;
        return $this;
    }

    /**
     * @param int $top
     * @return Gorilla_StampPDF_Model_Stamp_Message_Abstract
     */
    public function setTop($top)
    {
        $this->_top = (int)$top;
        return $this;
    }

    /**
     * @param int $bottom
     * @return Gorilla_StampPDF_Model_Stamp_Message_Abstract
     */
    public function setBottom($bottom)
    {
        $this->_bottom = (int)$bottom;
        return $this;
    }

    /**
     * @param int $offset
     * @return Gorilla_StampPDF_Model_Stamp_Message_Abstract
     */
    public function setOffsetX($offset)
    {
        $this->_offsetX = (int)$offset;
        return $this;
    }

    /**
     * @param int $offset
     * @return Gorilla_StampPDF_Model_Stamp_Message_Abstract
     */
    public function setOffsetY($offset)
    {
        $this->_offsetY = (int)$offset;
        return $this;
    }

    /**
     * @param string $name
     * @return Gorilla_StampPDF_Model_Stamp_Message_Abstract
     */
    public function setName($name)
    {
        $this->_name = $name;
        return $this;
    }

    /**
     * @param string $pageBox
     * @return Gorilla_StampPDF_Model_Stamp_Message_Abstract
     * @throws InvalidArgumentException
     */
    public function setPageBox($pageBox)
    {
        $valid = array('mediabox','cropbox','artbox','trimbox','bleedbox');
        if(!in_array($pageBox,$valid)){
            throw new InvalidArgumentException(sprintf('%s is not a valid PageBox value. Valid values are %s',
                $pageBox,implode(', ',$valid)));
        }
        $this->_pageBox = $pageBox;
        return $this;
    }

    public function methodize($value)
    {
        return 'set'.str_replace(' ','',ucwords(str_replace('_',' ',$value)));
    }
}