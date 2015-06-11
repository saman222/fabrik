<?php
/**
 * Renders the form's database name or a field to create one
 *
 * @package     Joomla
 * @subpackage  Form
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

Fabrik\Helpers;


/**
 * Renders the form's database name or a field to create one
 *
 * @package     Joomla
 * @subpackage  Form
 * @since       1.6
 */

class JFormFieldFormDatabaseName extends JFormFieldText
{
	/**
	 * Element name
	 * @var		string
	 */
	protected $name = 'FormDatabaseName';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string	The field input markup.
	 */

	/**
	 * Method to get the field input markup.
	 *
	 * @return	string	The field input markup.
	 */

	protected function getInput()
	{
		if ($this->form->getValue('record_in_database'))
		{
			// FIXME - jsonify
			$db = Worker::getDbo(true);
			$query = $db->getQuery(true);
			$id = (int) $this->form->getValue('id');
			$query->select('db_table_name')->from('#__fabrik_lists')->where('form_id = ' . $id);
			$db->setQuery($query);
			$this->element['readonly'] == true;
			$this->element['class'] = 'readonly';
			$this->value = $db->loadResult();
		}

		return parent::getInput();
	}
}
