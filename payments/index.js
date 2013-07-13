document.write('<script src="../formSetup.js" type="text/javascript"></script>');

var g_org;
var g_redirect_url;
var g_translations;
function index_js_init(org, redirectUrl, translationMapping) {
	g_org = org;
	g_redirect_url = redirectUrl;
	g_translations = translationMapping;
	
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
}

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

/*****************************************************************************************************
 * The rest of the file are callbacks called by formSetup.js using the Template Method pattern. See  *
 * formSetup.js for more details of how it peforms most of the tasks of initializing, validating and *
 * submitting a form for you.                                                                        *
 *****************************************************************************************************/

function validatorRules() {
	return {
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
	};
}
function validatorMessages() {
	return {
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
	};
}

function fieldsToUpload() {
	var retValue = [];
	retValue['country'] = $('#country').val();
	retValue['state'] = (retValue['country'] == 'SG') ? '--' : $('#state').val();
	retValue['city'] = (retValue['country'] == 'SG') ? '--' : $('#city').val();
	retValue['creditCard'] = $('#creditCard').val().replace(/ /g, "").replace(/\-/, "");
	retValue['org'] = g_org;

	var arr = ['name','email','phone','address','address2','postalCode',
			   'amount','project','cardName','month','year']
	for (var i = 0; i < arr.length; i++) {
		retValue[arr[i]] = $('#' + arr[i]).val();
	}

	return retValue;
}
function onSuccess(retValue) {
	if (retValue.indexOf('ok') == 0) {
		alert(g_translations["Thank you for your donation."]);
		window.location = g_redirect_url;
	} else {
		$('#errorAnchor').html('<span style="color:red">' + retValue  + '</span>');
	}
}

function setupEventHandlers() {
	$('#country').change(function() { fireCountryChange(); });
	fireCountryChange();
}

function testFields() {
	return {
		'name': 'TEST CARD1',
		'phone': '+60123456789',
		'state': 'Test state',
		'city': 'Test city',
		'address': '123 Test Street',
		'address2': '',
		'postalCode': '11200',
		'email': 'michael_hu@wycliffe.net',
		'email2': 'michael_hu@wycliffe.net',
		'amount': '10.50',
		'cardName': 'TEST CARD NAME',
		'creditCard': '3541 5999 9909 4213',
		'month': '04',
		'year': '2015',
		'footer': '<h4>CUP Test Card Information</h4>Test Debit Card: 6299991111111111<br />PIN: 111111<br />Dynamic Verification Code: 111111<br /><br />Test Credit Card: 6200002222222222<br />Expiry: 06/2016<br />Dynamic Verification Code: 111111<br />CVV2: 111<h4>JCB Test Card information</h4>Approve: 3541 5999 9909 4213<br />Expiry: 12/2014<br />CVV2: 123<br />Cardholder Name: TEST CARD1<br /><br />Reject: 3541 5999 9909 4304<br />Expiry: 12/2014<br />CVV2: 123<br />Cardholder Name: TEST CARD2'
	}
}
