<?php

require_once 'util/lti_util.php';

session_start();
if(!isset($_SESSION['lis_outcome_service_url'])
|| !isset($_SESSION['lis_result_sourcedid'])
|| !isset($_SESSION['oauth_consumer_key'])
|| !isset($_SESSION['oauth_consumer_secret'])
) {
    print_r($_SESSION);
    exit('Missing parameters in the session - perhaps 3rd party cookies are disabled? You should only go to this page via the Submit button on the quiz');
}
session_write_close();

$endpoint              = $_SESSION['lis_outcome_service_url'];
$sourcedid             = $_SESSION['lis_result_sourcedid'];
$oauth_consumer_key    = $_SESSION['oauth_consumer_key'];
$oauth_consumer_secret = $_SESSION['oauth_consumer_secret'];

if (isset($_REQUEST['question1']) && isset($_REQUEST['question2'])) {
    // Calculate the students grade based on their answers
    $grade = ($_REQUEST['question1'] == 'B' ? 1 : 0) + ($_REQUEST['question2'] == 'A' ? 1 : 0);
	$grade /= 2.0;

    // Submit the grade to the LMS with LTI
	$postBody = str_replace(
		array('SOURCEDID', 'GRADE', 'OPERATION', 'MESSAGE'),
		array($sourcedid, $grade, 'replaceResultRequest', uniqid()),
		getPOXGradeRequest());
	$response = sendOAuthBodyPOST('POST', $endpoint, $oauth_consumer_key, $oauth_consumer_secret, 'application/xml', $postBody);
	$response = parseResponse($response);
	if($response['imsx_codeMajor'] == 'success') {
        header('Location: whmis.php?grade=' . $grade);
	} else {
        header('Location: whmis.php?grade=0&setGradeFailure=1&imsx_codeMajor=' . $response['imsx_codeMajor']);
	}
} else {
    exit("The questions weren't answered - huh?");
}
?>
