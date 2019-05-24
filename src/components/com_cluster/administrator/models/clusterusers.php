<?php
/**
 * @package    Cluster
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Model\ListModel;

/**
 * Methods supporting a list of records.
 *
 * @since  1.0.0
 */
class ClusterModelClusterUsers extends ListModel
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see        JController
	 * @since      1.0.0
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'cu.id',
				'cluster_id', 'cu.cluster_id',
				'state', 'cu.state',
			);
		}

		parent::__construct($config);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   JDatabaseQuery
	 *
	 * @since    1.0.0
	 */
	protected function getListQuery()
	{
		// Initialize variables.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select(array('cu.*','cl.name', 'users.name as uname'));
		$query->from($db->quoteName('#__tj_cluster_nodes', 'cu'));
		$query->join('INNER', $db->quoteName('#__users', 'users') . ' ON (' . $db->quoteName('cu.user_id') . ' = ' . $db->quoteName('users.id') . ')');
		$query->join('INNER', $db->quoteName('#__tj_clusters', 'cl') . ' ON (' . $db->quoteName('cl.id') . ' = ' . $db->quoteName('cu.cluster_id') . ')');

		// Filter by search in title.
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('cu.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
				$query->where('(users.name LIKE' . $search . ' OR cl.name LIKE ' . $search . ')');
			}
		}

		$created_by = $this->getState('filter.created_by');

		if (!empty($created_by))
		{
			$query->where($db->quoteName('cu.created_by') . ' = ' . (int) $created_by);
		}

		// Filter by state
		$state = $this->getState('filter.state');

		if (is_numeric($state))
		{
			$query->where('cu.state = ' . (int) $state);
		}
		elseif ($state === '')
		{
			$query->where('(cu.state = 0 OR cu.state = 1)');
		}

		// Filter by cluster
		$cluster = $this->getState('filter.cluster_id');

		if (is_numeric($cluster))
		{
			$query->where('cu.cluster_id = ' . (int) $cluster);
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}
}
