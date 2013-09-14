document.write('<script type="text/javascript" src="../../formSetup.js" ></script>');

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
	return {};
}
function validatorMessages() {
	return {};
}

function fieldsToUpload() {
	var retValue = [];
	return retValue;
}
function onSuccess(retValue) {
	$('#errorAnchor').html('<span>' + retValue + '</span>');
}

function eventHandlers() {
	return {};
}

function formDefaultValues() {
	var retValue = {};
	return retValue;
}
