<?php

class ICC_Ecodes_Adminhtml_ManagepremiumController extends Mage_Adminhtml_Controller_Action
{
	private function errorRedirectNow($error_message = 'There was an error managing this user.', $url = null)
	{
		if(is_null($url))
		{
			$url = $this->getUrl('*/*/edit', array('id'=> $this->getRequest()->getParam('id'), 'subscription_id' => $this->getRequest()->getParam('subscription_id')));
		}
		$this->_getSession()->addError($error_message);
		header("Location: $url");
		exit; // The follwoing is not redirecting immediately: $this->_redirect('*/*/edit', array('id'=> $id));
	}

	private function successRedirectNow($success_message = 'Successfully saved user.', $url = null)
	{
		if(is_null($url))
		{
			$url = $this->getUrl('*/*/edit', array('id'=> $this->getRequest()->getParam('id'), 'subscription_id' => $this->getRequest()->getParam('subscription_id')));
		}
		$this->_getSession()->addSuccess($success_message);
		header("Location: $url");
		exit; // The follwoing is not redirecting immediately: $this->_redirect('*/*/edit', array('id'=> $id));
	}

	public function gridAction()
	{
		$this->loadLayout();
		$this->renderLayout();
	}

	public function indexAction ()
	{
		$this->_redirect('*/*/list');
	}

	public function listAction ()
	{
		//die('here list');
		//housekeeping
		$this->_getSession()->setFormData(array());
		$id = (int) $this->getRequest()->getParam('id');

		$this->_title($this->__('Manage PremiumACCESS Subscriptions'));
		$this->loadLayout();
		$this->_setActiveMenu('customer');

		$this->renderLayout();
	}

	public function newAction()
	{
		$this->_forward('edit');
	}

	public function editAction()
	{
		if( ! $this->getRequest()->has('subscription_id'))
		{
			$this->errorRedirectNow('Could not find the subscription identifier.', $this->getUrl('*/customer'));
		}
		$model = Mage::getModel('ecodes/premiumusers');
		if ($id = (int) $this->getRequest()->getParam('id'))
		{
			$model->load($id);
		}

		Mage::register('current_premiumusers', $model);

		$this->_title('Edit PremiumACCESS Subscription');
		$this->loadLayout();
		$this->_setActiveMenu('customer/ecodes');
		$this->renderLayout();
	}

	public function saveAction ()
	{
		if($data = $this->getRequest()->getPost())
		{
			$subid = $this->getRequest()->getParam('subscription_id');

			//echo $subid;
			//die;
			//$this->_getSession()->setFormData($data);
			$helper = Mage::helper('ecodes');
			$this->_getSession()->setFormData($data);
			$model = Mage::getModel('ecodes/premiumusers');
			$id = (int) $this->getRequest()->getParam('id');
			// check to see if this user exits already and is just not associated yet
			$login = "";
			if( ! ($id > 0) && isset($data['user'])) // this is the case where we are adding a NEW user so we need to check if they already exist and if they have already been added
			{
				$login = $data['user'];
				$pu_test = $model->getCollection()->addFieldToFilter('user', $data['user'] );
				if($pu_test->count())
				{
					$id = (int) $pu_test->getFirstItem()->getId();
					$sub_id = $this->getRequest()->getParam('subscription_id');
					$prem_sub_user = Mage::getModel('ecodes/premiumsubusers');
					$prem_sub_user_test = $prem_sub_user->getCollection()
					->addFieldToFilter('subs_id', $sub_id)
					->addFieldToFilter('user_id', $id);
					if($prem_sub_user_test->count())
					{
						$this->errorRedirectNow('The user with username ' . $data['user'] . ' has already been added to this subscription.', $this->getUrl('*/*/list/id/' . $sub_id));
					}
					else
					{
						$prem_sub_user->setSubsId($sub_id);
						$prem_sub_user->setUserId($id);
						$prem_sub_user->save();
						$this->successRedirectNow('Successfully added the user to your subscription list.', $this->getUrl('*/*/list/id/' . $sub_id) );
					}
				}
			}

			$session = $this->_getSession();
			$is_new = ! ($id > 0);
			try {
				if($id) {
					$model->load($id);
					if( ! isset($data['user']))
					{
						$data['user'] = $model->getUser();
					}
					$model_data = $model->getOrigData();
					$differences = array_diff($data, $model_data);
					unset($differences['form_key'], $differences['new_pass'], $differences['confirm_new_pass']);
					if( empty( $differences ) && empty($data['new_pass']) )
					{
						$this->errorRedirectNow('There were no changes to save.');
					}
				}

				if( ! empty($data['new_pass']) )
				{
					$data['new_pass'] = trim($data['new_pass']);
					$data['confirm_new_pass'] = trim($data['confirm_new_pass']);

					if($data['new_pass'] != $data['confirm_new_pass'])
					{
						$this->errorRedirectNow('Your new password and confirmation password did not match.');
					}

					$validation_error = $helper->validatePassword($data['new_pass'], $data['user'], $data['firstname'], $data['lastname']);
					if($validation_error )
					{
						$this->errorRedirectNow('Validation error: '. $validation_error);
					}

					$enc_pass = $helper->encryptPassword($data['new_pass']);
					$data['pass'] = $enc_pass;
				}
				if( $is_new )
				{  // we want to check this first to ensure username. the "edits" can just go into the queue
					$this->__addNewIccUser($data);
				}

				$model->addData($data);
				$model->save();
				$subscription = Mage::getModel('ecodes/premiumsubs')->load( $this->getRequest()->getParam('subscription_id') );
					
				if( ! is_object($subscription) || ! $subscription->getId())
				{
					$this->errorRedirectNow('Could not load subscription. id: ' . $this->getRequest()->getParam('subscription_id'), $this->getUrl('*/customer'));
				}

				$customer = Mage::getModel('customer/customer')->load($subscription->getCustomerId());
				//Mage::log($customer->debug(), null, 'managepremium-controller-save.log');
				if( ! is_object($customer) || ! $customer->getId())
				{
					$this->errorRedirectNow('Could not load customer.', $this->getUrl('*/customer'));
				}
				if( $is_new )
				{
					// make joining table rows
					$premium_sub_user = Mage::getModel('ecodes/premiumsubusers');
					$premium_sub_user->setSubsId($subscription->getId());
					$premium_sub_user->setUserId($model->getId());
					$premium_sub_user->save();

				}
				else
				{
					// this after the magento because we want to save successfully before risking adding bad info to the queue
					$icc_connect = Mage::getModel('ecodes/api'); // need this a little later
					$icc_update_result = $icc_connect->updateUser(
							$login,
							$helper->decryptPassword($customer->getEcodesMasterPass()),
							$model->getUser(),
							$helper->decryptPassword($model->getPass()),
							$data['firstname'],
							$data['lastname'],
							$data['email']
					);
				}


				if($login != "") /* new customer so need to add user product */

				{
				//	echo "login : $login";
					$result = Mage::getModel('ecodes/api')->appendUserProduct(
							$model->getUser(), $subscription->getSku(),
							$subscription->getExpiration());
						
						
						
					$user = Mage::getModel('ecodes/premiumusers')->getCollection()->getByUsername($login);

					
					//print_r($user);
					
					if (isset($result['success']) && $result['success']) {
						$subUser = Mage::getModel('ecodes/premiumsubusers');
						$subUser->setSubsId($subid);
						$subUser->setUserId( $user->getId());
						$subUser->setCreatedAt(date('m/d/y h:i:s', time()));
						$subUser->save();
						$session->addSuccess('The user has been added successfully');
					} else {
						$error = $result['message'];
						$this->errorRedirectNow('Error tying product to user.'.$error, $this->getUrl('*/customer'));
					}
				}


				$this->_getSession()->addSuccess(
						$this->__('PremiumACCESS Subscription user was successfully saved.')
				);
				$this->_getSession()->setFormData(false);

				$params = array('id' => $this->getRequest()->getParam('subscription_id') ); //, 'subscription_id' => $this->getRequest()->getParam('subscription_id') );
				$this->_redirect( '*/*/list', $params);

			}
			catch(Exception $e)
			{
				$session->addError($e->getMessage());
				if(isset($model) && $model->getId()) {
					$this->_redirect('*/*/edit', array(
							'id' => $model->getId()
					));
				} else {
					$this->_redirect('*/customer');
				}
			}
			return;
		} // end if the request object has data to return to us

		// if there is no data
		$this->_getSession()->addError($this->__('No data found to save'));
		$this->_redirect('* /customer'); // forward to the controller index action
		/* */
	}


	private function __addNewIccUser($data)
	{
		if(empty($data['new_pass']))
		{
			$this->errorRedirectNow('You must set a password when creating a new user.', $this->getUrl('*/*/*'));
		}

		$usertest = Mage::getModel('ecodes/premiumusers')->getCollection()->getByUsername( $data['user'] );
		if($usertest->getId() != 0) {
			$this->errorRedirectNow('This username has already been user: '. $data['user'] . '.', $this->getUrl('*/*/*'));
		}
		$premiumsub = Mage::getModel('ecodes/premiumsubs')->load($this->getRequest()->getParam('subscription_id'));
		$customer = Mage::getModel('customer/customer')->load($premiumsub->getCustomerId());
		if( ! $customer->hasEcodesMasterUser() )
		{
			$this->errorRedirectNow('There is not master user associated to this customer: ' . $customer->getName() . '.', $this->getUrl('*/customer/edit/id/' . $customer->getId()) );
		}

		$helper = Mage::helper('ecodes');
		//create new user
		$result = Mage::getModel('ecodes/api')
		->createUser(
				$customer->getEcodesMasterUser(),
				$helper->decryptPassword($customer->getEcodesMasterPass()),
				$data['user'],
				$helper->decryptPassword($data['pass']),
				$data['firstname'],
				$data['lastname'],
				$data['email']
		);
		if( ! $result['success'])
		{
			$this->errorRedirectNow('There was a problem with ICC Connect: ' . $result['message']);
		}
	}

}