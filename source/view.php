<?php

require_once(dirname(dirname(__DIR__)) . '/config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/locallib.php');

include(__DIR__ . '/view_init.php');

echo $OUTPUT->heading('Start');

global $SESSION;

$email = "nebenmail09@gmail.com";
$password = "qwert12345";
$integratorKey = "3d677c95-6e5e-4133-9bd6-5670a4db865c"; //To identify third-party Apps

$recipientId = $_GET['recipientId'];
$recipient = $DB->get_record('recipientdata', array('id' => $recipientId), $fields='*', $strictness=IGNORE_MISSING);
$envelopeId = $_GET['envelopeId'];

// construct the authentication header:
$header = "<DocuSignCredentials><Username>" . $email . "</Username><Password>" . $password . "</Password><IntegratorKey>" . $integratorKey . "</IntegratorKey></DocuSignCredentials>";

/////////////////////////////////////////////////////////////////////////////////////////////////
// STEP 1 - Login (to retrieve baseUrl and accountId)
/////////////////////////////////////////////////////////////////////////////////////////////////
$url = "https://demo.docusign.net/restapi/v2/login_information";
$curl = curl_init($url);
curl_setopt($curl, CURLOPT_HEADER, false);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array("X-DocuSign-Authentication: $header"));

$json_response = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

if ( $status != 200 ) {
    echo "error calling webservice, status is:" . $status;
    exit(-1);
}

$response = json_decode($json_response, true);
$accountId = $response["loginAccounts"][0]["accountId"];
$baseUrl = $response["loginAccounts"][0]["baseUrl"];
curl_close($curl);

$data = array("returnUrl" => "https://hardware-rental.moodle-dhbw.de/mod/digitalsignature/redirect.php?recipientId=" . $recipientId . "&envelopeId=" . $envelopeId,
        "authenticationMethod" => "None", "email" => $recipient->email,
        "userName" => $recipient->firstname,
        "recipientId" => $recipientId, "clientUserId" => $recipientId
);

$data_string = json_encode($data);
$curl = curl_init($baseUrl . "/envelopes/$envelopeId/views/recipient" );
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string),
                "X-DocuSign-Authentication: $header" )
);

$json_response = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
if ( $status != 201 ) {
    echo "error calling webservice, status is:" . $status . "\nerror text is --> ";
    print_r($json_response); echo "\n";
    exit(-1);
}

$response = json_decode($json_response, true);
$url = $response["url"];
?>

<div style="position:relative;padding-top:56.25%;">
  <iframe src="<?php echo $url ?>" frameborder="0" allowfullscreen
    style="position:absolute;top:0;left:0;width:100%;height:100%;"></iframe>
</div>
<?php
// Finish the page.
echo $OUTPUT->footer();
