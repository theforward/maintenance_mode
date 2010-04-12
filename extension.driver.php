<?php

	Class extension_maintenance_mode extends Extension{

		public function about(){
			return array('name' => 'Maintenance Mode',
						 'version' => '1.2',
						 'release-date' => '2010-02-02',
						 'type' => array('Core'),
						 'author' => array('name' => 'Alistair Kearney',
										   'website' => 'http://pointybeard.com',
										   'email' => 'alistair@pointybeard.com')
				 		);
		}
		
		public function getSubscribedDelegates(){
			return array(
						array(
							'page' => '/system/settings/',
							'delegate' => 'AddCustomToolFieldsets',
							'callback' => 'appendPreferences'
						),
						
						array(
							'page' => '/system/settings/',
							'delegate' => 'Save',
							'callback' => '__SavePreferences'
						),							
						
						array(
							'page' => '/system/settings/',
							'delegate' => 'CustomActions',
							'callback' => '__toggleMaintenanceMode'
						),

						array(
							'page' => '/frontend/',
							'delegate' => 'FrontendPrePageResolve',
							'callback' => '__checkForMaintenanceMode'
						),
							
						array(
							'page' => '/frontend/',
							'delegate' => 'FrontendParamsResolve',
							'callback' => '__addParam'
						),						
						
						array(
							'page' => '/backend/',
							'delegate' => 'AppendPageAlert',
							'callback' => '__appendAlert'
						),				

					);
		}
		
		public function __toggleMaintenanceMode($context){
			
			if($_REQUEST['action'] == 'toggle-maintenance-mode'){			
				$value = (Symphony::Configuration()->get('enabled', 'maintenance_mode') == 'no' ? 'yes' : 'no');					
				Symphony::Configuration()->set('enabled', $value, 'maintenance_mode');
				Symphony::Parent()->saveConfig();
				redirect((isset($_REQUEST['redirect']) ? ADMIN_URL . '' . $_REQUEST['redirect'] : Symphony::Parent()->getCurrentPageURL() . '/'));
			}
			
		}
		
		public function __appendAlert($context){
			
			if(!is_null($context['alert'])) return;
			
			if(Symphony::Configuration()->get('enabled', 'maintenance_mode') == 'yes'){
				Administration::instance()->Page->pageAlert(__('This site is currently in maintenance mode.') . ' <a href="' . ADMIN_URL . '/system/preferences/?action=toggle-maintenance-mode&amp;redirect=' . getCurrentPage() . '">' . __('Restore?') . '</a>', Alert::NOTICE);
			}
		}
		
		public function __addParam($context){
			$context['params']['site-mode'] = (Symphony::Configuration()->get('enabled', 'maintenance_mode') == 'yes' ? 'maintenance' : 'live'); 
		}
		
		public function __checkForMaintenanceMode($context){
			
			if(!Symphony::Parent()->isLoggedIn() && Symphony::Configuration()->get('enabled', 'maintenance_mode') == 'yes'){
				
				$context['row'] = Symphony::Database()->fetchRow(0, "
											SELECT `tbl_pages`.* FROM `tbl_pages`, `tbl_pages_types` 
											WHERE `tbl_pages_types`.page_id = `tbl_pages`.id 
											AND tbl_pages_types.`type` = 'maintenance' 
											LIMIT 1");
			
				if(empty($context['row'])){
					Symphony::Parent()->customError(E_USER_ERROR, __('Website Offline'), __('This site is currently in maintenance. Please check back at a later date.'), false, true);
				}
				
			}
			
		}
		
		public function __SavePreferences($context){

			if(!is_array($context['settings'])) $context['settings'] = array('maintenance_mode' => array('enabled' => 'no'));
			
			elseif(!isset($context['settings']['maintenance_mode'])){
				$context['settings']['maintenance_mode'] = array('enabled' => 'no');
			}
			
		}

		public function appendPreferences($context){

			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', __('Maintenance Mode')));			
			
			$label = Widget::Label();
			$input = Widget::Input('settings[maintenance_mode][enabled]', 'yes', 'checkbox');
			if(Symphony::Configuration()->get('enabled', 'maintenance_mode') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue($input->generate() . ' ' . __('Enable maintenance mode'));
			$group->appendChild($label);
						
			$group->appendChild(new XMLElement('p', __('Maintenance mode will redirect all visitors, other than developers, to the specified maintenance page.'), array('class' => 'help')));
									
			$context['wrapper']->appendChild($group);
						
		}
		
	}
