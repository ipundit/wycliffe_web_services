/*********************************************************************************************************
 * formSetup.js will setup:                                                                              *
 * 1) Some useful string functions                                                                       *
 * 2) The input validators according to validatorRules() and validatorMessages() callbacks.                    *
 * 3) The submit handler given fieldsToUpload(). The common test and simulate params are initialized     *
 *    from the GET url string for you if you don't initialize them yourself.                             *
 *    onSuccess() is called if the form submission is successful.                                        *
 *    Assumes that you have elements id=errorAnchor and id=spinner to show the error messages and submit *
 *    spinner respectively.                                                                              *
 * 4) The event handlers by just passing control back to your setupEventHandlers() callback function     *
 *********************************************************************************************************/

$(document).ready(function() {
	setupStringPrototypes();
	fillTestForm(testFields());
	setupValidators(validatorRules(), validatorMessages(), fieldsToUpload, onSuccess);
	setupEventHandlers();
});

function setupValidators(rules, messages, fieldsToUploadCallback, onSuccessCallback) {
	$.validator.messages.required = "Please enter your {1}."

	$.validator.addMethod("noAngleBrackets", function(value, element, params) { 
		return value.indexOf("<") == -1 && value.indexOf(">") == -1;
	}, "{0} cannot contain the < or > characters.");

	var validator = $("#theForm").validate({
		errorPlacement: function(error, element) {
			error.insertAfter("#errorAnchor");
		},
		rules: rules,
		messages: messages,
		submitHandler: function(form) {
			submitHandler(form, fieldsToUploadCallback, onSuccessCallback);
		}
	});
	translateErrorMessages(validator.settings);
}

function translateErrorMessages(settings) {
	for (customValidator in $.validator.messages) {
		var template = $.validator.messages[customValidator];
		if (typeof(template) == 'string' && template.indexOf('{') == -1) { continue; }
		
		for (key in settings.rules) {
			if (undefined !== settings.rules[key][customValidator] && undefined === settings.messages[key]) {
				var variable = settings.rules[key][customValidator];
				var str = $.validator.format(template, variable);

				settings.messages[key] = {};
				settings.messages[key][customValidator] = g_translations[str];
			}
		}
	}
}

function fillTestForm(fields) {
	if (fields === undefined || fields == null || urlParam("test") != 1) { return; }
	
	footer = fields['footer'];
	if (undefined !== footer) {
		delete fields['footer'];
		$('#footer').after(footer);
	}
	
	for (var key in fields) {
		$('#' + key).val(fields[key]);
	}
}

function submitHandler(form, fieldsToUploadCallback, onSuccessCallback) {
	if ($('#spinner').css('display') == 'block') { return; } // Prevent form from being submitted twice
	$('#errorAnchor > span').html(''); // Clear error messages
	$('#spinner').css('display', 'inline-block');

	fields = fieldsToUploadCallback();
	if (fields['test'] === undefined) {
		var test = urlParam("test");
		if (test != 1) { test = 0; }
		fields['test'] = test;
	}
	if (fields['simulate'] === undefined) {
		var simulate = urlParam("simulate");
		if (simulate != 1) { simulate = 0; }
		fields['simulate'] = simulate;
	}

	var data = new FormData();
	for (var key in fields) {
		data.append(key, fields[key]);
	}
	
	$.ajax({
		type: 'POST',
		url: 'webservice.php',
		data: data,
		success: function(retValue, textStatus) {
			$('#spinner').hide();
			onSuccessCallback(retValue);
		},
		error: function(XMLHttpRequest, textStatus, errorThrown) {
			$('#spinner').hide();
			$('#errorAnchor').html('<span style="color:red">' + XMLHttpRequest.statusText.removeBefore('(0)') + '</span>');
		},
		// Required options for file uploading to work
		cache: false,
		contentType: false,
		processData: false
	});
}

function setupStringPrototypes() {
	if (typeof String.prototype.startsWith != 'function') {
		String.prototype.startsWith = function (str) {
			return this.slice(0, str.length) == str;
		};
	}
	if (typeof String.prototype.removeBefore != 'function') {
		String.prototype.removeBefore = function (needle) {
			var index = this.indexOf(needle);
			if (index == -1) { return this; }
			return this.substring(index + needle.length).ltrim();
		};
	}
	if (typeof String.prototype.capitalizeFirstLetter != 'function') {
		String.prototype.capitalizeFirstLetter = function (str) {
			return this.charAt(0).toUpperCase() + this.slice(1);
		};
	}
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