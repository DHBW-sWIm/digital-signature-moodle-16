<?php

require_once(dirname(dirname(__DIR__)) . '/config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/locallib.php');

include(__DIR__ . '/view_init.php');

global $SESSION;

echo $OUTPUT->heading('Start');

$email = "nebenmail09@gmail.com";
$password = "qwert12345";
$integratorKey = "3d677c95-6e5e-4133-9bd6-5670a4db865c"; //To identify third-party Apps
$recipientName = "Mustafa Alaran";
$templateId = "aa64ae37-b3f8-4743-a52d-e6a31d09592a"; //ID of the template uploaded in Docu-Sign-Cloud
$templateRoleName = "Student";  // Role which has been set at the uploaded template
$clientUserId = "1";      // ID of the signer to distinguish between multiple persons

// authentication header:
$header = "<DocuSignCredentials><Username>" . $email . "</Username><Password>" . $password . "</Password><IntegratorKey>" . $integratorKey . "</IntegratorKey></DocuSignCredentials>";


// 1: Login (Retrieve Account ID of Docu-Sign User and BaseUrl which is used for making API calls on behalf of this account.)

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


// 2: Create an envelope containing the document and the information of the signer

$data = array("accountId" => $accountId,
              "emailSubject" => "Please sign this document.",
              "templateId" => $templateId,
              "templateRoles" => array(
                  array(
                    "roleName" => $templateRoleName,
                    "email" => $email,
                    "name" => $recipientName,
                    "clientUserId" => $clientUserId )),
              "status" => "sent");

$data_string = json_encode($data);
$curl = curl_init($baseUrl . "/envelopes" );
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

// ID of the envelope in the cloud
$response = json_decode($json_response, true);
$envelopeId = $response["envelopeId"];
curl_close($curl);

// 3: Get the URL of the Embedded Singing View

$data = array("returnUrl" => "http://www.docusign.com/devcenter",
    "authenticationMethod" => "None", "email" => $email,
    "userName" => $recipientName, "clientUserId" => $clientUserId
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

// display results
echo "Embedded URL is: \n\n" . $url . "\n\nNavigate to this URL to start the embedded signing view of the envelope\n";

// Finish the page.
echo $OUTPUT->footer();
