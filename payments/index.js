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
	$.validator.messages.required = "Please enter your {1}."

	$.validator.addMethod("noAngleBrackets", function(value, element, params) { 
		return value.indexOf("<") == -1 && value.indexOf(">") == -1;
	}, "{0} cannot contain the < or > characters.");

    // Create our own custom validator for CUP credit cards
	$.validator.addMethod("extendedCreditCard", function(value, element) {
		if (value.indexOf("62", 0) === 0) { // See if it's a CUP credit card
			// Should only consist of spaces, digits and dashes and be 16-19 digits long (sans dashes and spaces)
			if (/[0-9 \-]+/.test(value)) {
				var digitCount = value.replace(/ /g, "").replace(/\-/, "").length;
				return digitCount >= 16 && digitCount <= 19;
			}
			return false;
		} else {
			// Not a CUP credit card - delegate to the built-in method
			return $.validator.methods.creditcard.call(this, value, element);
		}
	}, "Please enter a valid {0}.");

	var settings = $("#theForm").validate({
		errorPlacement: function(error, element) {
			error.insertAfter("#instructions");
		},
		rules:{
			name:{
				required: [true, 'name'],
				noAngleBrackets: 'Name'
			},
			phone:{
				required: [true, 'phone number'],
				noAngleBrackets: 'Phone number'
			},
			state:{
				required: [true, 'state'],
				noAngleBrackets: 'State'
			},
			city:{
				required: [true, 'city'],
				noAngleBrackets: 'City'
			},
			address:{
				required: [true, 'address'],
				noAngleBrackets: 'Address'
			},
			address2:{
				noAngleBrackets: 'Address line 2'
			},
			postalCode:{
				required: [true, 'postal code'],
				noAngleBrackets: 'Postal code'
			},
			project:{
				noAngleBrackets: 'Project'
			},
			cardName:{
				required: [true, 'name on card'],
				noAngleBrackets: 'Name on card'
			},
			email:{
				required: [true, 'email'],
				email: true
			},
			email2:{
				equalTo: "#email"
			},
			amount:{
				required: [true, 'amount'],
				number: true
			},
			creditCard:{
				required: [true, 'credit card number'],
				extendedCreditCard: 'credit card number'
			}
		},
		messages:{
			email:{
				email: g_translations["Please enter a valid email."]
			},
			email2:{
				equalTo: g_translations["Please enter the same email."]
			},
			amount:{
				required: g_translations["Please enter the amount you want to donate."],
				number: g_translations["Please enter a valid amount to donate."]
			},
			creditCard:{
				extendedCreditCard: g_translations["Please enter a valid amount to donate."]
			}
		}
	}).settings;
	translateErrorMessages(settings);
}

function translateErrorMessages(settings) {
	if (typeof String.prototype.startsWith != 'function') {
		String.prototype.startsWith = function (str) {
			return this.slice(0, str.length) == str;
		};
	}
	if (typeof String.prototype.capitalizeFirstLetter != 'function') {
		String.prototype.capitalizeFirstLetter = function (str) {
			return this.charAt(0).toUpperCase() + this.slice(1);
		};
	}

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