<?php
/**
 * Plugin element to render plain text/HTML
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.display
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugins\Element;

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\ArrayHelper;
use Fabrik\Helpers\Worker;

/**
 * Plugin element to render plain text/HTML
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.display
 * @since       3.5
 */
class Display extends Element
{
	/**
	 * Db table field type
	 *
	 * @var  string
	 */
	protected $fieldDesc = 'TEXT';

	/**
	 * Does the element's data get recorded in the db
	 *
	 * @var bool
	 */
	protected $recordInDatabase = false;

	/**
	 * Set/get if element should record its data in the database
	 *
	 * @deprecated - not used
	 *
	 * @return bool
	 */

	public function setIsRecordedInDatabase()
	{
		$this->recordInDatabase = false;
	}

	/**
	 * Get the element's HTML label
	 *
	 * @param   int     $repeatCounter  Group repeat counter
	 * @param   string  $tmpl           Form template
	 *
	 * @return  string  label
	 */

	public function getLabel($repeatCounter = 0, $tmpl = '')
	{
		$params = $this->getParams();
		$element = $this->getElement();

		if (!$params->get('display_showlabel', true))
		{
			$element->label = $this->getValue(array());
			$element->label_raw = $element->label;
		}

		return parent::getLabel($repeatCounter, $tmpl);
	}

	/**
	 * Get the element's raw label (used for details view, not wrapped in <label> tags
	 *
	 * @return  string  Label
	 */
	protected function getRawLabel()
	{
		if (!$this->getParams()->get('display_showlabel', true))
		{
			return $this->getValue(array());;
		}

		return parent::getRawLabel();
	}

	/**
	 * Shows the data formatted for the list view
	 *
	 * @param   string    $data      elements data
	 * @param   stdClass  &$thisRow  all the data in the lists current row
	 *
	 * @return  string	formatted value
	 */

	public function renderListData($data, stdClass &$thisRow)
	{
		unset($this->default);
		$value = $this->getValue(ArrayHelper::fromObject($thisRow));

		return parent::renderListData($value, $thisRow);
	}

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           To pre-populate element with
	 * @param   int    $repeatCounter  Repeat group counter
	 *
	 * @return  string	elements html
	 */

	public function render($data, $repeatCounter = 0)
	{
		$params = $this->getParams();
		$layout = $this->getLayout('form');
		$displayData = new stdClass;
		$displayData->id = $this->getHTMLId($repeatCounter);
		$displayData->value = $params->get('display_showlabel', true) ? $this->getValue($data, $repeatCounter) : '';

		return $layout->render($displayData);
	}

	/**
	 * Helper method to get the default value used in getValue()
	 * Unlike other elements where readonly effects what is displayed, the display element is always
	 * read only, so get the default value.
	 *
	 * @param   array  $data  Form data
	 * @param   array  $opts  Options
	 *
	 * @since  3.0.7
	 *
	 * @return  mixed	value
	 */

	protected function getDefaultOnACL($data, $opts)
	{
		return ArrayHelper::getValue($opts, 'use_default', true) == false ? '' : $this->getDefaultValue($data);
	}

	/**
	 * Determines the value for the element in the form view
	 *
	 * @param   array  $data           Form data
	 * @param   int    $repeatCounter  When repeating joined groups we need to know what part of the array to access
	 * @param   array  $opts           Options
	 *
	 * @return  string	value
	 */

	public function getValue($data, $repeatCounter = 0, $opts = array())
	{
		$value = $this->getDefaultOnACL($data, $opts);

		if ($value === '')
		{
			// Query string for joined data
			$value = ArrayHelper::getValue($data, $value);
		}

		$formModel = $this->getFormModel();

		// Stops this getting called from form validation code as it messes up repeated/join group validations

		if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1)
		{
			Worker::getPluginManager()->runPlugins('onGetElementDefault', $formModel, 'form', $this);
		}

		return $value;
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int  $repeatCounter  Repeat group counter
	 *
	 * @return  array
	 */

	public function elementJavascript($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);

		return array('FbDisplay', $id, $opts);
	}
}
