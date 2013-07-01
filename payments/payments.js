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
		
		var country = $('#country').val();
		var state = (country == 'SG') ? '--' : $('#state').val();
		var city = (country == 'SG') ? '--' : $('#city').val();
			
		var data = { 
			name: $('#name').val(), 
			email: $('#email').val(),
			phone: $('#phone').val(),
			country: country,
			address: $('#address').val(),
			address2: $('#address2').val(),
			state: state,
			city: city,
			postalCode: $('#postalCode').val(),
			amount: $('#amount').val(),
			project: $('#project').val(),
			cardName: $('#cardName').val(),
			creditCard: $('#creditCard').val().replace(/ /g, "").replace(/\-/, ""),
			month: $('#month').val(),
			year: $('#year').val(),
			test: test,
			org: g_org
		};
		
		$.ajax({
			type: 'POST',
			url: 'webservice.php',
			data: JSON.stringify(data),
			dataType: 'json',
			contentType: 'application/json',
			success: function(retValue, textStatus) {
				$('#spinner').hide();
				if (retValue.status == 'ok') {
					alert(g_thank_you_for_donation);
					window.location = g_redirect_url;
				} else {
					$('#instructions').html('<span style="color:red">' + retValue.status  + '</span>');
				}
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				$('#spinner').hide();
				$('#instructions').html('<span style="color:red">' + removeBefore(XMLHttpRequest.statusText, '(0)') + '</span>');
			}
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
	}, translate("Has angle brackets"));

	$("#name").rules("add", {
		required: true,
		noAngleBrackets: true,
		messages: { 
			required: translate("Please enter your name."),
			noAngleBrackets: translate("Name cannot contain the < or > characters.")
		}
	});
	$("#phone").rules("add", {
		required: true,
		noAngleBrackets: true,
		messages: { 
			required: translate("Please enter your phone number."),
			noAngleBrackets: translate("Phone number cannot contain the < or > characters.")
		}
	});
	$("#state").rules("add", {
		required: true,
		noAngleBrackets: true,
		messages: { 
			required: translate("Please enter your state."),
			noAngleBrackets: translate("State cannot contain the < or > characters.")
		}
	});
	$("#city").rules("add", {
		required: true,
		noAngleBrackets: true,
		messages: { 
			required: translate("Please enter your city."),
			noAngleBrackets: translate("City cannot contain the < or > characters.")
		}
	});
	$("#address").rules("add", {
		required: true,
		noAngleBrackets: true,
		messages: { 
			required: translate("Please enter your address."),
			noAngleBrackets: translate("Address cannot contain the < or > characters.")
		}
	});
	$("#address2").rules("add", {
		noAngleBrackets: true,
		messages: { 
			noAngleBrackets: translate("Address line 2 cannot contain the < or > characters.")
		}
	});
	$("#postalCode").rules("add", {
		required: true,
		noAngleBrackets: true,
		messages: { 
			required: translate("Please enter your postal code."),
			noAngleBrackets: translate("Postal code cannot contain the < or > characters.")
		}
	});

	$("#email").rules("add", {
		required: true,
		email: true,
		messages: { 
			required: translate("Please enter your email."),
			email: translate("Please enter a valid email.")
		}
	});
	$("#email2").rules("add", {
		equalTo: "#email",
		messages: { equalTo: translate("Please enter the same email.") }
	});

	$("#amount").rules("add", {
		required: true,
		number: true,
		messages: { 
			required: translate("Please enter the amount you want to donate."),
			number: translate("Please enter a valid amount to donate.")
		}
	});
	$("#project").rules("add", {
		noAngleBrackets: true,
		messages: { 
			noAngleBrackets: translate("Project cannot contain the < or > characters.")
		}
	});
	$("#cardName").rules("add", {
		required: true,
		noAngleBrackets: true,
		messages: { 
			required: translate("Please enter your name on card."),
			noAngleBrackets: translate("Name on card cannot contain the < or > characters.")
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
	}, translate("Please enter a valid credit card number."));

	$("#creditCard").rules("add", {
		required: true,
		extendedCreditCard: true,
		messages: { 
			required: translate("Please enter your name on card."),
			noAngleBrackets: translate("Name on card cannot contain the < or > characters.")
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
	$('#email').val('brian_morrow@wycliffe.net');
	$('#email2').val('brian_morrow@wycliffe.net');
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