<?php
/**
 * @copyright  Copyright (c) 2010 Amasty (http://www.amasty.com)
 */  
class Amasty_Xcoupon_Model_Generator
{
    protected $alphabet = array();
    protected $counts   = array();
    protected $pos      = array();
    
    public function __construct()
    {
        // no "0" and "1" as they are confusing
        $this->alphabet['D']  = array(2,3,4,5,6,7,8,9); 
        $this->counts['D']  = count($this->alphabet['D']);
        // no I, Q and O as they are confusing
        $this->alphabet['L']  = array('A','B','C','D','E','F','G','H','J','K','L','M','N','P','R','S','T','U','V','W','X'); 
        $this->counts['L'] = count($this->alphabet['L']);
        
        $this->pos['L'] =  $this->pos['D'] = 0;
    }  
    
    public function getCode($pattern)
    {
        $code = '';
        
        // we call rand() per one code, not per one letter
        // as it is a bit faster
        shuffle($this->alphabet['D']);
        shuffle($this->alphabet['L']);
        
        $pattern = str_split($pattern);
        foreach ($pattern as $i){
            if (empty($this->alphabet[$i])){
                $code .= $i;
            }
            else {
                $code .= $this->alphabet[$i][$this->pos[$i]];  
                $this->pos[$i] = ($this->pos[$i]+1) % $this->counts[$i];
                //$code .= $this->alphabet[$i][rand(0, $this->counts[$i]-1)]; 
            }
        }
        
        return $code;
    }
    
    public function validate($pattern)
    {
        if (false === strpos($pattern, 'L') && false === strpos($pattern, 'D')){
            $msg = Mage::helper('amxcoupon')->__('Please add L or D placeholders into the pattern "%s"', $pattern);
            throw new Exception($msg);
        }
        
        return true;       
    }
    
    public function getDescription()
    {
        return Mage::helper('amxcoupon')->__(
            '<b>L</b> - letter, <b>D</b> - digit<br/>e.g. PROMO_LLDDD results in PROMO_DF627'
        );
    }

}
