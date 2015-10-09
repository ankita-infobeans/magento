<?php

class Gorilla_Greatplains_Adminhtml_ModelnameController extends Mage_Adminhtml_Controller_Action {
	protected function _initAction() {
		$this->loadLayout ()->_setActiveMenu ( "greatplains/modelname" )->_addBreadcrumb ( Mage::helper ( "adminhtml" )->__ ( "Modelname  Manager" ), Mage::helper ( "adminhtml" )->__ ( "Modelname Manager" ) );
		return $this;
	}
	public function indexAction() {
		$this->_initAction ();
		$this->renderLayout ();
	}
	public function editAction() {
		$brandsId = $this->getRequest ()->getParam ( "id" );
		$brandsModel = Mage::getModel ( "greatplains/modelname" )->load ( $brandsId );
		if ($brandsModel->getId () || $brandsId == 0) {
			Mage::register ( "greatplains_data", $brandsModel );
			$this->loadLayout ();
			$this->_setActiveMenu ( "greatplains/modelname" );
			$this->_addBreadcrumb ( Mage::helper ( "adminhtml" )->__ ( "Modelname Manager" ), Mage::helper ( "adminhtml" )->__ ( "Modelname Manager" ) );
			$this->_addBreadcrumb ( Mage::helper ( "adminhtml" )->__ ( "Modelname Description" ), Mage::helper ( "adminhtml" )->__ ( "Modelname Description" ) );
			$this->getLayout ()->getBlock ( "head" )->setCanLoadExtJs ( true );
			$this->_addContent ( $this->getLayout ()->createBlock ( "greatplains/adminhtml_modelname_edit" ) )->_addLeft ( $this->getLayout ()->createBlock ( "greatplains/adminhtml_modelname_edit_tabs" ) );
			$this->renderLayout ();
		} else {
			Mage::getSingleton ( "adminhtml/session" )->addError ( Mage::helper ( "greatplains" )->__ ( "Item does not exist." ) );
			$this->_redirect ( "*/*/" );
		}
	}
	
	public function newAction() {
		
		$id = $this->getRequest ()->getParam ( "id" );
		$model = Mage::getModel ( "greatplains/modelname" )->load ( $id );
		
		$data = Mage::getSingleton ( "adminhtml/session" )->getFormData ( true );
		if (! empty ( $data )) {
			$model->setData ( $data );
		}
		
		Mage::register ( "greatplains_data", $model );
		
		$this->loadLayout ();
		$this->_setActiveMenu ( "greatplains/modelname" );
		
		$this->getLayout ()->getBlock ( "head" )->setCanLoadExtJs ( true );
		
		$this->_addBreadcrumb ( Mage::helper ( "adminhtml" )->__ ( "Modelname Manager" ), Mage::helper ( "adminhtml" )->__ ( "Modelname Manager" ) );
		$this->_addBreadcrumb ( Mage::helper ( "adminhtml" )->__ ( "Modelname Description" ), Mage::helper ( "adminhtml" )->__ ( "Modelname Description" ) );
		
		$this->_addContent ( $this->getLayout ()->createBlock ( "greatplains/adminhtml_modelname_edit" ) )->_addLeft ( $this->getLayout ()->createBlock ( "greatplains/adminhtml_modelname_edit_tabs" ) );
		
		$this->renderLayout ();
		
		// $this->_forward("edit");
	}
	public function saveAction() {
		
		$post_data = $this->getRequest ()->getPost ();
		
		if ($post_data) {
			
			try {
				
				$brandsModel = Mage::getModel ( "greatplains/modelname" )->addData ( $post_data )->setId ( $this->getRequest ()->getParam ( "id" ) )->save ();
				
				Mage::getSingleton ( "adminhtml/session" )->addSuccess ( Mage::helper ( "adminhtml" )->__ ( "Modelname was successfully saved" ) );
				Mage::getSingleton ( "adminhtml/session" )->setModelnameData ( false );
				
				if ($this->getRequest ()->getParam ( "back" )) {
					$this->_redirect ( "*/*/edit", array ("id" => $brandsModel->getId () ) );
					return;
				}
				$this->_redirect ( "*/*/" );
				return;
			} catch ( Exception $e ) {
				Mage::getSingleton ( "adminhtml/session" )->addError ( $e->getMessage () );
				Mage::getSingleton ( "adminhtml/session" )->setModelnameData ( $this->getRequest ()->getPost () );
				$this->_redirect ( "*/*/edit", array ("id" => $this->getRequest ()->getParam ( "id" ) ) );
				return;
			}
		
		}
		$this->_redirect ( "*/*/" );
	}
	
	public function deleteAction() {
		if ($this->getRequest ()->getParam ( "id" ) > 0) {
			try {
				$brandsModel = Mage::getModel ( "greatplains/modelname" );
				$brandsModel->setId ( $this->getRequest ()->getParam ( "id" ) )->delete ();
				Mage::getSingleton ( "adminhtml/session" )->addSuccess ( Mage::helper ( "adminhtml" )->__ ( "Item was successfully deleted" ) );
				$this->_redirect ( "*/*/" );
			} catch ( Exception $e ) {
				Mage::getSingleton ( "adminhtml/session" )->addError ( $e->getMessage () );
				$this->_redirect ( "*/*/edit", array ("id" => $this->getRequest ()->getParam ( "id" ) ) );
			}
		}
		$this->_redirect ( "*/*/" );
	}
}
