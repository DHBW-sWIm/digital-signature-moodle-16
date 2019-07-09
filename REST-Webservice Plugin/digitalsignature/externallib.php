<?php

// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licen
require_once($CFG->libdir . "/externallib.php");
defined('MOODLE_INTERNAL') || die();

class local_digitalsignature_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function createEnvelope_parameters() {
        return new external_function_parameters(
                array(
                        'pdfDocument' => new external_value(PARAM_RAW, 'The PDF-Document'),
                        'documentName' => new external_value(PARAM_TEXT, 'The document name'),
                        'returnUrl' => new external_value(PARAM_RAW, 'The return URL after the recipient has signed the document'),
                        'signers' => new external_multiple_structure(
                                        new external_single_structure(
                                                array(
                                                    'email' => new external_value(PARAM_TEXT, 'The signers mailadress'),
                                                    'firstName' => new external_value(PARAM_TEXT, 'The signers first Name'),
                                                    'lastName' => new external_value(PARAM_TEXT, 'The signers last Name'),
                                                    'tabs' => new external_multiple_structure(
                                                            new external_single_structure(
                                                                    array(
                                                                        'type' => new external_value(PARAM_TEXT, 'The tab.'),
                                                                        'xPos' => new external_value(PARAM_INT, 'The X-Position of the tab'),
                                                                        'yPos' => new external_value(PARAM_INT, 'The Y-Position of the tab'),
                                                                        'page' => new external_value(PARAM_INT, 'The page where the tab will be placed'),
                                                                    )
                                                            )
                                                    )
                                                )
                                        )
                        )
                )
        );
    }


    public static function createEnvelope($pdfDocument, $documentName, $returnUrl, $signers) {
        global $DB;
        global $USER;

        //Parameter validation
        //REQUIRED
        $params = self::validate_parameters(self::createEnvelope_parameters(),
                array('pdfDocument' => $pdfDocument, 'documentName' => $documentName, 'returnUrl' => $returnUrl, 'signers' => $signers));

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        //Capability checking
        //OPTIONAL but in most web service it should present
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }

        if ($DB->record_exists('recipientdata', array('email' => $signers[0]['email'])) == FALSE) {
            try {
                $transaction = $DB->start_delegated_transaction();
                $DB->insert_record('recipientdata', ['firstname' => $signers[0]['firstName'],
                                                    'lastname' => $signers[0]['lastName'],
                                                    'email' => $signers[0]['email'],
                                                    'returnurl' => $params['returnUrl']],
                                    $returnid=true, $bulk=false);
                $transaction->allow_commit();
            } catch (Exception $e) {
                $transaction->rollback($e);
            }
        }

        $recipient = $DB->get_record('recipientdata', array('email' => $signers[0]['email']), $fields='*', $strictness=IGNORE_MISSING);

        $email = "nebenmail09@gmail.com";
        $password = "qwert12345";
        $integratorKey = "3d677c95-6e5e-4133-9bd6-5670a4db865c";

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

        $data = array("accountId" => $accountId,
                "emailSubject" => "Please sign this document.",
                "documents" => array(
                array(
                        "documentId" => "1",
                        "name" => $params['documentName'],
                        "documentBase64" => $params['pdfDocument'],
                )),
                "recipients" => array (
                "signers" => array(
                        array(
                                "email" => $recipient->email,
                                "name" => $recipient->firstname,
                                "recipientId" => $recipient->id,
                                "clientUserId" => $recipient->id,
                                "tabs" => array (
                                        "signHereTabs" => array (
                                                array (
                                                        "xPosition" => "394",
                                                        "yPosition" => "187",
                                                        "documentId" => "1",
                                                        "recipientId" => $recipient->id,
                                                        "pageNumber" => "1"
                                                )
                                        ),
                                        "dateSignedTabs" => array (
                                                array (
                                                        "xPosition" => "119",
                                                        "yPosition" => "220",
                                                        "documentId" => "1",
                                                        "fontSize" => "Size16",
                                                        "recipientId" => $recipient->id,
                                                        "pageNumber" => "1"
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

        $recipient->envelopeid = $envelopeId;

        try {
            $transaction = $DB->start_delegated_transaction();
            $DB->update_record('recipientdata', $recipient, $bulk=false);
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
        }

        return "https://hardware-rental.moodle-dhbw.de/mod/digitalsignature/view.php?id=27&envelopeId=" . $envelopeId . "&recipientId=" . $recipient->id;

    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function createEnvelope_returns() {
        return new external_value(PARAM_TEXT, 'The welcome message + user first name');
    }



}
