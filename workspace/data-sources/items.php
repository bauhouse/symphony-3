<?php
	
	require_once EXTENSIONS . '/ds_sections/lib/class.datasource.php';
	
	Final Class DataSourceItems extends SectionsDataSource {

		public function __construct(){
			parent::__construct();

			$this->_about = (object)array(
				'name'			=> 'Items',
				'author'		=> (object)array(
					'name'			=> 'Stephen Bau',
					'website'		=> 'http://home/sym3/alpha',
					'email'			=> 'bauhouse@gmail.com'
				),
				'version'		=> '1.0',
				'release-date'	=> '2010-06-12T23:04:12+00:00'
			);
			
			$this->_parameters = (object)array(
				'root-element' => 'items',
				'limit' => '20',
				'page' => '1',
				'section' => 'items',
				'conditions' => array (
					),
				'filters' => array (
					),
				'redirect-404-on-empty' => false,
				'append-pagination' => false,
				'append-sorting' => false,
				'sort-field' => 'system:id',
				'sort-order' => 'desc',
				'included-elements' => array (
					  0 => 'system:creation-date',
					  1 => 'system:modification-date',
					  2 => 'system:user',
					  3 => 'to-do: formatted',
					  4 => 'open',
					),
				'parameter-output' => array (
					  0 => 'to-do',
					),
				'dependencies' => array (
					),
			);
		}

		public function allowEditorToParse() {
			return true;
		}
	}

	return 'DataSourceItems';