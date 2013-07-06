$(document).ready(function() {
	$('#commandFile').click(function() { selectRadio('choiceFile'); });
	$('#service').click(function() { selectRadio('choiceService'); });
	$('#text').click(function() { selectRadio('choiceText'); });
	addValidators();
	addSubmitHandler();
	preselectRadio();
});

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

function addSubmitHandler() {
	$('button').click(function() {
		$('#error').html('');
		if (!$("#theForm").valid()) { return; }
		$('#spinner').css('display', 'inline-block');

		var data = new FormData();
		switch ($('input[name=choice]:checked', '#theForm').attr('id')) {
		case 'choiceFile':
			data.append('src', document.getElementById('commandFile').files[0]);
			break;
		case 'choiceService':
			data.append('src', $('#service').val());
			break;
		case 'choiceText':
			data.append('src', $('#text').val());
			break;
		}
		for (i = 1; i <= 4; i++) {
			if ($('#file' + i).val() != '') { data.append('_file' + i, document.getElementById('file' + i).files[0]); }
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
	$("#commandFile").rules("add", {
		isCSV: true
	});
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