<?php
/**
 * @package    Com_Cluster
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;

JLoader::import("/components/com_cluster/includes/cluster", JPATH_ADMINISTRATOR);

/**
 * Get Cluster Users controller class.
 *
 * @since  1.0.0
 */
class ClusterControllerClusterUsers extends BaseController
{
	/**
	 * Method to get user list depending on the client chosen.
	 *
	 * @return   null
	 *
	 * @since    1.0.0
	 */
	public function getUsersByClientId()
	{
		$app = Factory::getApplication();

		// Check for request forgeries.
		if (!Session::checkToken())
		{
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
			$app->close();
		}

		$clusterIds = $app->input->getInt('cluster_id', 0);
		$userOptions = $allUsers = array();

		// Initialize array to store dropdown options
		$userOptions[] = HTMLHelper::_('select.option', "", Text::_('COM_CLUSTER_OWNERSHIP_USER'));

		// Check cluster selected or not
		if ($clusterIds)
		{
			$clusterObj = ClusterFactory::model('ClusterUsers', array('ignore_request' => true));
			$clusterObj->setState('filter.block', 0);
			$clusterObj->setState('filter.cluster_id', $clusterIds);
			$clusterObj->setState('list.group_by_user_id', 1);
			$allUsers = $clusterObj->getItems();
		}

		if (!empty($allUsers))
		{
			foreach ($allUsers as $user)
			{
				$userOptions[] = HTMLHelper::_('select.option', $user->user_id, trim($user->uname . ' (' . $user->uemail . ')'));
			}
		}

		echo new JsonResponse($userOptions);
		$app->close();
	}
}
