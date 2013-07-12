$(document).ready(function() {
	fillTestForm();
	$('#country').change(function() { fireCountryChange(); });
	addValidators();
	addDonateClickHandler();
	fireCountryChange();
});

function fireCountryChange() {
	var hasState = true;
	var hasCity = true;
	if ($('#country').val() == 'SG') {
		hasState = false;
		hasCity = false;
	}
	if (hasState) {
		$('#state').slideDown();
		$('#lblState').slideDown();
	} else {
		$('#state').slideUp();
		$('#lblState').slideUp();
	}
	if (hasCity) {
		$('#city').slideDown();
		$('#lblCity').slideDown();
	} else {
		$('#city').slideUp();
		$('#lblCity').slideUp();
	}
}

function addDonateClickHandler() {
	$('#donate').click(function() {
		
		if (!$("#theForm").valid()) { return; }
		$('#spinner').css('display', 'inline-block');

		var test = urlParam("test");
		if (test != 1) { test = 0; }
		
		var simulate = urlParam("simulate");
		if (simulate != 1) { simulate = 0; }

		var country = $('#country').val();
		var state = (country == 'SG') ? '--' : $('#state').val();
		var city = (country == 'SG') ? '--' : $('#city').val();
		
		var data = new FormData();
		data.append('name', $('#name').val());
		data.append('email', $('#email').val());
		data.append('phone', $('#phone').val());
		data.append('country', country);
		data.append('address', $('#address').val());
		data.append('address2', $('#address2').val());
		data.append('state', state);
		data.append('city', city);
		data.append('postalCode', $('#postalCode').val());
		data.append('amount', $('#amount').val());
		data.append('project', $('#project').val());
		data.append('cardName', $('#cardName').val());
		data.append('creditCard', $('#creditCard').val().replace(/ /g, "").replace(/\-/, ""));
		data.append('month', $('#month').val());
		data.append('year', $('#year').val());
		data.append('month', $('#month').val());
		data.append('test', test);
		data.append('simulate', simulate);
		data.append('org', g_org);
		
		$.ajax({
			type: 'POST',
			url: 'webservice.php',
			data: data,
			success: function(retValue, textStatus) {
				$('#spinner').hide();
				if (retValue.indexOf('ok') == 0) {
					alert(g_translations["Thank you for your donation."]);
					window.location = g_redirect_url;
				} else {
					$('#instructions').html('<span style="color:red">' + retValue  + '</span>');
				}
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				$('#spinner').hide();
				$('#instructions').html('<span style="color:red">' + removeBefore(XMLHttpRequest.statusText, '(0)') + '</span>');
			},
			// Required options for file uploading to work
			cache: false,
			contentType: false,
			processData: false
		});
	});
}

function removeBefore(haystack, needle) {
	var index = haystack.indexOf(needle);
	if (index == -1) { return haystack; }
	return haystack.substring(index + needle.length).ltrim();
}

function addValidators() {
	$("#theForm").validate({
		errorPlacement: function(error, element) {
			error.insertAfter("#instructions");
		}
	});

	$.validator.addMethod("noAngleBrackets", function(value, element, param) { 
		return value.indexOf("<") == -1 && value.indexOf(">") == -1;
	}, g_translations["Has angle brackets"]);

	$("#name").rules("add", {
		required: true,
		noAngleBrackets: true,
		messages: { 
			required: g_translations["Please enter your name."],
			noAngleBrackets: g_translations["Name cannot contain the < or > characters."]
		}
	});
	$("#phone").rules("add", {
		required: true,
		noAngleBrackets: true,
		messages: { 
			required: g_translations["Please enter your phone number."],
			noAngleBrackets: g_translations["Phone number cannot contain the < or > characters."]
		}
	});
	$("#state").rules("add", {
		required: true,
		noAngleBrackets: true,
		messages: { 
			required: g_translations["Please enter your state."],
			noAngleBrackets: g_translations["State cannot contain the < or > characters."]
		}
	});
	$("#city").rules("add", {
		required: true,
		noAngleBrackets: true,
		messages: { 
			required: g_translations["Please enter your city."],
			noAngleBrackets: g_translations["City cannot contain the < or > characters."]
		}
	});
	$("#address").rules("add", {
		required: true,
		noAngleBrackets: true,
		messages: { 
			required: g_translations["Please enter your address."],
			noAngleBrackets: g_translations["Address cannot contain the < or > characters."]
		}
	});
	$("#address2").rules("add", {
		noAngleBrackets: true,
		messages: { 
			noAngleBrackets: g_translations["Address line 2 cannot contain the < or > characters."]
		}
	});
	$("#postalCode").rules("add", {
		required: true,
		noAngleBrackets: true,
		messages: { 
			required: g_translations["Please enter your postal code."],
			noAngleBrackets: g_translations["Postal code cannot contain the < or > characters."]
		}
	});

	$("#email").rules("add", {
		required: true,
		email: true,
		messages: { 
			required: g_translations["Please enter your email."],
			email: g_translations["Please enter a valid email."]
		}
	});
	$("#email2").rules("add", {
		equalTo: "#email",
		messages: { equalTo: g_translations["Please enter the same email."] }
	});

	$("#amount").rules("add", {
		required: true,
		number: true,
		messages: { 
			required: g_translations["Please enter the amount you want to donate."],
			number: g_translations["Please enter a valid amount to donate."]
		}
	});
	$("#project").rules("add", {
		noAngleBrackets: true,
		messages: { 
			noAngleBrackets: g_translations["Project cannot contain the < or > characters."]
		}
	});
	$("#cardName").rules("add", {
		required: true,
		noAngleBrackets: true,
		messages: { 
			required: g_translations["Please enter your name on card."],
			noAngleBrackets: g_translations["Name on card cannot contain the < or > characters."]
		}
	});

    // Create our own custom validator for CUP credit cards
	jQuery.validator.addMethod("extendedCreditCard", function(value, element) {
		// See if it's a CUP credit card
		if (value.indexOf("62", 0) === 0) {
			// Should only consist of spaces, digits and dashes and be 16-19 digits long (sans dashes and spaces)
			if (/[0-9 \-]+/.test(value)) {
				var digitCount = value.replace(/ /g, "").replace(/\-/, "").length;
				return digitCount >= 16 && digitCount <= 19;
			}
			return false;
		} else {
			// Not a CUP credit card - delegate to the built-in method
			return jQuery.validator.methods.creditcard.call(this, value, element);
		}
	}, g_translations["Please enter a valid credit card number."]);

	$("#creditCard").rules("add", {
		required: true,
		extendedCreditCard: true,
		messages: { 
			required: g_translations["Please enter your name on card."],
			noAngleBrackets: g_translations["Name on card cannot contain the < or > characters."]
		}
	});
}

function fillTestForm() {
	if (urlParam("test") != 1) { return; }

	$('#name').val('TEST CARD1');
	$('#phone').val('+60123456789');
	$('#state').val('Test state');
	$('#city').val('Test city');
	$('#address').val('123 Test Street');
	$('#address2').val('');
	$('#postalCode').val('11200');
	$('#email').val('michael_hu@wycliffe.net');
	$('#email2').val('michael_hu@wycliffe.net');
	$('#amount').val('10.50');
	$('#cardName').val('TEST CARD1');
	$('#creditCard').val('3541 5999 9909 4213');
	$('#month').val('12');
	$('#year').val('2014');
	$('#footer').after("<h4>CUP Test Card Information</h4>Test Debit Card: 6299991111111111<br />PIN: 111111<br />Dynamic Verification Code: 111111<br /><br />Test Credit Card: 6200002222222222<br />Expiry: 06/2016<br />Dynamic Verification Code: 111111<br />CVV2: 111<h4>JCB Test Card information</h4>Approve: 3541 5999 9909 4213<br />Expiry: 12/2014<br />CVV2: 123<br />Cardholder Name: TEST CARD1<br /><br />Reject: 3541 5999 9909 4304<br />Expiry: 12/2014<br />CVV2: 123<br />Cardholder Name: TEST CARD2");
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