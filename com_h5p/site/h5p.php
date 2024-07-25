<?php
/**
 * @package    Com_H5P
 * @author     Vitalii Butsykin <v.butsykin@gmail.com>
 * @copyright  2022 Vitalii Butsykin
 * @license    GNU General Public License ver. 2 or later
 */

defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\MVC\Controller\BaseController;

// Include dependancies
jimport('joomla.application.component.controller');

JLoader::registerPrefix('H5p', JPATH_COMPONENT);
JLoader::register('H5pController', JPATH_COMPONENT . '/controller.php');


// Execute the task.
$controller = BaseController::getInstance('H5p');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();