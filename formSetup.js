/*********************************************************************************************************
 * formSetup.js will setup:                                                                              *
 * 1) Some useful string functions                                                                       *
 * 2) The event handlers through the eventHandlers() callback function                                   *
 * 3) The input validators according to validatorRules() and validatorMessages() callbacks.              *
 * 4) The submit handler given fieldsToUpload(). The common test and simulate params are initialized     *
 *    from the GET url string for you if you don't initialize them yourself.                             *
 *    onSuccess() is called if the form submission is successful.                                        *
 *    Assumes that you have elements id=errorAnchor and id=spinner to show the error messages and submit *
 *    spinner respectively.                                                                              *
 *********************************************************************************************************/

$(document).ready(function() {
	setupStringPrototypes();
	setupEventHandlers(eventHandlers());
	setupForm(formDefaultValues());
	setupValidators(validatorRules(), validatorMessages(), fieldsToUpload, onSuccess);
});

function setupValidators(rules, messages, fieldsToUploadCallback, onSuccessCallback) {
	$.validator.messages.required = "Please enter your {1}."

	$.validator.addMethod("radioChecked", function(value, element, param) { 
		return (param != null && !$(param).prop('checked'));
	}, "{0} radio must be checked.");

	
	$.validator.addMethod("noAngleBrackets", function(value, element, params) { 
		if ($.validator.methods.radioChecked.call(this, value, element, params[1])) { return true; }
		return value.indexOf("<") == -1 && value.indexOf(">") == -1;
	}, "{0} cannot contain the < or > characters.");

	$.validator.addMethod("isCSV", function(value, element, params) {
		if ($.validator.methods.radioChecked.call(this, value, element, params[1])) { return true; }
		value = value.trim();
		if (value.length == 0) { return false; }
		return value.endsWith('.csv');
	}, "Please choose a .csv file.");

	$.validator.addMethod("isTXT", function(value, element, params) {
		if ($.validator.methods.radioChecked.call(this, value, element, params[1])) { return true; }
		value = value.trim();
		if (value.length == 0) { return false; }
		return value.endsWith('.txt');
	}, "Please choose a .txt file.");

	var validator = $("#theForm").validate({
		errorPlacement: function(error, element) {
			error.insertAfter("#errorAnchor");
		},
		rules: rules,
		messages: messages,
		submitHandler: function(form) {
			submitHandler(fieldsToUploadCallback, onSuccessCallback, "spinner");
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

function setupEventHandlers(lookup) {
	if (undefined === lookup) { return; }
	
	for (key in lookup) {
		var payload = lookup[key];
		var theCommand;
		if (Object.prototype.toString.call(payload) === '[object Array]') {
			theCommand = payload[0];
		} else {
			theCommand = 'click';
		}
		
		switch (theCommand) {
		case 'click':
			$('#' + key).click(function() { formEventHandler(lookup[this.id]); });
			break;
		case 'change':
			$('#' + key).change(function() { formEventHandler(lookup[this.id]); });
			break;
		default:
			alert(theCommand + ' is not supported');
		}
	}
}

function formEventHandler(payload) {
	var command;
	if (Object.prototype.toString.call(payload) === '[object Array]') {
		command = payload[1];
	} else {
		command = payload;
	}
	
	if (typeof command == 'function') {
		command();
	} else {
		selectRadio(command);
	}
}

function selectRadio(name) {
	$('#' + name).prop('checked', true);
}
function setupForm(fields) {
	if (fields === undefined || fields == null) { return; }
	
	footer = fields['footer'];
	if (undefined !== footer) {
		delete fields['footer'];
		$('#footer').after(footer);
	}
	
	for (var id in fields) {
		var value = urlParam(id);
		var element = $('#'+ id);
		var elementType = element.get(0).type;
		
		if (value === undefined) { 
			if (elementType == 'checkbox' || elementType == 'radio') {
				element.prop('checked', fields[id] == 1);
			} else {
				if (element.val() == '' || elementType == 'select-one') { element.val(fields[id]); }
			}
		} else {
			if (elementType == 'checkbox' || elementType == 'radio') {
				element.prop('checked', value == 1);
			} else {
				element.val(value);
			}
		}
	}
}

function submitHandler(fieldsToUploadCallback, onSuccessCallback, spinnerName) {
	if ($('#spinner').css('display') == 'block') { return; } // Prevent form from being submitted twice
	$('#errorAnchor > span').html(''); // Clear error messages
	$('#' + spinnerName).css('display', 'inline-block');

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

	var theURL = (typeof webserviceURL == 'function') ? webserviceURL() : 'webservice.php';
	$.ajax({
		type: 'POST',
		url: theURL,
		data: data,
		success: function(retValue, textStatus) {
			$('#' + spinnerName).hide();
			onSuccessCallback(retValue);
		},
		error: function(XMLHttpRequest, textStatus, errorThrown) {
			$('#' + spinnerName).hide();
			$('#errorAnchor').html('<span>' + XMLHttpRequest.statusText.removeBefore('(0)') + '</span>');
		},
		// Required options for file uploading to work
		cache: false,
		contentType: false,
		processData: false
	});
}

function setupStringPrototypes() {
	if (typeof String.prototype.startsWith != 'function') {
		String.prototype.startsWith = function (prefix) {
			return this.slice(0, prefix.length) == prefix;
		};
	}
	
	if (typeof String.prototype.endsWith != 'function') {
		String.prototype.endsWith = function (suffix) {
			return this.indexOf(suffix, this.length - suffix.length) !== -1;
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