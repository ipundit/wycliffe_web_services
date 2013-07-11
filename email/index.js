$(document).ready(function() {
	$('#fromNameJAARS').click(function() { selectRadio('choiceJAARS'); });
	$('#fromEmailJAARS').click(function() { selectRadio('choiceJAARS'); });
	$('#fromNameWWS').click(function() { selectRadio('choiceWWS'); });
	$('#fromEmailWWS').click(function() { selectRadio('choiceWWS'); });
	$('#replyTo').click(function() { selectRadio('choiceWWS'); });
	
	$('#to').click(function() { selectRadio('choiceEmail'); });
	$('#toEmailFile').click(function() { selectRadio('choiceFile'); });
	$('#startRow').click(function() { selectRadio('choiceFile'); });
	$('#maxRows').click(function() { selectRadio('choiceFile'); });
	$('#tags').click(function() { selectRadio('choiceFile'); });

	addValidators();
	addSubmitHandler();
	initForm();
});

function initForm() {
	var from = urlParam("from");
	if (from === undefined) {
		selectRadio('choiceJAARS');
		if ($('#fromEmailJAARS').val() == '') {
			$('#fromEmailJAARS').val('Your JAARS email');
			$('#fromEmailWWS').val('no-reply');
		}
	} else {
		var index = from.indexOf('@wycliffe-services.net');
		if (index == -1) {
			selectRadio('choiceJAARS');
			$('#fromEmailJAARS').val(from);
			$('#fromEmailWWS').val('no-reply');
		} else {
			selectRadio('choiceWWS');

			from = from.substring(0, index);
			$("#fromEmailWWS > option").each(function() {
				if (this.value == from) {
					$(this).prop('selected', true);
					return;
				}
			});
		}
	}
	
	var to = urlParam("to");
	var startRow = urlParam("startRow");
	var maxRows = urlParam("maxRows");
	var tags = urlParam("tags");
	if (to !== undefined || (startRow == undefined && maxRows == undefined && tags == undefined)) { 
		selectRadio('choiceEmail');
	} else {
		selectRadio('choiceFile');
	}

	var defaultValues = {
		'fromName': 'Your name', 
		'to': 'recipient list', 
		'replyTo': 'Your email',
		'startRow': 1,
		'maxRows': 0,
		'tags': '',
		'cc': '',
		'bcc': '',
		'subject': '',
		'body': '',
		'simulate': '0',
	};
	
	for (key in defaultValues) { 
		initFormElement(key, defaultValues[key]);
	}
}
function initFormElement(id, defaultValue) {	
	var value = urlParam(id);
	var element = $('#'+ id);
	
	if (value === undefined) { 
		if (element.get(0).type == 'checkbox') {
			element.prop('checked', defaultValue == 1);
		} else {
			if (element.val() == '') { element.val(defaultValue); }
		}
	} else {
		if (element.get(0).type == 'checkbox') {
			element.prop('checked', value == 1);
		} else {
			element.val(value);
		}
	}
}

function selectRadio(name) {
	$('#' + name).prop('checked', true);
}

function addSubmitHandler() {
	$('button').click(function() {
		if ($('#fromName').val() == 'Your name') { $('#fromName').val(''); }
		if ($('#choiceJAARS').prop('checked') && $('#fromEmailJAARS').val() == 'Your JAARS email') { $('#fromEmailJAARS').val(''); }
		if ($('#choiceWWS').prop('checked') && $('#replyTo').val() == 'Your email') { $('#replyTo').val(''); }
		if ($('#choiceEmail').prop('checked') && $('#to').val() == 'recipient list') { $('#to').val(''); }
	
		$('#error').html('');
		if (!$("#theForm").valid()) { return; }
		$('#spinner').css('display', 'inline-block');

		var data = new FormData();
		data.append('fromName', $('#fromName').val());

		switch ($('input[name=choiceFrom]:checked', '#theForm').attr('id')) {
		case 'choiceJAARS':
			data.append('from', $('#fromEmailJAARS').val());
			break;
		case 'choiceWWS':
			data.append('from', $('#fromEmailWWS').val() + '@wycliffe-services.net');
			data.append('replyTo', $('#replyTo').val());
			break;
		}

		switch ($('input[name=choiceTo]:checked', '#theForm').attr('id')) {
		case 'choiceEmail':
			data.append('to', $('#to').val());
			break;
		case 'choiceFile':
			data.append('to', document.getElementById('toEmailFile').files[0]);
			data.append('startRow', $('#startRow').val());
			data.append('maxRows', $('#maxRows').val());
			data.append('tags', $('#tags').val());
			break;
		}
			
		data.append('cc', $('#cc').val());
		data.append('bcc', $('#bcc').val());
		data.append('subject', $('#subject').val());
		data.append('body', $('#body').val());
		if ($('#simulate').prop('checked')) { data.append('simulate', 1); }

		for (i = 1; i <= 9; i++) {
			if ($('#file' + i).val() != '') { data.append('attach' + i, document.getElementById('file' + i).files[0]); }
		}
		
		$.ajax({
			type: 'POST',
			url: 'webservice.php',
			data: data,
			success: function(retValue, textStatus) {
				$('#spinner').hide();
				$('#error').html(retValue);
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				$('#spinner').hide();
				$('#error').html(removeBefore(XMLHttpRequest.statusText, '(0)'));
			},
			// Required options for file uploading to work
			cache: false,
			contentType: false,
			processData: false
		});
	});
}
function addValidators() {
	$("#theForm").validate({
		errorPlacement: function(error, element) {
			$('#error').html(error.html());
		},
		rules:{
			fromName:{
				noAngleBrackets: ['From', null]
			},
			fromEmailJAARS:{
				dependentEmail: ['From email', '#choiceJAARS', true]
			},
			replyTo:{
				dependentEmail: ['Reply-to', '#choiceWWS', false]
			},
			to:{
				dependentEmailList: ['To', '#choiceEmail', true]
			},
			toEmailFile:{
				isCSV: ['To', '#choiceFile']
			},
			startRow:{
				dependentMinInt: ['Start row', '#choiceFile', 1]
			},
			maxRows:{
				dependentMinInt: ['Max rows', '#choiceFile', 0]
			},
			cc:{
				dependentEmailList: ['Cc', null, false]
			},
			bcc:{
				dependentEmailList: ['Bcc', null, false]
			},
			subject:{
				noAngleBrackets: ['Subject', null]
			},
			tags:{
				noAngleBrackets: ['Tags', '#choiceFile']
			},
		},
	});
	
	$.validator.addMethod("radioChecked", function(value, element, param) { 
		return (param != null && !$(param).prop('checked'));
	}, "{0} radio must be checked");

	$.validator.addMethod("noAngleBrackets", function(value, element, params) { 
		if ($.validator.methods.radioChecked.call(this, value, element, params[1])) { return true; }
		return value.indexOf("<") == -1 && value.indexOf(">") == -1;
	}, "{0} cannot contain the < or > characters");

	$.validator.addMethod("dependentEmail", function(value, element, params) { 
		if ($.validator.methods.radioChecked.call(this, value, element, params[1])) { return true; }
		if (params[2] && !$.validator.methods.required.call(this, value, element, params)) { return false; }
		return $.validator.methods.email.call(this, value, element, params);
	}, "{0} must be a valid email");

	$.validator.addMethod("dependentEmailList", function(value, element, params) { 
		if ($.validator.methods.radioChecked.call(this, value, element, params[1])) { return true; }
		if (params[2] && !$.validator.methods.required.call(this, value, element, params)) { return false; }
		if (value == '') { return true; }
		
		// John Doe <test@test.com>, test@test.com
		var array = value.split(',');
		for (var i = 0; i < array.length; i++) {
			var entry = array[i].trim();
			if (endsWith(entry, '>')) {
				entry = entry.substring(entry.indexOf("<") + 1).slice(0, -1);
			}
			if (!$.validator.methods.email.call({optional:function(){return false;}}, entry, null)) { return false; }
		}
		return true;
	}, "{0} must be a valid, comma separated email list");

	$.validator.addMethod("dependentMinInt", function(value, element, params) { 
		if ($.validator.methods.radioChecked.call(this, value, element, params[1])) { return true; }
		if (!$.validator.methods.required.call(this, value, element, params)) { return false; }
		if (value != parseInt(value)) { return false; }
		return $.validator.methods.min.call(this, value, element, params[2]);
	}, "{0} must be an integer with a minimum of {2}");

	$.validator.addMethod("isCSV", function(value, element, params) {
		if ($.validator.methods.radioChecked.call(this, value, element, params[1])) { return true; }
		value = value.trim();
		if (value.length == 0) { return false; }
		return value.endsWith('.csv');
	}, translate("Please choose a mailing list template .csv file."));
}

function removeBefore(haystack, needle) {
	var index = haystack.indexOf(needle);
	if (index == -1) { return haystack; }
	return haystack.substring(index + needle.length).ltrim();
}

function urlParam(name) {
	var retValue = getUrlVars()[name];
	if (retValue === undefined) { return retValue; }
	return retValue.replace(/%20/g, ' ');
}
function getUrlVars() {
    var vars = {};
    var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
        vars[key] = value;
    });
    return vars;
}
function endsWith(str, suffix) {
    return str.indexOf(suffix, str.length - suffix.length) !== -1;
}