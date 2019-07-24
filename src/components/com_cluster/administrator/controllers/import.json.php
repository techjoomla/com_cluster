<?php
/**
 * @package    Cluster
 *
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;

jimport('joomla.log.logger.formattedtext');

JLoader::import('components.com_users.models.users', JPATH_ADMINISTRATOR);
JLoader::import('components.com_cluster.controllers.defines', JPATH_ADMINISTRATOR);

/**
 * The cluster import controller
 *
 * @since  1.0.0
 */
class ClusterControllerImport extends BaseController
{
	/**
	 * The main function triggered after on format upload
	 *
	 * @return object of result and message
	 *
	 * @since 1.0.0
	 * */
	public function csvImport()
	{
		$user          = Factory::getUser();
		$canImportdata = $user->authorise('core.import', 'com_cluster');

		$app = Factory::getApplication();

		if (!$canImportdata)
		{
			echo new JResponseJson(Text::_('COM_CLUSTER_NOT_AUTHORISED'), false);
			jexit();
		}

		header('Cache-Control: no-cache, must-revalidate');
		header('Content-type: application/json');

		jimport('joomla.log.log');
		$logFileName = 'com_cluster.import_' . Factory::getDate() . '.log';

		Log::addLogger(array('text_file' => $logFileName), Log::ALL, array('com_cluster'));

		// Set log file name to session
		$session     = Factory::getSession();
		$session->set('com_cluster.import', $logFileName);

		$files          = $app->input->files;
		$file_to_upload = $files->get('FileInput', '', 'ARRAY');

		/* Validate the uploaded file*/

		$validate_result = $this->validateUpload($file_to_upload);

		$ret = array();

		if ($validate_result['res'] != 1)
		{
			$ret['OUTPUT']['flag'] = $validate_result['res'];
			$ret['OUTPUT']['msg'] = $validate_result['msg'];
			echo new JResponseJson($ret);
			jexit();
		}

		$return = 1;
		$msg = '';

		$file_attached	= $file_to_upload['tmp_name'];

		/* Save csv content to question table */

		$result = array();

		$result = $this->saveCsvContent($file_to_upload);

		$filename = $file_to_upload['name'];

		$ret['OUTPUT']['flag'] = $result['returns'];
		$ret['OUTPUT']['msg']  = $result['msg'];

		echo new JResponseJson($ret);
		jexit();
	}

	/**
	 * The function to validate the uploaded format file
	 *
	 * @param   MIXED  $file_to_upload  file object
	 *
	 * @return  array of result and message
	 *
	 * @since 1.0.0
	 * */
	public function validateUpload($file_to_upload)
	{
		$app = Factory::getApplication()->input;
		$clusterParams = ComponentHelper::getParams('com_cluster');
		$filename      = $file_to_upload['name'];

		$output = array();
		$return = 1;
		$msg = '';

		if ($file_to_upload["error"] == UPLOAD_ERR_OK)
		{
			// Check if file size in within the uploading limit of site*/

				if ( $app->server->getString('CONTENT_LENGTH', '') > ($clusterParams->get('file_size', 0) * 1024 * 1024)
					|| $app->server->getString('CONTENT_LENGTH', '') > (int) (ini_get('upload_max_filesize')) * 1024 * 1024
					|| $app->server->getString('CONTENT_LENGTH', '') > (int) (ini_get('post_max_size')) * 1024 * 1024
					|| (($app->server->getString('CONTENT_LENGTH', '') > (int) (ini_get('memory_limit')) * 1024 * 1024) && ((int) (ini_get('memory_limit')) != -1)))
				{
					$return = 0;
					$msg = Text::sprintf('COM_CLUSTER_UPLOAD_SIZE_ERROR', $clusterParams->get('import_size', 10, 'INT') . ' MB');
				}

			/* Check for the type/extensiom of the file*/
			if ($return == 1)
			{
				$fileext = File::getExt($filename);

				$valid_extensions_arr = array('csv');

				if (!in_array($fileext, $valid_extensions_arr))
				{
					$msg = Text::_('COM_CLUSTER_VALID_DOCUMENT_UPLOAD');
					$return = 0;
				}
			}
		}
		else
		{
			$return = 0;
			$msg = Text::_('COM_CLUSTER_ERROR_UPLOADINGFILE', $filename);
		}

		$output['res'] = $return;
		$output['msg'] = $msg;

		return $output;
	}

	/**
	 * Save csv content table from csv
	 *
	 * @param   MIXED  $file_to_upload  file object
	 *
	 * @return  ARRAY
	 *
	 * @since  1.0.0
	 */
	public function saveCsvContent($file_to_upload)
	{
		$csvFileName = $file_to_upload['name'];

		$output = array();
		$success = $failed = $missingDetails = 0;
		$logLink = '';
		$key = '';

		if (($handle = fopen($file_to_upload['tmp_name'], 'r')) !== false)
		{
			$rowNum = '';
			$lineno = 0;
			$headers = array();

			while (($data = fgetcsv($handle)) !== false)
			{
				if ($rowNum == 0)
				{
					$lineno++;

					// Parsing the CSV header

					foreach ($data as $d)
					{
						$headers[] = $d;
					}
				}
				else
				{
					// Parsing the data rows
					$rowData = array();

					foreach ($data as $d)
					{
						$rowData[] = $d;
					}

					$masterData[] = array_combine($headers, $rowData);
				}

				$rowNum++;
			}

			fclose($handle);

			if (empty($masterData))
			{
				$output['returns'] = 0;
				$output['msg'] = Text::_('COM_CLUSTER_IMPORT_BLANK_FILE');
			}

			$column = array(COM_CLUSTER_GROUP_TITLE, COM_CLUSTER_GROUP_EMAIL, COM_CLUSTER_CLIENT_ID, COM_CLUSTER_CLUSTERUSERS_STATE, COM_CLUSTER_GROUP_ID);

			// Error and Info logs
			$logFilepath = JRoute::_('index.php?option=com_cluster&view=clusters&task=import.downloadLog&prefix=com_cluster.import&format=json');

			$logLink = '<a href="' . $logFilepath . '" >' . Text::_('COM_CLUSTER_LOG_FILE') . '</a>';
			$logLink =	Text::sprintf('COM_CLUSTER_LOG_FILE_PATH', $logLink);

			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_cluster/tables');

			if (!empty($masterData))
			{
				$user = Factory::getUser();
				$totalRecords = count($masterData);

				foreach ($masterData as $eachType)
				{
					$data = array();

					foreach ($eachType as $key => $value)
					{
						switch ($key)
						{
							case COM_CLUSTER_GROUP_TITLE :

								if (!empty ($value))
								{
									$data['name'] = $value;
								}

							break;

							case COM_CLUSTER_GROUP_EMAIL :

								if (!empty ($value))
								{
									$userModel = BaseDatabaseModel::getInstance('Users', 'UsersModel', array('ignore_request' => true));
									$userModel->setState('filter.search', $value);
									$userData = $userModel->getItems();

									$usersdata['user_id'] = $userData[0]->id;
								}

							break;

							case COM_CLUSTER_CLIENT :

								if (!empty ($value))
								{
									$data['client'] = $value;
								}

							break;

							case COM_CLUSTER_CLIENT_ID :

								if (!empty ($value))
								{
									$data['client_id'] = $value;
								}

							break;

							case COM_CLUSTER_GROUP_ID :

								if (!empty ($value))
								{
									$usersdata['cluster_id'] = $value;
								}

							break;

							case COM_CLUSTER_CLUSTER_STATE :

								if (!empty ($value))
								{
									$data['state'] = $value;
								}

							break;

							case COM_CLUSTER_CLUSTERUSERS_STATE :

								if (!empty ($value))
								{
									$usersdata['state'] = $value;
								}

							break;

							case COM_CLUSTER_CLUSTER_CREATED_BY :

								if (!empty ($value))
								{
									$data['created_by'] = $value;
								}

							break;

							case COM_CLUSTER_CLUSTERUSERS_CREATED_BY :

								if (!empty ($value))
								{
									$usersdata['created_by'] = $value;
								}

							break;

							case COM_CLUSTER_CLUSTER_MODIFIED_BY :

								if (!empty ($value))
								{
									$data['modified_by'] = $value;
								}

							break;

							case COM_CLUSTER_CLUSTERUSERS_MODIFIED_BY :

								if (!empty ($value))
								{
									$usersdata['modified_by'] = $value;
								}

							break;
						}
					}

					if (!in_array($key, $column))
					{
						$output['returns'] = 0;
						$output['msg'] = Text::sprintf('COM_CLUSTER_INCORRECT_COLUMN_CSV_ERROR', $key);

						return $output;
					}

					// If csv data missing
					if (empty($data['name']) || (empty($usersdata['cluster_id']) && empty($data['client']) &&  empty($data['email']) && empty($data['client_id'])))
					{
						$missingDetails++;
					}

					// Save cluster
					$clusterModel = BaseDatabaseModel::getInstance('Cluster', 'ClusterModel', array('ignore_request' => true));
					$clusterModel->save($data);

					if (empty($usersdata['cluster_id']))
					{
						$clusterTable = Table::getInstance('Clusters', 'ClusterTable');
						$clusterTable->load(array('name' => $data['name']));

						$usersdata['cluster_id'] = $clusterTable->id;
					}

					$clusterUserModel = BaseDatabaseModel::getInstance('ClusterUser', 'ClusterModel', array('ignore_request' => true));

					if ($clusterUserModel->save($usersdata))
					{
						$success ++;
						$msg = 'COM_CLUSTER_NEW_USER_ADDED';
						Log::add(Text::sprintf($msg, $usersdata['user_id']), Log::INFO, 'com_cluster');
					}
					else
					{
						$failed ++;
						$msg = 'COM_CLUSTER_ALREADY_EXIST';
						Log::add(Text::sprintf($msg, $usersdata['user_id']), Log::ERROR, 'com_cluster');
					}
				}
			}
		}
		else
		{
			$output['returns'] = 0;
			$output['msg'] = Text::sprintf('COM_CLUSTER_FILE_READ_ERROR');
		}

		if ($missingDetails > 0)
		{
			$message = ($missingDetails == 1) ? 'COM_CLUSTER_CSV_MANDATORY_FIELDS_ONE' : 'COM_CLUSTER_CSV_MANDATORY_FIELDS_MULTIPLE';
			array_push($messages, array('error' => Text::sprintf($message, $missingDetails)));

			$output['msg'] = $messages;
		}

		if ($success > 0 || $failed > 0)
		{
			$output['returns'] = 1;
			$output['msg'] = Text::sprintf('COM_CLUSTER_RECORDS_IMPORT_SUCCESSFULLY', $success) .
			Text::sprintf('COM_CLUSTER_RECORDS_IMPORT_FAILED', $failed) . ' ' . $logLink;
		}

		return $output;
	}

	/**
	 * Download log on import users.
	 *
	 * @return  mixed
	 *
	 * @since   1.0.0
	 */
	public function downloadLog()
	{
		$user    = Factory::getUser();
		$canImportdata = $user->authorise('core.import', 'com_cluster');

		$app = Factory::getApplication();

		if (!$canImportdata)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->redirect(Route::_(Uri::base()));

			return false;
		}

		jimport('joomla.filesystem.file');
		$prefix   = $app->input->get('prefix', '', 'string');

		$session  = Factory::getSession();
		$config   = Factory::getConfig();

		$filename = $session->get($prefix);

		$file = $config->get('log_path') . '/' . $filename;

		if (!empty($filename) && File::exists($file))
		{
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="' . basename($file) . '"');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			readfile($file);
		}
		else
		{
			$app->redirect(Route::_(Uri::base()));
		}
	}
}
