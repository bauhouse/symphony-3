<?php

	include_once('lib/class.role.php');

	Class extensionAdministrationAccessControl implements iExtension{
		
		public function about(){
			return (object)array(
				'name' => 'Administration Access Control',
				'version' => '1.0',
				'release-date' => '2010-05-26',
				'author' => (object)array(
					'name' => 'Alistair Kearney',
					'website' => 'http://alistairkearney.com',
					'email' => 'hi@alistairkearney.com'
				)
	 		);
		}

		public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/administration/',
					'delegate' => 'AdminPagePreGenerate',
					'callback' => 'cbModifyPages'
				),
				
				array(
					'delegate' => 'UserPreCreate',
					'page' => '/system/users/new/',
					'callback' => 'cbSetRoleFromPost'
				),
				
				array(
					'delegate' => 'UserPreSave',
					'page' => '/system/users/edit/',
					'callback' => 'cbSetRoleFromPost'
				),
				
				array(
					'delegate' => 'AdminPagePreBuild',
					'page' => '/administration/',
					'callback' => 'cbCheckPagePermissions'
				),
			);
		}

		public function fetchNavigation(){
			return array(
				array(
					'location' => 'System',
					'name' => 'User Roles',
					'link' => '/roles/'
				)
			);
		}
		
		public function cbCheckPagePermissions(array $context=NULL){

			$role = Role::load(Administration::instance()->User->role_id);

			// Sections that have no create or edit permissions
			if(preg_match('/^\/publish\/([^\/]+)\/$/i', $context['callback']['pageroot'], $match)){
				$section_handle = $match[1];
				if(
					((!isset($role->permissions()->{"publish::{$section_handle}.create"}) || $role->permissions()->{"publish::{$section_handle}.create"} < 1)
					&&
					(!isset($role->permissions()->{"publish::{$section_handle}.edit"}) || $role->permissions()->{"publish::{$section_handle}.edit"} < 1))
					
					or
					
					// On a 'new' page, but no 'create' permission
					($context['callback']['context']['page'] == 'new' 
						&& (!isset($role->permissions()->{"publish::{$section_handle}.create"}) 
						|| $role->permissions()->{"publish::{$section_handle}.create"} < 1))
						
					or

					// On an 'edit' page, but no 'edit' permission
					($context['callback']['context']['page'] == 'edit' 
						&& (!isset($role->permissions()->{"publish::{$section_handle}.edit"}) 
						|| $role->permissions()->{"publish::{$section_handle}.edit"} < 1))
					
				){
					throw new AdministrationForbiddenPageException;
				}
			}
			
			// Blueprints pages that have no create or edit permissions
			elseif(preg_match('/^\/(blueprints|system)\/(datasource|events|views|sections|utilities|users)\//i', $context['callback']['pageroot'], $match)){
				$area = $match[1];
				$handle = $match[2];
				
				if(
					// Cannot create or edit
					((!isset($role->permissions()->{"{$area}::{$handle}.create"}) || $role->permissions()->{"{$area}::{$handle}.create"} < 1)
					&&
					(!isset($role->permissions()->{"{$area}::{$handle}.edit"}) || $role->permissions()->{"{$area}::{$handle}.edit"} < 1))
					
					or
					
					// On a 'new' page, but no 'create' permission
					($context['callback']['context'][0] == 'new' 
						&& (!isset($role->permissions()->{"{$area}::{$handle}.create"}) 
						|| $role->permissions()->{"{$area}::{$handle}.create"} < 1))
						
					or

					// On an 'edit' page, but no 'edit' permission
					($context['callback']['context'][0] == 'edit' 
						&& (!isset($role->permissions()->{"{$area}::{$handle}.edit"}) 
						|| $role->permissions()->{"{$area}::{$handle}.edit"} < 1))
						
				){
					throw new AdministrationForbiddenPageException;
				}
			}
			
			// Forbidden pages
		}
		
		public function cbSetRoleFromPost(array $context=NULL){
			if(!isset($_POST['fields']['role_id'])) return;
			$context['user']->role_id = (int)$_POST['fields']['role_id'];
		}
		
		private function removeCreateButton(DOMDocument $doc){
			$buttons = $doc->xpath("//a[@class='create button']");
			foreach($buttons as $element){
				$element->parentNode->removeChild($element);
			}
		}
		
		private function removeWithSelected(DOMDocument $doc){
			$actions = $doc->xpath("//div[@class='actions']");
			foreach($actions as $element){
				$element->parentNode->removeChild($element);
			}
			
			$checkboxes = $doc->xpath("//td/input[@type='checkbox' and starts-with(@name, 'items')]");
			foreach($checkboxes as $element){
				$element->parentNode->removeChild($element);
			}
		}
		
		public function cbModifyPages($context=NULL){
			$callback = Administration::instance()->getPageCallback();
			$doc = $context['page'];
			$role = Role::load(Administration::instance()->User->role_id);
			
			// TODO: Remove items from navigation that the user has no permission to access
			//			- Sections: if user has no edit or create privileges
			//			- Forbidden Pages
			
			// Users
			if($callback['pageroot'] == '/system/users/'){

				// Index
				if(is_null($callback['context'])){

					if(isset($role->permissions()->{"system::users.edit"}) && $role->permissions()->{"system::users.edit"} > 0){
						if(isset($_POST['with-selected']) && isset($_POST['items']) && preg_match('/^aac-role::(\d+)/i', $_POST['with-selected'], $match)){
							$checked = @array_keys($_POST['items']);
							if(is_array($checked) && !empty($checked)){
								Symphony::Database()->query(sprintf(
									"UPDATE `tbl_users` SET `role_id` = %d WHERE `id` IN (%s)",
									(int)$match[1],
									implode(',', $checked)
								));
							}
						}
					}
					
					// Remove the 'Create New' button if user has no 'create' privileges
					if(!isset($role->permissions()->{"system::users.create"}) || $role->permissions()->{"system::users.create"} < 1){
						$this->removeCreateButton($doc);
					}

					// Remove the 'With Selected' and row checkboxes if user has no 'edit' privileges
					if(!isset($role->permissions()->{"system::users.edit"}) || $role->permissions()->{"system::users.edit"} < 1){
						$this->removeWithSelected($doc);
					}
					
					$this->modifyUsersPageIndex($context);
				}
				
				// New
				elseif(isset($callback['context'][0]) && $callback['context'][0] == 'new'){
					$this->modifyUsersPageNew($context);
				}

				// Edit
				elseif(isset($callback['context'][0]) && $callback['context'][0] == 'edit'){
					$this->modifyUsersPageEdit((int)$callback['context'][1], $context);
				}
			}

			// Publish
			elseif(preg_match('/^\/publish\/([^\/]+)\/$/i', $callback['pageroot'], $match)){
				$handle = $match[1];
				
				switch($callback['context']['page']){
					case 'index':
					
						// Remove the 'Create New' button if user has no 'create' privileges
						if(!isset($role->permissions()->{"publish::{$handle}.create"}) || $role->permissions()->{"publish::{$handle}.create"} < 1){
							$this->removeCreateButton($doc);
						}
						
						// Remove the 'With Selected' and row checkboxes if user has no 'edit' privileges
						if(!isset($role->permissions()->{"publish::{$handle}.edit"}) || $role->permissions()->{"publish::{$handle}.edit"} < 1){
							$this->removeWithSelected($doc);
						}
						
						break;
				}
			}
			
			// Catch All
			elseif(preg_match('/^\/([^\/]+)\/([^\/]+)\/$/i', $callback['pageroot'], $match)){
				$area = $match[1];
				$handle = $match[2];

				switch($callback['context'][0]){
					
					
					case 'index':
					default:
					
						// Remove the 'Create New' button if user has no 'create' privileges
						if(!isset($role->permissions()->{"{$area}::{$handle}.create"}) || $role->permissions()->{"{$area}::{$handle}.create"} < 1){
							$this->removeCreateButton($doc);
						}
						
						// Remove the 'With Selected' and row checkboxes if user has no 'edit' privileges
						if(!isset($role->permissions()->{"{$area}::{$handle}.edit"}) || $role->permissions()->{"{$area}::{$handle}.edit"} < 1){
							$this->removeWithSelected($doc);
						}
						break;
				}
			}
		}
		
		private function modifyUsersPageIndex($context=NULL){
			$doc = $context['page'];
	
			// Add the 'Role' column to the thead
	        $tr = $doc->xpath('//table/thead/tr')->item(0);

			$th_role = $doc->createElement('th', 'Role');
			$th_role->setAttribute('scope', 'col');
			$tr->appendChild($th_role);

			// Find all the users
		    $users = $doc->xpath('//table/tbody/tr');

			foreach($users as $position => $u){
				
				// Figure out the user's ID based on the url for editing. Would be nice if there was a more direct way
				$edit_url = trim((string)$u->getElementsByTagName('td')->item(0)->getElementsByTagName('a')->item(0)->getAttribute('href'));
				preg_match('/edit\/(\d+)\/$/i', $edit_url, $match);
				$user_id = (int)$match[1];
				
				// Fine the role name
				$role = Symphony::Database()->query(
					sprintf(
						"SELECT r.name, r.id FROM `tbl_aac_roles` AS `r`
						LEFT JOIN `tbl_users` AS `u` ON r.id = u.role_id
						WHERE u.id = %d
						LIMIT 1",
						$user_id
					)
				)->current();
				
				// Append the new td using the role we discovered above
				$td = $doc->createElement('td');
				$td->appendChild(Widget::Anchor(General::sanitize((string)$role->name), ADMIN_URL . "/extension/aac/roles/edit/{$role->id}/"));
				$u->appendChild($td);
			}

			// Update the with-selected
		    $with_selected = $doc->xpath("//select[@name = 'with-selected']")->item(0);
			if($with_selected instanceof DOMNode){
				//$roles = Symphony::Database()->query("SELECT * FROM `tbl_aac_roles` ORDER BY `name` ASC");
			
				$optgroup = $doc->createElement('optgroup');
				$optgroup->setAttribute('label', 'Set Role');

				foreach(new RoleIterator as $r){
					$option = $doc->createElement('option', General::sanitize($r->title));
					$option->setAttribute('value', "aac-role::{$r->id}");
					$optgroup->appendChild($option);
				}
			
				$with_selected->appendChild($optgroup);
			}
	        $context['page'] = $doc;
		}
		
		private function modifyUsersPageNew($context=NULL){
			$doc = $context['page'];

			$element = $doc->xpath("//div[@id='layout']/div[1]/fieldset[1]")->item(0);
			
			$group = $doc->createElement('div');
			$group->setAttribute('class', 'group');
			
			$label = $doc->createElement('label', 'Role');
			
			$select = $doc->createElement('select');
			$select->setAttribute('name', 'fields[role_id]');

			foreach(new RoleIterator as $r){
				$option = $doc->createElement('option', General::sanitize($r->name));
				$option->setAttribute('value', $r->id);
				
				if(isset($_POST['fields']) && isset($_POST['fields']['role_id']) && (int)$_POST['fields']['role_id'] == $r->id){
					$option->setAttribute('selected', 'selected');
				}
				
				$select->appendChild($option);
			}
			
			$label->appendChild($select);
			$group->appendChild($label);
				
			$element->appendChild($group);
			
			$context['page'] = $doc;
		}
		
		private function modifyUsersPageEdit($user_id, $context=NULL){

			$user = User::load($user_id);

			$doc = $context['page'];
			
			$element = $doc->xpath("//div[@id='layout']/div[1]/fieldset[1]")->item(0);
			
			$group = $doc->createElement('div');
			$group->setAttribute('class', 'group');
			
			$label = $doc->createElement('label', 'Role');
			
			$select = $doc->createElement('select');
			$select->setAttribute('name', 'fields[role_id]');

			$role_selected = false;

			foreach(new RoleIterator as $r){
				$option = $doc->createElement('option', General::sanitize($r->name));
				$option->setAttribute('value', $r->id);
				
				if($role_selected == false){
					if(isset($_POST['fields']) && isset($_POST['fields']['role_id']) && (int)$_POST['fields']['role_id'] == $r->id){
						$option->setAttribute('selected', 'selected');
						$role_selected = true;
					}
					elseif($user->role_id == $r->id){
						$option->setAttribute('selected', 'selected');
						$role_selected = true;
					}
				}
				
				$select->appendChild($option);
			}
			
			$label->appendChild($select);
			$group->appendChild($label);
				
			$element->appendChild($group);
			
			$context['page'] = $doc;
		}		
		
		public function uninstall(){
			Symphony::Database()->query("DROP TABLE `tbl_aac_roles`");
			Symphony::Database()->query("DROP TABLE `tbl_aac_permissions`");
			Symphony::Database()->query("ALTER TABLE `tbl_users` DROP `role_id`");
			return true;
		}

		public function install(){

			Symphony::Database()->query("CREATE TABLE `tbl_aac_roles` (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `name` varchar(255) NOT NULL,
			  `description` text,
			  PRIMARY KEY (`id`),
			  KEY `name` (`name`)
			)");
			
			Symphony::Database()->query("CREATE TABLE `tbl_aac_permissions` (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `role_id` int(11) NOT NULL,
			  `key` varchar(50) NOT NULL,
			  `type` varchar(50) NOT NULL,
			  `level` tinyint(1) unsigned NOT NULL DEFAULT '0',
			  PRIMARY KEY (`id`)
			)");

			// Default roles
			Symphony::Database()->insert('tbl_aac_roles', array('id' => 1, 'name' => 'Author'));
			Symphony::Database()->insert('tbl_aac_roles', array('id' => 2, 'name' => 'Developer'));
			
			// Add a role field to the user table
			Symphony::Database()->query(
				"ALTER TABLE `tbl_users` ADD `role_id` INT(11) NOT NULL"
			);
			
			// Set existing users to 'Developer' role
			Symphony::Database()->update('tbl_users', array('role_id' => 2));

			// Add permissions for the default roles
			foreach(new SectionIterator as $section){
				
				// Developer 'create'
				Symphony::Database()->insert('tbl_aac_permissions', array(
					'id' => NULL,
					'role_id' => 2,
					'key' => "publish::{$section->handle}",
					'type' => 'create',
					'level' => 1
				));
				
				// Developer 'edit' (all)
				Symphony::Database()->insert('tbl_aac_permissions', array(
					'id' => NULL,
					'role_id' => 2,
					'key' => "publish::{$section->handle}",
					'type' => 'edit',
					'level' => 2
				));
				
				// Author 'create'
				Symphony::Database()->insert('tbl_aac_permissions', array(
					'id' => NULL,
					'role_id' => 1,
					'key' => "publish::{$section->handle}",
					'type' => 'create',
					'level' => 1
				));
			
				// Author 'edit' (own)
				Symphony::Database()->insert('tbl_aac_permissions', array(
					'id' => NULL,
					'role_id' => 1,
					'key' => "publish::{$section->handle}",
					'type' => 'edit',
					'level' => 1
				));
			}
			
			// TODO: Forbidden Pages
			
			return true;

		}

	}
	
	return 'extensionAdministrationAccessControl';