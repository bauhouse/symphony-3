<?php
	
	require_once EXTENSIONS . '/ds_sections/lib/class.datasource.php';
	
	Final Class DataSourceComments extends SectionsDataSource {

		public function __construct(){
			parent::__construct();

			$this->_about = (object)array(
				'name'			=> 'Comments',
				'author'		=> (object)array(
					'name'			=> 'Stephen Bau',
					'website'		=> 'http://home/sym3/alpha',
					'email'			=> 'bauhouse@gmail.com'
				),
				'version'		=> '1.0',
				'release-date'	=> '2010-06-12T23:04:43+00:00'
			);
			
			$this->_parameters = (object)array(
				'root-element' => 'comments',
				'limit' => '20',
				'page' => '1',
				'section' => 'comments',
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
					  3 => 'comment: formatted',
					  4 => 'items',
					),
				'parameter-output' => array (
					  0 => 'system:id',
					  1 => 'system:creation-date',
					  2 => 'system:modification-date',
					  3 => 'system:user',
					  4 => 'comment',
					  5 => 'items',
					),
				'dependencies' => array (
					),
			);
		}

		public function allowEditorToParse() {
			return true;
		}
	}

	return 'DataSourceComments';