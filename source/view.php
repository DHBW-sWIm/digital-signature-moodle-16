<?php

require_once(dirname(dirname(__DIR__)) . '/config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/locallib.php');

include(__DIR__ . '/view_init.php');

global $SESSION;

echo $OUTPUT->heading('Start');

global $SESSION;

$email = "nebenmail09@gmail.com";
$password = "qwert12345";
$integratorKey = "3d677c95-6e5e-4133-9bd6-5670a4db865c"; //To identify third-party Apps

$recipient_email = 'nebenmail09@gmail.com';
$name = 'Edwin Neumann';
$document_name = 'Bachelorthesis.pdf';

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

/////////////////////////////////////////////////////////////////////////////////////////////////
// STEP 2 - Create an envelope with one recipient, one tab, one document and send!
/////////////////////////////////////////////////////////////////////////////////////////////////

$file_contents = file_get_contents(__DIR__ . '/resources/Bachelorthesis.pdf');
$base64Data = base64_encode($file_contents);

$data = array("accountId" => $accountId,
        "emailSubject" => "Please sign this document.",
        "documents" => array(
                array(
                        "documentId" => "1",
                        "name" => $document_name,
                        "documentBase64" => $base64Data,
                )),
        "recipients" => array (
                "signers" => array(
                        array(
                                "email" => $recipient_email,
                                "name" => $name,
                                "recipientId" => "1",
                                "clientUserId" => "1",
                                "tabs" => array (
                                        "signHereTabs" => array (
                                                array (
                                                        "xPosition" => "394",
                                                        "yPosition" => "187",
                                                        "documentId" => "1",
                                                        "recipientId" => "1",
                                                        "pageNumber" => "3"
                                                )
                                        ),
                                        "dateSignedTabs" => array (
                                                array (
                                                        "xPosition" => "119",
                                                        "yPosition" => "220",
                                                        "documentId" => "1",
                                                        "fontSize" => "Size16",
                                                        "recipientId" => "1",
                                                        "pageNumber" => "3",
                                                )
                                        )
                                )
                        )
                )
        ),
        "status" => "sent",
);

$data_string = json_encode($data);

// *** append "/envelopes" to baseUrl and as signature request endpoint
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

$response = json_decode($json_response, true);
$envelopeId = $response["envelopeId"];
curl_close($curl);

// 3: Get the URL of the Embedded Singing View

$data = array("returnUrl" => "http://www.docusign.com/devcenter",
        "authenticationMethod" => "None", "email" => $recipient_email,
        "userName" => $name, "recipientId" => "1", "clientUserId" => "1"
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

echo '<a href="'.$url.'">Click this link to get to the signing-view</a>';

// Finish the page.
echo $OUTPUT->footer();
