document.write('<script type="text/javascript" src="../formSetup.js" ></script>');

var g_translations;

function index_js_init(translationMapping) {
	g_translations = translationMapping;
	
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
			if (entry.endsWith('>')) {
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
}

function chooseToOption() {
	var to = urlParam("to");
	if (to !== undefined) { return 'choiceEmail'; }
	
	var hasFileListParam = urlParam("startRow") != undefined || urlParam("maxRows") != undefined || urlParam("tags") != undefined;
	if (hasFileListParam) { return 'choiceFile'; }
	
	to = $('#to').val();
	if (to == 'recipient list') { to = ''; }
	
	startRow = $('#startRow').val(); if (startRow == 1) { startRow = ''; }
	maxRows = $('#maxRows').val(); if (maxRows == 0) { maxRows = ''; }
	tags = $('#tags').val();
	hasFileListParam = startRow != '' || maxRows != '' || tags != '';

	if (to == '') {
		if (hasFileListParam) { return 'choiceFile'; }
		return '';
	}
	return hasFileListParam ? '' : 'choiceEmail';
}

/*****************************************************************************************************
 * The rest of the file are callbacks called by formSetup.js using the Template Method pattern. See  *
 * formSetup.js for more details of how it peforms most of the tasks of initializing, validating and *
 * submitting a form for you.                                                                        *
 *****************************************************************************************************/

function validatorRules() {
	return {
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
		}
	};
}
function validatorMessages() {
	return {
		toEmailFile:{
			isCSV: g_translations["Please choose a mailing list template .csv file."]
		}
	};
}

function fieldsToUpload() {
	if ($('#fromName').val() == 'Your name') { $('#fromName').val(''); }
	if ($('#choiceJAARS').prop('checked') && $('#fromEmailJAARS').val() == 'Your JAARS email') { $('#fromEmailJAARS').val(''); }
	if ($('#choiceWWS').prop('checked') && $('#replyTo').val() == 'Your email') { $('#replyTo').val(''); }
	if ($('#choiceEmail').prop('checked') && $('#to').val() == 'recipient list') { $('#to').val(''); }

	var retValue = [];
	retValue['fromName'] = $('#fromName').val();

	switch ($('input[name=choiceFrom]:checked', '#theForm').attr('id')) {
	case 'choiceJAARS':
		retValue['from'] = $('#fromEmailJAARS').val();
		break;
	case 'choiceWWS':
		retValue['from'] = $('#fromEmailWWS').val() + '@wycliffe-services.net';
		retValue['replyTo'] = $('#replyTo').val();
		break;
	}

	switch ($('input[name=choiceTo]:checked', '#theForm').attr('id')) {
	case 'choiceEmail':
		retValue['to'] = $('#to').val();
		break;
	case 'choiceFile':
		retValue['to'] = document.getElementById('toEmailFile').files[0];
		retValue['startRow'] = $('#startRow').val();
		retValue['maxRows'] = $('#maxRows').val();
		retValue['tags'] = $('#tags').val();
		break;
	}
		
	retValue['cc'] = $('#cc').val();
	retValue['bcc'] = $('#bcc').val();
	retValue['subject'] = $('#subject').val();
	retValue['body'] = $('#body').val();
	retValue['simulate'] = $('input[name=choiceSimulate]:checked', '#theForm').val();

	for (i = 1; i <= 9; i++) {
		if ($('#file' + i).val() != '') { retValue['attach' + i] = document.getElementById('file' + i).files[0]; }
	}
	return retValue;
}
function onSuccess(retValue) {
	$('#errorAnchor').html('<span>' + retValue + '</span>');
}

function eventHandlers() {
	return {
		'fromNameJAARS': 'choiceJAARS',
		'fromEmailJAARS': 'choiceJAARS',
		'fromNameWWS': 'choiceWWS',
		'fromEmailWWS': 'choiceWWS',
		'replyTo': 'choiceWWS',
		'to': 'choiceEmail',
		'toEmailFile': 'choiceFile',
		'startRow': 'choiceFile',
		'maxRows': 'choiceFile',
		'tags': 'choiceFile',
	};
}

function formDefaultValues() {
	var retValue = {
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
	};

	var from = urlParam("from");
	if (from === undefined) {
		var fromEmail = $('#fromEmailJAARS').val();
		if (fromEmail == 'Your JAARS email') { fromEmail = ''; }
		
		var replyTo = $('#replyTo').val();
		if (replyTo == 'Your email') { replyTo = ''; }
		
		if (fromEmail == '') {
			retValue['fromEmailJAARS'] = 'Your JAARS email';
			retValue['fromEmailWWS'] = 'no-reply';
			if (replyTo != '') { retValue['choiceWWS'] = 1;	}
		} else {
			if (replyTo == '') { retValue['choiceJAARS'] = 1; }
		}
	} else {
		var index = from.indexOf('@wycliffe-services.net');
		if (index == -1) {
			retValue['choiceJAARS'] = 1;
			retValue['fromEmailJAARS'] = from;
			retValue['fromEmailWWS'] = 'no-reply';
		} else {
			retValue['choiceWWS'] = 1;
			retValue['fromEmailWWS'] = from.substring(0, index);
		}
	}

	var id = chooseToOption();
	if (id != '') { retValue[id] = 1; }
	return retValue;
}