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
	retValue['report'] = $('#report').val();
	if (retValue['report'] == 'upload') {
		retValue['file'] = document.getElementById('file').files[0];
	} else {
		retValue['name'] = $('#name').val();
		retValue['fromEmail'] = $('#fromEmail').val();
	}
	return retValue;
}
function onSuccess(retValue) {
	if (retValue == 'ok') {
		if ($('#report').val() == 'upload') {
			retValue = 'You have updated the participant list database successfully.';
		} else {
			retValue = 'The <b>' + $('#report').val() + '</b> email template has been sent to <b>' + $('#fromEmail').val() + '</b>. Please check your email for further instructions.';
		}
	}
	$('#errorAnchor').html('<span>' + retValue + '</span>');
	$('#report').val('upload');
}

function openParticipantEditor(id, passkey) {
	var prefix = (id == 0) ? URLwithPassword : URL;
	window.open(prefix + id + '&passkey=' + passkey, '_blank');
}

function eventHandlers() {
	$("#participant").autocomplete({
		source: participant,
		focus: function( event, ui ) {
			$("#participant").val(ui.item.label);
			return false;  
		},
		select: function( event, ui ) {
			event.preventDefault();
			var payload = ui.item.value.split('_', 2);
			openParticipantEditor(payload[0], payload[1]);
		}
	});

	return {
		'invitation': function() {
			clickLink('Invitation');
		},
		'logistics': function() {
			clickLink('Logistics');
		},
		'addParticipant': function() {
			openParticipantEditor(0, '');
		},
	};
}
function clickLink(linkName) {
	$('#report').val(linkName.toLowerCase());
	submitHandler(fieldsToUpload, onSuccess, 'spinner' + linkName);
}

function formDefaultValues() {
	return {};
}