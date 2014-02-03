<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<title>Wycliffe payment processor</title>
<style type="text/css">
#theForm {
	width: 800px;
	margin: auto;
}
fieldset {
	padding: 5px 12px 12px 12px;
	margin: 0 0 13px 0;
}
#contactInfo { 
	float: left;
	width: 350px;
}
#paymentInfo {
	float: right;
	width: 384px;
}
legend {
	font-weight: bold;
	font-size: 28px;
}
label { 
	display: inline-block;
	text-align: right;
}
#contactInfo label{
	width: 106px;
}
#paymentInfo label{
	width: 136px;
}
.form-text { width: 230px; }
.form-select { width: 234px; }
.date-select { width: 109px; }
@-moz-document url-prefix() {
	.form-select { width: 236px; }
	.date-select { width: 110px; }
}
#month {
	margin-right: 2px;
}
#year {
	margin-left: 2px;
}
#JCB {
	margin-left: 164px;
}
#unionPay {
	margin: 13px 9px 0 7px;
}
#donate {
	float: right;
	width: 150px;
	margin: 0;
	padding: 6px 22px;
	border-width: 2px;
	font-weight: bold;
	font-size: 23px;
}
#spinner {
	float: right;
    background-image: url("../spinner.gif");
    background-repeat: no-repeat;
    display: none;
    height: 16px;
    margin: 4px 0 0 3px;
    width: 16px;
}
.error {
	color: red;
}
label.error {
	display: block;
	text-align: left;
	font-weight: bold;
}
</style>
<script language='JavaScript' type='text/javascript' src='../jquery.min.js'></script>
<script language='JavaScript' type='text/javascript' src='../jquery.validate.min.js'></script>
<script language='JavaScript' type='text/javascript' src='index.js'></script>
<script language='JavaScript' type='text/javascript'>
<?php 
	$org = configureForORG();
	require_once 'translation.php';
	echo 'index_js_init("' . $org["org"] . '","' . $org["redirect_url"] . '",{' . configureForLang(0) . '})';
?>
</script>
</head>
<body>
<form id="theForm" action="#" method="post">
<img src="<?php echo $org["img_prefix"]; ?>header.png" />
<h3 id="errorAnchor">You are donating to <?php echo $org["name"]; ?>, a Participating Organization of the Wycliffe Global Alliance. Your donation will be converted to <?php echo $org["currency"]; ?> for processing purposes.</h3>

<fieldset id="contactInfo"><legend><?php echo t("Contact Information"); ?></legend>
<div>
 <label for="name"><?php echo t("Name"); ?>:</label>
 <input type="text" maxlength="64" name="name" id="name" class="form-text" />
</div>
<div>
 <label for="email"><?php echo t("Email"); ?>:</label>
 <input type="text" maxlength="64" name="email" id="email" class="form-text" />
</div>
<div>
 <label for="email2"><?php echo t("Confirm Email"); ?>:</label>
 <input type="text" maxlength="64" name="email2" id="email2" class="form-text" />
</div>
<div>
 <label for="phone"><?php echo t("Phone Number"); ?>:</label>
 <input type="text" maxlength="64" name="phone" id="phone" class="form-text" />
</div>
<div>
 <label for="country"><?php echo t("Country"); ?>:</label>
 <select name="country" id="country" class="form-select">
   <option value="AF">Afghanistan</option><option value="AL">Albania</option><option value="DZ">Algeria</option><option value="AS">American Samoa</option><option value="AD">Andorra</option><option value="AO">Angola</option><option value="AI">Anguilla</option><option value="AQ">Antarctica</option><option value="AG">Antigua and Barbuda</option><option value="AR">Argentina</option><option value="AM">Armenia</option><option value="AW">Aruba</option><option value="AU">Australia</option><option value="AT">Austria</option><option value="AZ">Azerbaijan</option><option value="BS">Bahamas</option><option value="BH">Bahrain</option><option value="BD">Bangladesh</option><option value="BB">Barbados</option><option value="BY">Belarus</option><option value="BE">Belgium</option><option value="BZ">Belize</option><option value="BJ">Benin</option><option value="BM">Bermuda</option><option value="BT">Bhutan</option><option value="BO">Bolivia</option><option value="BA">Bosnia and Herzegovina</option><option value="BW">Botswana</option><option value="BV">Bouvet Island</option><option value="BR">Brazil</option><option value="IO">British Indian Ocean Territory</option><option value="BN">Brunei Darussalam</option><option value="BG">Bulgaria</option><option value="BF">Burkina Faso</option><option value="BI">Burundi</option><option value="KH">Cambodia</option><option value="CM">Cameroon</option><option value="CA">Canada</option><option value="CV">Cape Verde</option><option value="KY">Cayman Islands</option><option value="CF">Central African Republic</option><option value="TD">Chad</option><option value="CL">Chile</option><option value="CN">China</option><option value="CX">Christmas Island</option><option value="CC">Cocos (Keeling) Islands</option><option value="CO">Colombia</option><option value="KM">Comoros</option><option value="CG">Congo</option><option value="CD">Congo, the Democratic Republic of the</option><option value="CK">Cook Islands</option><option value="CR">Costa Rica</option><option value="CI">Cote D&#039;Ivoire</option><option value="HR">Croatia</option><option value="CU">Cuba</option><option value="CY">Cyprus</option><option value="CZ">Czech Republic</option><option value="DK">Denmark</option><option value="DJ">Djibouti</option><option value="DM">Dominica</option><option value="DO">Dominican Republic</option><option value="EC">Ecuador</option><option value="EG">Egypt</option><option value="SV">El Salvador</option><option value="GQ">Equatorial Guinea</option><option value="ER">Eritrea</option><option value="EE">Estonia</option><option value="ET">Ethiopia</option><option value="FK">Falkland Islands (Malvinas)</option><option value="FO">Faroe Islands</option><option value="FJ">Fiji</option><option value="FI">Finland</option><option value="FR">France</option><option value="GF">French Guiana</option><option value="PF">French Polynesia</option><option value="TF">French Southern Territories</option><option value="GA">Gabon</option><option value="GM">Gambia</option><option value="GE">Georgia</option><option value="DE">Germany</option><option value="GH">Ghana</option><option value="GI">Gibraltar</option><option value="GR">Greece</option><option value="GL">Greenland</option><option value="GD">Grenada</option><option value="GP">Guadeloupe</option><option value="GU">Guam</option><option value="GT">Guatemala</option><option value="GN">Guinea</option><option value="GW">Guinea-Bissau</option><option value="GY">Guyana</option><option value="HT">Haiti</option><option value="HM">Heard Island and Mcdonald Islands</option><option value="VA">Holy See (Vatican City State)</option><option value="HN">Honduras</option><option value="HK" <?php if ($org["country"] == "HK") { echo "selected"; } ?>>Hong Kong</option><option value="HU">Hungary</option><option value="IS">Iceland</option><option value="IN">India</option><option value="ID">Indonesia</option><option value="IR">Iran, Islamic Republic of</option><option value="IQ">Iraq</option><option value="IE">Ireland</option><option value="IL">Israel</option><option value="IT">Italy</option><option value="JM">Jamaica</option><option value="JP">Japan</option><option value="JO">Jordan</option><option value="KZ">Kazakhstan</option><option value="KE">Kenya</option><option value="KI">Kiribati</option><option value="KP">Korea, Democratic People&#039;s Republic of</option><option value="KR">Korea, Republic of</option><option value="KW">Kuwait</option><option value="KG">Kyrgyzstan</option><option value="LA">Lao People&#039;s Democratic Republic</option><option value="LV">Latvia</option><option value="LB">Lebanon</option><option value="LS">Lesotho</option><option value="LR">Liberia</option><option value="LY">Libya</option><option value="LI">Liechtenstein</option><option value="LT">Lithuania</option><option value="LU">Luxembourg</option><option value="MO">Macao</option><option value="MK">Macedonia, the Former Yugoslav Republic of</option><option value="MG">Madagascar</option><option value="MW">Malawi</option><option value="MY">Malaysia</option><option value="MV">Maldives</option><option value="ML">Mali</option><option value="MT">Malta</option><option value="MH">Marshall Islands</option><option value="MQ">Martinique</option><option value="MR">Mauritania</option><option value="MU">Mauritius</option><option value="YT">Mayotte</option><option value="MX">Mexico</option><option value="FM">Micronesia, Federated States of</option><option value="MD">Moldova, Republic of</option><option value="MC">Monaco</option><option value="MN">Mongolia</option><option value="ME">Montenegro</option><option value="MS">Montserrat</option><option value="MA">Morocco</option><option value="MZ">Mozambique</option><option value="MM">Myanmar</option><option value="NA">Namibia</option><option value="NR">Nauru</option><option value="NP">Nepal</option><option value="NL">Netherlands</option><option value="AN">Netherlands Antilles</option><option value="NC">New Caledonia</option><option value="NZ">New Zealand</option><option value="NI">Nicaragua</option><option value="NE">Niger</option><option value="NG">Nigeria</option><option value="NU">Niue</option><option value="NF">Norfolk Island</option><option value="MP">Northern Mariana Islands</option><option value="NO">Norway</option><option value="OM">Oman</option><option value="PK">Pakistan</option><option value="PW">Palau</option><option value="PS">Palestinian Territory, Occupied</option><option value="PA">Panama</option><option value="PG">Papua New Guinea</option><option value="PY">Paraguay</option><option value="PE">Peru</option><option value="PH" <?php if ($org["country"] == "PH") { echo "selected"; } ?>>Philippines</option><option value="PN">Pitcairn</option><option value="PL">Poland</option><option value="PT">Portugal</option><option value="PR">Puerto Rico</option><option value="QA">Qatar</option><option value="RE">Reunion</option><option value="RO">Romania</option><option value="RU">Russian Federation</option><option value="RW">Rwanda</option><option value="SH">Saint Helena</option><option value="KN">Saint Kitts and Nevis</option><option value="LC">Saint Lucia</option><option value="PM">Saint Pierre and Miquelon</option><option value="VC">Saint Vincent and the Grenadines</option><option value="WS">Samoa</option><option value="SM">San Marino</option><option value="ST">Sao Tome and Principe</option><option value="SA">Saudi Arabia</option><option value="SN">Senegal</option><option value="RS">Serbia</option><option value="SC">Seychelles</option><option value="SL">Sierra Leone</option><option value="SG" <?php if ($org["country"] == "SG") { echo "selected"; } ?>>Singapore</option><option value="SK">Slovakia</option><option value="SI">Slovenia</option><option value="SB">Solomon Islands</option><option value="SO">Somalia</option><option value="ZA">South Africa</option><option value="GS">South Georgia and the South Sandwich Islands</option><option value="ES">Spain</option><option value="LK">Sri Lanka</option><option value="SD">Sudan</option><option value="SR">Suriname</option><option value="SJ">Svalbard and Jan Mayen</option><option value="SZ">Swaziland</option><option value="SE">Sweden</option><option value="CH">Switzerland</option><option value="SY">Syrian Arab Republic</option><option value="TW">Taiwan, Province of China</option><option value="TJ">Tajikistan</option><option value="TZ">Tanzania, United Republic of</option><option value="TH">Thailand</option><option value="TL">Timor-Leste</option><option value="TG">Togo</option><option value="TK">Tokelau</option><option value="TO">Tonga</option><option value="TT">Trinidad and Tobago</option><option value="TN">Tunisia</option><option value="TR">Turkey</option><option value="TM">Turkmenistan</option><option value="TC">Turks and Caicos Islands</option><option value="TV">Tuvalu</option><option value="UG">Uganda</option><option value="UA">Ukraine</option><option value="AE">United Arab Emirates</option><option value="GB">United Kingdom</option><option value="US">United States</option><option value="UM">United States Minor Outlying Islands</option><option value="UY">Uruguay</option><option value="UZ">Uzbekistan</option><option value="VU">Vanuatu</option><option value="VE">Venezuela</option><option value="VN">Viet Nam</option><option value="VG">Virgin Islands, British</option><option value="VI">Virgin Islands, U.s.</option><option value="WF">Wallis and Futuna</option><option value="EH">Western Sahara</option><option value="YE">Yemen</option><option value="ZM">Zambia</option><option value="ZW">Zimbabwe</option>
 </select>
</div>
<div>
 <label for="address"><?php echo t("Address Line 1"); ?>:</label>
 <input type="text" maxlength="64" name="address" id="address" class="form-text" />
</div>
<div>
 <label for="address2"><?php echo t("Address Line 2"); ?>:</label>
 <input type="text" maxlength="64" name="address2" id="address2" class="form-text" />
</div>
<div>
 <label for="city" id="lblCity"><?php echo t("City"); ?>:</label>
 <input type="text" maxlength="64" name="city" id="city" class="form-text" />
</div>
<div>
 <label for="state" id="lblState"><?php echo t("State"); ?>:</label>
 <input type="text" maxlength="64" name="state" id="state" class="form-text" />
</div>
<div>
 <label for="postalCode"><?php echo t("Postal Code"); ?>:</label>
 <input type="text" maxlength="64" name="postalCode" id="postalCode" class="form-text" />
</div>
</fieldset>
<fieldset id="paymentInfo"><legend><?php echo t("Payment Information"); ?></legend>
<div>
 <label for="amount"><?php echo t("Amount"); ?>:</label>
 <input type="text" maxlength="64" name="amount" id="amount" class="form-text" />
</div>
<div>
 <label for="project"><?php echo t("Project (optional)"); ?>:</label>
 <input type="text" maxlength="64" name="project" id="project" class="form-text" value="<?php echo $org['project']; ?>" />
</div>
<div>
 <label for="cardName"><?php echo t("Name on Card"); ?>:</label>
 <input type="text" maxlength="64" name="cardName" id="cardName" class="form-text" />
</div>
<div>
 <label for="creditCard"><?php echo t("Credit Card Number"); ?>:</label>
 <input type="text" maxlength="64" name="creditCard" id="creditCard" class="form-text" />
</div>
<div>
 <label for="month"><?php echo t("Expiration Date"); ?>:</label>
 <select name="month" id="month" class="date-select"><option value="01" selected>1</option><option value="02">2</option><option value="03">3</option><option value="04">4</option><option value="05">5</option><option value="06">6</option><option value="07">7</option><option value="08">8</option><option value="09">9</option><option value="10">10</option><option value="11">11</option><option value="12">12</option></select> / 
 <select name="year" id="year" class="date-select"><option value="2013" selected>2013</option><option value="2014">2014</option><option value="2015">2015</option><option value="2016">2016</option><option value="2017">2017</option><option value="2018">2018</option><option value="2019">2019</option><option value="2020">2020</option></select>
</div>
</fieldset>
<img id="JCB" src="JCB.gif" /><img id="unionPay" src="unionpay.png" />
<button type="submit" id="donate"><?php echo t("Donate"); ?><div id="spinner"></div></button>
<img id="footer" src="<?php echo $org["img_prefix"]; ?>footer.png" />
</form>
</body>
</html>
<?php
function configureForORG() {
	require_once 'classes/Organization.php';

	$project = isset($_GET["project"]) ? filter_var($_GET["project"], FILTER_SANITIZE_STRING) : "";
	$org = new Organization();
	$msg = '';
	$org->readFromData($_GET, $msg, true);

	return array(
		"project" => $project,
		"org" => $org->org(),
		"img_prefix" => $org->org() . '_',
		"name" => $org->name(),
		"country" => $org->country(),
		"currency" => $org->currency(),
		"redirect_url" => $org->redirect_url(),
	);
}
?>