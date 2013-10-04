<?php

error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1);

require_once 'util/lti_util.php';
require_once 'OAuth1p0.php';

/*
 *   CONFIGURATION
 */
$OAUTH_KEY    = 'key';
$OAUTH_SECRET = 'secret'; // You should use a better secret! This is shared with the LMS
$SITE_URL     = 'http://example.com';

if(!isset($_REQUEST['lis_outcome_service_url'])
|| !isset($_REQUEST['lis_result_sourcedid'])
|| !isset($_REQUEST['oauth_consumer_key'])
) {
    // If these weren't set then we aren't a valid LTI launch
    // The only case this happens is on the redirect in gradeSubmit.php
    if(!isset($_GET['grade'])) {
        exit('Neither an LTI launch or a redirect from gradeSubmit.php - make sure to visit the quiz via an LTI launch from an LMS.');
    }
    if(isset($_GET['setGradeFailure'])) {
        $setGradeFailure = true;
    } else {
        $showGradeNewlyRecorded = true;
        $disabled = 'disabled';
        $grade = $_GET['grade'];
    }
} else {
    // Ok, we are an LTI launch.

    /*
     *   VERIFY OAUTH SIGNATURE
     */

    // The LMS gives us a key and we need to find out which shared secret belongs to that key.
    // The key & secret are configured on the LMS in "Admin Tools" > "External Learning Tools" > "Manage Tool Providers",
    // Or in the remote plugin setup page if you are using them.
    $oauth_consumer_key = $_REQUEST['oauth_consumer_key'];

    // We only have one key, "key", which corresponds to the (shared) secret "secret"
    if($oauth_consumer_key != $OAUTH_KEY) {
        exit("Invalid OAuth key");
    } else {
        $oauth_consumer_secret = $OAUTH_SECRET;
    }

    if (!OAuth1p0::CheckSignatureForFormUrlEncoded($SITE_URL . $_SERVER['REQUEST_URI'], 'POST', $_POST, $oauth_consumer_secret)) {
        exit("Invalid OAuth signature");
    }

    // Store things that gradeSubmit.php will need into the session
    session_start();
    $_SESSION['lis_outcome_service_url'] = $_REQUEST['lis_outcome_service_url'];
    $_SESSION['lis_result_sourcedid']    = $_REQUEST['lis_result_sourcedid'];
    $_SESSION['lis_person_name_given']   = $_REQUEST['lis_person_name_given'];
    $_SESSION['oauth_consumer_key']      = $_REQUEST['oauth_consumer_key'];
    $_SESSION['oauth_consumer_secret']   = $oauth_consumer_secret;
    session_write_close();

    /*
     *   READ BACK OUR GRADE
     *   If it exists, show the grade and disable the submit button.
     */
    $endpoint  = $_REQUEST['lis_outcome_service_url'];
    $sourcedid = $_REQUEST['lis_result_sourcedid'];

    $postBody = str_replace(
        array('SOURCEDID', 'OPERATION', 'MESSAGE'),
        array($sourcedid, 'readResultRequest', uniqid()),
        getPOXRequest());
    $response = parseResponse(sendOAuthBodyPOST('POST', $endpoint, $oauth_consumer_key, $oauth_consumer_secret, 'application/xml', $postBody));
    if($response['imsx_codeMajor'] == 'success' && $response['textString'] != '') {
        $grade = $response['textString'];
        $showGrade = true;
        $disabled = 'disabled';
    }
}

/*
 *   CUSTOM LTI LAUNCH PARAMETERS
 *   The "posted date" is the date that the quiz is posted. It is either set
 *   in the "Edit Link" page of "External Learning Tools" in your LMS, or set
 *   when the link is created with the Valence REST API.
 *   This example parameter is NOT automatically set by the LMS!
 */
if(isset($_SESSION['custom_timecreated'])) {
    $postedDate = date('l, F j, Y', $_SESSION['custom_timecreated']); // this custom parameter is the date the quiz was posted.
}

/*
 *   OPTIONAL LTI LAUNCH PARAMETERS
 *   Some LTI parameters are set by the LMS but may or may not be sent depending on security settings.
 *   One example is the users given name. Check External Learning Tools to see if this is enabled for your links
 *   or disabled globally.
 */
if(isset($_REQUEST['lis_person_name_given'])) {
    $user = $_REQUEST['lis_person_name_given'];
}
?>

<!doctype html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device.width, initial-scale=1.0">
  <title>WHIMIS Quiz</title>

  <link href="css/bootstrap.min.css" rel="stylesheet">
  <script src="http://code.jquery.com/jquery-latest.js"></script>
  <script src="js/bootstrap.min.js"></script>

  <style>
    @media (min-width:990px) {
      body {
        padding-top: 60px;
      }
    }
  </style>
</head>

<body>

  <div class="container">

    <div class="alert alert-error" style="<?php if(!isset($setGradeFailure)) echo 'display: none;'; ?>">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      <strong>Uhoh!</strong> Could not set grade!
    </div>

    <div class="alert alert-success" style="<?php if(!isset($showGradeNewlyRecorded)) echo 'display: none;'?>">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      <strong>Congratulations!</strong> Your mark of <?php echo ($grade*100).'%'; ?> was recorded.
    </div>

    <div class="alert alert-success" style="<?php if(!isset($showGrade)) echo 'display: none;'; ?>">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      <strong>Hey</strong> You already took this grade and got a score of <?php echo ($grade*100).'%'; ?>
    </div>

    <div class="alert alert-info" style="<?php if(isset($showGrade) || isset($showGradeNewlyRecorded)) echo 'display: none;'; ?>">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      <strong>Quiz started</strong> You have infinity minutes to finish this quiz! <!-- You'll have to implement this yourself! -->
    </div>

    <p>Welcome<?php if(isset($user)) echo " $user";?>!</p>
    <h2>WHIMIS Quiz</h2>
    <?php if(isset($postedDate)) { ?>
    This quiz was posted on <?php echo $postedDate; ?>.
    <?php } ?>
    <form action="gradeSubmit.php" method="post" enctype="multipart/form-data">
      <fieldset>
        <h3>Question 1</h3>

        <img src="img/whmis1.jpg" width="100" height="100" alt="Image for question 1" />

        <label>Identify the above symbol</label>
        <label class="radio">
          <input <?php echo $disabled; ?> type="radio" name="question1" id="1A" value="A">
          Oxidizing Material
        </label>

        <label class="radio">
          <input <?php echo $disabled; ?> type="radio" name="question1" id="1B" value="B">
          Flammable and Combustible Material
        </label>

        <label class="radio">
          <input <?php echo $disabled; ?> type="radio" name="question1" id="1C" value="C">
          Corrosive material
        </label>

        <h3>Question 2</h3>
        <img src="img/whmis2.jpg" width="100" height="100" alt="Image for question 2" />
        <label>Identify the above symbol</label>

        <label class="radio">
          <input <?php echo $disabled; ?> type="radio" name="question2" id="2A" value="A">
          Compressed Gas
        </label>
        <label class="radio">
          <input <?php echo $disabled; ?> type="radio" name="question2" id="2B" value="B">
          Corrosive Material
        </label>
        <label class="radio">
          <input <?php echo $disabled; ?> type="radio" name="question2" id="2C" value="C">
          Dangerously Reactive Material
        </label>

        <br><br>
        <button id="submit" <?php echo $disabled; ?> type="submit" class="btn">Submit</button>
      </fieldset>
    </form>
  </div>
</body>
