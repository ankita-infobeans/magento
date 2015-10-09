<?php

//die  ('Buka zluka');
	  
class ICC_ResetDownloads_Block_Adminhtml_Grid_Column_Renderer_Action 
	 extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract //Mage_Adminhtml_Block_Widget_Grid_Column //
{    


	public function render(Varien_Object $row)	{
		//die  ('Buka');
		//$col = $this->getColumn();
		//$grid=$col->_grid; 
		//$id=$this->_getValue($row);
		//->getGridUrl();
		$column =$this->getColumn();
		$action = $column->getActions();
		
		//$value = $row->getData($this->getColumn()->getIndex());
		//$field = '';//$this->getColumn()->getField();
		//$url = $this->getColumn()->getUrl();
		//$url = $action[url];
		
		$field = $action['index'];
		$id = $row->getData( $field );
	
	
		$gridJsObject = $action['gridId'].'JsObject';
		$onclickFunc= "onclick=\"";
			$onclickFunc.=$gridJsObject.'.reload(';
			// this.addVarToUrl(this.pageVar, pageNumber)
				$onclickFunc.=$gridJsObject.'.addVarToUrl(';
					$onclickFunc.= '\''. $field . '\'';
					$onclickFunc.= ',';
					$onclickFunc.= '\'' . $id . '\'';
				$onclickFunc.=')';
			$onclickFunc.=');return false;';
		$onclickFunc.= "\"";
		
		return "<a $onclickFunc>" . $action['caption'] . "</a>";
	}

	
	


}