document.write('<script src="../formSetup.js" type="text/javascript"></script>');

var g_translations;

function index_js_init(translationMapping) {
	var g_translations = translationMapping;

	$.validator.addMethod("isCSV", function(value, element, param) {
		if (!$('#choiceFile').prop('checked')) { return true; }
		value = value.trim();
		if (value.length == 0) { return false; }
		return value.endsWith('.csv');
	}, "Please choose a .csv file");
}

function preselectRadio() {
	var service = urlParam("service");
	if (service == undefined || $('#text').val() != '') { 
		selectRadio('choiceText');
		return;
	}

	selectRadio('choiceService');
	$("#service > option").each(function() {
		if (this.value == service) {
			$(this).prop('selected', true);
			return;
		}
	});
}
function selectRadio(name) {
	$('#' + name).prop('checked', true);
}

/*****************************************************************************************************
 * The rest of the file are callbacks called by formSetup.js using the Template Method pattern. See  *
 * formSetup.js for more details of how it peforms most of the tasks of initializing, validating and *
 * submitting a form for you.                                                                        *
 *****************************************************************************************************/

function validatorRules() {
	return {
		commandFile:{
			isCSV: true,
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
	preselectRadio();
}

function testFields() {
	return {}
}