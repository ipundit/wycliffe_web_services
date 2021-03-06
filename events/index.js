document.write('<script type="text/javascript" src="../../formSetup.js" ></script>');

var g_translations;

function index_js_init(translationMapping) {
	g_translations = translationMapping;
	
	$.validator.addMethod("phone", function(value, element) {
		return this.optional(element) || /^\+\d+ [\( \)\d]+$/i.test(value);  
	}, "{0} must start with a + and have a space after the country code.");

	$.validator.addMethod("time", function(value, element) {
		return this.optional(element) || /^(([0-1]?[0-9])|([2][0-3])):([0-5]?[0-9])(:([0-5]?[0-9]))?$/i.test(value);  
	}, "Please enter a valid {0}.");
}

/*****************************************************************************************************
 * The rest of the file are callbacks called by formSetup.js using the Template Method pattern. See  *
 * formSetup.js for more details of how it peforms most of the tasks of initializing, validating and *
 * submitting a form for you.                                                                        *
 *****************************************************************************************************/

function webserviceURL() {
	return 'https://wycliffe-services.net/events/webservice_participant.php';
}
 
function validatorRules() {
	return {
		arrivalFlightNumber:{
			noAngleBrackets: ['Arrival flight number', null]
		},
		arrivalDate:{
			date: true
		},
		arrivalTime:{
			time: ['arrival time']
		},
		departureFlightNumber:{
			noAngleBrackets: ['Departure flight number', null]
		},
		departureDate:{
			date: true
		},
		departureTime:{
			time: ['departure time']
		},
		honorific:{
			noAngleBrackets: ['Honorific', null]
		},
		firstName:{
			noAngleBrackets: ['First name', null]
		},
		lastName:{
			noAngleBrackets: ['Last name', null]
		},
		organization:{
			noAngleBrackets: ['Organization', null]
		},
		title:{
			noAngleBrackets: ['Title', null]
		},
		email:{
			email: true
		},
		phone:{
			phone: ['Cell phone number', null]
		},
		passportNumber:{
			noAngleBrackets: ['Passport number', null]
		},
		passportExpiryDate:{
			date: true
		},
		passportCountry:{
			noAngleBrackets: ['Passport country', null]
		},
		passportName:{
			noAngleBrackets: ['Passport name', null]
		},
	};
}
function validatorMessages() {
	return {
		arrivalDate:{
			date: g_translations["Please enter a valid arrival date."]
		},
		departureDate:{
			date: g_translations["Please enter a valid departure date."]
		},
		passportExpiryDate:{
			date: g_translations["Please enter a valid passport expiry date."]
		},
		email:{
			email: g_translations["Please enter a valid email."]
		},
	};
}

function fieldsToUpload() {
	var retValue = [];
	retValue['isComing'] = $('input[name=isComing]:radio:checked').val();

	var arr = ['eventName','passkey','id','arrivalFlightNumber','arrivalDate','arrivalTime',
			   'departureFlightNumber','departureDate','departureTime','honorific','firstName','lastName',
			   'organization','title','email','phone','passportNumber',
			   'passportExpiryDate','passportCountry','passportName','notes','password'];

	for (var i = 0; i < arr.length; i++) {
		var val = $('#' + arr[i]).val();
		if (undefined !== val) { retValue[arr[i]] = val; }
	}
	
	retValue['doUpdate'] = 1;
	retValue['arrivalDate'] = convertToDate(retValue['arrivalDate']);
	retValue['departureDate'] = convertToDate(retValue['departureDate']);
	if (undefined !== retValue['passportExpiryDate']) { retValue['passportExpiryDate'] = convertToDate(retValue['passportExpiryDate']); }
	return retValue;
}
function convertToDate(theDate) {
	if (theDate == '') { return ''; }
	return theDate.replace(/\//g, '-');
}

function onSuccess(retValue) {
	retValue = $.parseJSON(retValue);
	if (retValue.error == 'ok') {
		$('#id').val(retValue.id);
		$('#passkey').val(retValue.passkey);
		$('#password').val('');
		retValue.error = g_translations['Your registration information has been updated. Come back to this website any time to make a change.'];
	}
	$('#errorAnchor').html('<span>' + retValue.error + '</span>');
}

function eventHandlers() {
	return {};
}

function formDefaultValues() {
	if (urlParam('isComing') == 0) {
		$('#errorAnchor').html(g_translations["We're sorry that you can't make it. You can click the email link again if you change your mind."]);
	} else if (urlParam('isComing') == 1) {
		$('#errorAnchor').html(g_translations["We have confirmed your attendance. Please fill out the rest of the form to book your hotel room."]);
	} else if (urlParam('id') == 0) {
		$('#errorAnchor').html(g_translations["Enter the information for the new participant."]);
	}

	var retValue = {};
	return retValue;
}
