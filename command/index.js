$(document).ready(function() {
	$('#file').click(function() { $('#choiceFile').prop('checked', true); });
	$('#dir').click(function() { $('#choiceDir').prop('checked', true); });
	$('#text').click(function() { $('#choiceText').prop('checked', true); });
	addValidators();
	addSubmitHandler();
});

function addSubmitHandler() {
	$('button').click(function() {
		$('#error').html('');
		if (!$("#theForm").valid()) { return; }
		$('#spinner').css('display', 'inline-block');

		var data = new FormData();
		switch ($('input[name=src]:checked', '#theForm').attr('id')) {
		case 'choiceFile':
			data.append('file', document.getElementById('file').files[0]);
			break;
		case 'choiceService':
			data.append('service', $('#service').val());
			break;
		case 'choiceText':
			data.append('commands', $('#text').val());
			break;
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
		}
	});

	$.validator.addMethod("isCSV", function(value, element, param) {
		if (!$('#choiceFile').prop('checked')) { return true; }
		value = value.trim();
		if (value.length == 0) { return false; }
		return value.endsWith('.csv');
	}, translate("Please choose a .csv file"));
	$("#file").rules("add", {
		isCSV: true
	});
}

function removeBefore(haystack, needle) {
	var index = haystack.indexOf(needle);
	if (index == -1) { return haystack; }
	return haystack.substring(index + needle.length).ltrim();
}