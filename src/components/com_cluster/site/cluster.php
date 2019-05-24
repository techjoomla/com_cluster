<?php
/**
 * @package    Com_Cluster
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Include dependancies
jimport('joomla.application.component.controller');

JLoader::registerPrefix('Cluster', JPATH_COMPONENT);
JLoader::register('ClusterController', JPATH_COMPONENT . '/controller.php');


// Execute the task.
$controller = JControllerLegacy::getInstance('Cluster');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
