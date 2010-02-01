<?php
/*
//: "The contents of this file are subject to the Mozilla Public License
//: Version 1.1 (the "License"); you may not use this file except in
//: compliance with the License. You may obtain a copy of the License at
//: http://www.mozilla.org/MPL/
//:
//: Software distributed under the License is distributed on an "AS IS"
//: basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
//: License for the specific language governing rights and limitations
//: under the License.
//:
//: The Original Code is Guanxi (http://www.guanxi.uhi.ac.uk).
//:
//: The Initial Developer of the Original Code is Alistair Young alistair@codebrane.com
//: All Rights Reserved.
//:
*/

/**
 * Guanxi Guard.
 *
 * This is a PHP implementation of a Guanxi Guard.
 */
 
include "config.php";
require_once "utils.php";

session_start();

// Check if the ACS has already processed the attributes and made a decision
if(isset($_SESSION[SESSION_VAR_SESSION_ID])) {
	if (isSession($_SESSION[SESSION_VAR_SESSION_ID])) {
		$sessionData = loadSession($_SESSION[SESSION_VAR_SESSION_ID]);
		if ($sessionData[SESSION_VAR_AUTHZ] == SESSION_VAR_AUTHZ_VALUE) {
			return;
		}
	}
}

/* Let the request through if it's one of the Guanxi services
 * $_SERVER[REQUEST_URI] = /gxrest/headers.php for example
 */
$serviceName = getServiceName($_SERVER[REQUEST_URI]);
if (($serviceName == "GuanxiAttributeConsumerService.php") ||
    ($serviceName == "GuanxiPodderService.php") ||
    ($serviceName == "GuanxiGuard.php") ||
    ($serviceName == "GuanxiSessionVerifierService.php") ||
    ($serviceName == "error.php")) {
  return;
}

$_SESSION[SESSION_VAR_SESSION_ID] = md5(uniqid(rand(), true));
$_SESSION[SESSION_VAR_URL] = $_SERVER[REQUEST_URI];
$_SESSION[session.cookie_path] = COOKIE_PATH;
$_SESSION[session.cookie_lifetime] = COOKIE_LIFETIME;

// Need to do this as the verifier service doesn't have access to $_SESSION
$sessionData[SESSION_VAR_SESSION_ID] = $_SESSION[SESSION_VAR_SESSION_ID];
$sessionData[SESSION_VAR_URL] = $_SESSION[SESSION_VAR_URL];
saveSession($_SESSION);

// Call the Engine's WAYF location service
$restWS = ENGINE_WAYF_LOCATION_SERVICE;
$restWS .= "?guardid=".GUARD_ID;
$restWS .= "&sessionid=".$_SESSION[SESSION_VAR_SESSION_ID];
$result = @file($restWS);

if ($result[0] == "") {
	header("Location: error.php?errorCode=WAYF_LOCATION_SERVICE_ERROR");
	return;
}

// Construct the WAYF URL with the extra shibboleth paramters
$wayf = $result[0];
$wayf .= "?shire=".ENGINE_AUTH_CONSUMER_SERVICE;
$wayf .= "&target=".$_SESSION[SESSION_VAR_SESSION_ID];
$wayf .= "&time=TIME";
$wayf .= "&providerId=".GUARD_ID;

// Redirect to the WAYF
header("Location: $wayf");
?>
