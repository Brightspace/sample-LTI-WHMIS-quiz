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

/*
 *   READ BACK OUR GRADE
 *   If it exists we don't want to resubmit!
 */
$postBody = str_replace(
    array('SOURCEDID', 'OPERATION', 'MESSAGE'),
    array($sourcedid, 'readResultRequest', uniqid()),
    getPOXRequest());
$response = parseResponse(sendOAuthBodyPOST('POST', $endpoint, $oauth_consumer_key, $oauth_consumer_secret, 'application/xml', $postBody));
if($response['imsx_codeMajor'] == 'success' && $response['textString'] != '') {
    exit('Grade was already set in LMS - a cheater?!');
}

/*
 *   SET GRADE
 *   Calculate the grade and send it to the LMS via the LTI outcome url
 */
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
