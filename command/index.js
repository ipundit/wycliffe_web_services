document.write('<script type="text/javascript" src="../formSetup.js" ></script>');

var g_translations;

function index_js_init(translationMapping) {
	var g_translations = translationMapping;
}

/*****************************************************************************************************
 * The rest of the file are callbacks called by formSetup.js using the Template Method pattern. See  *
 * formSetup.js for more details of how it peforms most of the tasks of initializing, validating and *
 * submitting a form for you.                                                                        *
 *****************************************************************************************************/

function validatorRules() {
	return {
		commandFile:{
			isCSV: ['Command file', '#choiceFile']
		},
	};
}
function validatorMessages() {
	return {};
}

function fieldsToUpload() {
	var retValue = [];

	switch ($('input[name=choice]:checked', '#theForm').attr('id')) {
	case 'choiceFile':
		retValue['src'] = document.getElementById('commandFile').files[0];
		break;
	case 'choiceService':
		retValue['src'] = $('#service').val();
		break;
	case 'choiceText':
		retValue['src'] = $('#text').val();
		break;
	}
	for (i = 1; i <= 4; i++) {
		if ($('#file' + i).val() != '') { retValue['_file' + i] = document.getElementById('file' + i).files[0]; }
	}
	return retValue;
}
function onSuccess(retValue) {
	$('#errorAnchor').html(retValue);
}

function setupEventHandlers() {
	$('#commandFile').click(function() { selectRadio('choiceFile'); });
	$('#service').click(function() { selectRadio('choiceService'); });
	$('#text').click(function() { selectRadio('choiceText'); });
}

function formDefaultValues() {
	var service = urlParam("service");
	if (service != undefined) {
		return {
			'choiceService': 1,
			'service': service
		};
	}

	if ($('#text').val() != '' && $('#choiceText').prop('checked')) { return {}; }
	if ($('#commandFile').val() != '' && $('#choiceFile').prop('checked')) { return {}; }
	if ($('#text').val() != '') { return { 'choiceText': 1 }; }
	if ($('#commandFile').val() != '') { return { 'choiceFile': 1 }; }
	return {}
}