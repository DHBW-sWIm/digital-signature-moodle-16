<?php
require_once(dirname(dirname(__DIR__)) . '/config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/locallib.php');

//include(__DIR__ . '/view_init.php');

global $DB;

$recipientId = $_GET['recipientId'];
$recipient = $DB->get_record('recipientdata', array('id' => $recipientId), $fields='*', $strictness=IGNORE_MISSING);

$integratorKey = "3d677c95-6e5e-4133-9bd6-5670a4db865c";
$email = "nebenmail09@gmail.com";
$password = "qwert12345";
$name = "Edwin";
$envelopeId = $_GET['envelopeId'];
$header = "<DocuSignCredentials><Username>" . $email . "</Username><Password>" . $password . "</Password><IntegratorKey>" . $integratorKey . "</IntegratorKey></DocuSignCredentials>";

//
// STEP 1 - log in
//
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


$curl = curl_init($baseUrl . "/envelopes/" . $envelopeId . "/documents" );
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                "X-DocuSign-Authentication: $header" )
);
$json_response = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
if ( $status != 200 ) {
    echo "error calling webservice, status is:" . $status;
    exit(-1);
}

$response = json_decode($json_response, true);
print_r($response);
curl_close($curl);

$i = 0;

foreach( $response["envelopeDocuments"] as $document ) {
    $docUri = $document["uri"];

    $curl = curl_init($baseUrl . $docUri );
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    "X-DocuSign-Authentication: $header" )
    );
    $data = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ( $status != 200 ) {
        echo "error calling webservice, status is:" . $status;
        exit(-1);
    }
    //echo $data;

    //file_put_contents($envelopeId . "-" . $document["name"], $data);
    $record = new stdClass();
    $record->pdf = $data;
    $lastinsertid = $DB->insert_record('hardware_rental_pdf', $record, true);

    if ($i == 0) {
        $recipient->returnurl = $recipient->returnurl . "&documentId=" . $lastinsertid;
        $i = 1;
    } else {
        $recipient->returnurl = $recipient->returnurl . "&cocId=" . $lastinsertid;
    }

    curl_close($curl);
}

?>
<script>
    window.top.location.href = '<?php echo $recipient->returnurl ?>';
</script>

