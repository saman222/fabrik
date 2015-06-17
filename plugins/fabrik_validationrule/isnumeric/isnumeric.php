<?php
/**
 * Is Numeric Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.isnumeric
 * @copyright   Copyright (C) 2005-2015 fabrikar.com - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Plugins\Validation;

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Is Numeric Validation Rule
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.validationrule.isnumeric
 * @since       3.5
 */
class IsNumeric extends Validation
{
	/**
	 * Plugin name
	 *
	 * @var string
	 */
	protected $pluginName = 'isnumeric';

	/**
	 * Validate the elements data against the rule
	 *
	 * @param   string  $data           To check
	 * @param   int     $repeatCounter  Repeat group counter
	 *
	 * @return  bool  true if validation passes, false if fails
	 */
	public function validate($data, $repeatCounter)
	{
		// Could be a dropdown with multivalues
		if (is_array($data))
		{
			$data = implode('', $data);
		}

		$params = $this->getParams();
		$allow_empty = $params->get('isnumeric-allow_empty');

		if ($allow_empty == '1' and empty($data))
		{
			return true;
		}

		return is_numeric($this->elementModel->unNumberFormat($data));
	}

	/**
	 * Does the validation allow empty value?
	 * Default is false, can be overridden on per-validation basis (such as isnumeric)
	 *
	 * @return bool
	 */
	protected function allowEmpty()
	{
		$params = $this->getParams();
		$allow_empty = $params->get('isnumeric-allow_empty');

		return $allow_empty == '1';
	}
}
