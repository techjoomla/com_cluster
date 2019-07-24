var clusterImport = {
validateImport: function (thisfile, loaderId) {
		let obj = jQuery(thisfile);
		obj.closest('.controls').children('.statusbar').remove();

		let format_lesson_form = obj.closest('form');

		/* Hide all alerts msgs */
		let status = new clusterImport.createStatusbar(obj); //Using this we can set progress.

		/* Get uploaded file object */
		let uploadedfile = obj[0].files[0];

		if (uploadedfile == undefined)
		{
			alert(Joomla.Text._('COM_CLUSTER_PLEASE_SELECT_FILE'));
			return false;
		}

		/* Get uploaded file name */
		let filename = uploadedfile.name;

		/* pop out extension of file*/
		let ext = filename.split('.').pop().toLowerCase();
		let fileExt = filename.split('.').pop();

		if (fileExt != 'csv')
		{
			let finalMsg = new Object();
			finalMsg['errormsg'] = Joomla.Text._('COM_CLUSTER_CSV_FILE_UPLOAD_ERROR');
			status.setMsg(finalMsg);
			jQuery('.fileupload-preview').empty();
			jQuery('#csv-upload').val('');

			window.parent.SqueezeBox.close();
			return false;
		}

		/* IF evrything is correct so far, popolate file name in fileupload-preview*/

		let file_name_container = jQuery(".fileupload-preview", obj.closest('.fileupload-new'));

		jQuery(file_name_container).show();
		jQuery(file_name_container).text(filename);

		clusterImport.startImporting(uploadedfile, status, thisfile, loaderId);
	},
	createStatusbar: function (obj) {
		this.statusbar = jQuery("<div class='statusbar'></div>");
		this.filename = jQuery("<div class='filename'></div>").appendTo(this.statusbar);
		this.size = jQuery("<div class='filesize'></div>").appendTo(this.statusbar);
		this.success = jQuery('<div class=""></div>').appendTo(this.statusbar);
		this.error = jQuery('<div class=""></div>').appendTo(this.statusbar);

		obj.closest('.controls').append(this.statusbar);

		this.setFileNameSize = function(name, size)
		{
			var sizeStr = "";
			var sizeKB = size/1024;
			if(parseInt(sizeKB) > 1024)
			{
				var sizeMB = sizeKB/1024;
				sizeStr = sizeMB.toFixed(2)+" MB";
			}
			else
			{
				sizeStr = sizeKB.toFixed(2)+" KB";
			}

			this.filename.html(name);
			this.size.html(sizeStr);
		}
		this.setMsg = function(msg)
		{
			this.statusbar.show();

			if(msg['errormsg'])
			{
				Joomla.renderMessages({"error":[msg.errormsg]});
				window.parent.SqueezeBox.close();
			}

			if(msg['successmsg'])
			{
				Joomla.renderMessages({"success":[msg.successmsg]});
				window.parent.SqueezeBox.close();
			}

			if(msg['messages'])
			{
				var message = jQuery('<div>').addClass('import-messages');
				this.success.removeClass('msg alert');

				jQuery.each(msg['messages'], function(i, value){
					var key = Object.keys(value)[0];
					var curMessage = jQuery('<div>').addClass('alert alert-' + key).html(value[key]).get(0);
					message.append(curMessage);
				});

				this.success.html(message);
				this.success.show();
			}
		}
	},
	startImporting: function (file, status, thisfile, loaderId) {
	var finalMsg = new Object();
		if(file == undefined)
		{
			finalMsg['errormsg'] = file_not_selected_error;
			status.setMsg(finalMsg);
			return false;
		}

		var filename = file.name;

		if(window.FormData !== undefined)  // for HTML5 browsers
		{
			var newfilename = clusterImport.sendFileToServer(file, status, thisfile, loaderId);
		}
		else
		{
			alert(Joomla.Text._('COM_CLUSTER_PLEASE_UPGRADE_YOUR_BROWSER'));
		}
	},

	sendFileToServer: function (file, status, fileinputtag, loaderId) {
	var formData = new FormData();
	formData.append( 'FileInput', file );

	var returnvar = true;
	var jqXHR = jQuery.ajax({
		 xhr: function() {
			var xhrobj = jQuery.ajaxSettings.xhr();
			if (xhrobj.upload) {
				xhrobj.upload.addEventListener('progress', function(event) {
					var percent = 0;
					var position = event.loaded || event.position;
					var total = event.total;
					if (event.lengthComputable) {
						percent = Math.ceil(position / total * 100);
					}
				}, false);
			}
			return xhrobj;
		},
		url:Joomla.getOptions('system.paths').base +'/index.php?option=com_cluster&task=import.csvImport&format=json',
		type:'POST',
		data:formData,
		mimeType:"multipart/form-data",
		contentType: false,
		dataType:'json',
		cache: false,
		processData:false,
		success: function(response)
		{
			var output = response['OUTPUT'];
			var result = output['flag'];
			var finalMsg = new Object();

			/* File uploading on local is done*/
			if (result == 0)
			{
				finalMsg['errormsg'] = output['msg'];
				status.setMsg(finalMsg);
				jQuery('.fileupload-preview').empty();
			}
			else
			{
				finalMsg['successmsg'] = output['msg'];
				status.setMsg(finalMsg);
			}

			jQuery('#csv-upload').val('');
		},
		error: function(jqXHR, textStatus, errorThrown)
		{
			finalMsg['errormsg'] = jqXHR.responseText;
			status.setMsg(finalMsg);
			returnvar = false;
		}
   });

	return returnvar;
	status.setAbort(jqXHR);
	}
}
