<?php
/**
* @package    Cluster
* @author     Techjoomla <contact@techjoomla.com>
* @copyright  Copyright (C) 2012-2019 Techjoomla. All rights reserved.
* @license    GNU General Public License version 2 or later; see LICENSE.txt
*/
// No direct access
defined('_JEXEC') or die;
use Joomla\CMS\Factory;

$jinput = Factory::getApplication()->input;
$view = $jinput->get('view', '', 'string');

if ($view == 'clusters')
{
	$filepath = JUri::root() . 'administrator/components/com_cluster/csv/clusterDetails.csv';
}
?>
<div class="mt-20 ml-20 mr-20">
	<form method="post" id="adminForm" name="adminForm">
		<div class="ques-container-csv p-20">
			<div class="csv-import" >
				<div class="span9">
					<div class="input-append">
						<div class="uneditable-input span4">
							<span class="fileupload-preview">
								<?php echo JText::_("COM_CLUSTER_CSV_UPLOAD_FILE");?>
							</span>
						</div>
						<span class="btn btn-file">
							<span class="fileupload-new"></span>
							<input type="file"  id="csv-upload" name="csv-upload"
							onchange="jQuery('.fileupload-preview').html(jQuery(this)[0].files[0].name);">
						</span>
					</div>
					<button class="btn btn-primary mb-10" id="upload-submit"
					onclick="clusterImport.validateImport(document.getElementById('upload-submit').
					form['csv-upload'],'1'); return false;">
					<span class="icon-upload icon-white"></span> <?php echo JText::_("COM_CLUSTER_START_UPLOAD_CSV");?>
					</button>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php echo JHtml::_('form.token'); ?>
	</form>
</div>
