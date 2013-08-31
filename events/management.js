document.write('<script type="text/javascript" src="../formSetup.js" ></script>');

var g_translations;

function index_js_init(translationMapping) {
	g_translations = translationMapping;
}

/*****************************************************************************************************
 * The rest of the file are callbacks called by formSetup.js using the Template Method pattern. See  *
 * formSetup.js for more details of how it peforms most of the tasks of initializing, validating and *
 * submitting a form for you.                                                                        *
 *****************************************************************************************************/

function validatorRules() {
	return {
		file:{
			isCSV: ['file']
		}
	};
}
function validatorMessages() {
	return {
		file:{
			isCSV: g_translations["Please choose a mailing list template .csv file."]
		}
	};
}

function fieldsToUpload() {
	var retValue = [];
	retValue['eventName'] = $('#eventName').val();
	retValue['userName'] = $('#userName').val();
	retValue['password'] = $('#password').val();
	retValue['report'] = 'upload';
	retValue['file'] = document.getElementById('file').files[0];
	return retValue;
}
function onSuccess(retValue) {
	$('#errorAnchor').html('<span>' + retValue + '</span>');
}

function eventHandlers() {
	return {
		'downloadLink': function() {
			$('#downloadRadio').prop('checked', true);
		},
	};
}

function formDefaultValues() {
	return {};
}