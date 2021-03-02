<?php

declare(strict_types=1);

namespace PERUCOVID;

require_once '../includes/globals.php';
require_once '../includes/ingest.php';
require_once '../includes/email.php';
require_once '../includes/export.php';

$message = 'UnknownError';

try {
    // check for HTTP header
    $headers = array_change_key_case(apache_request_headers());
    if (!isset($headers['qualtrics'])) {
        throw new \Exception('HeaderMissing');
    }
    
    $apiKey = trim(file_get_contents(API_KEY_FILE));
    if ($headers['qualtrics'] != $apiKey) {
        throw new \Exception('ApiKeyMismatch');
    }
    
    // check for corrections header
    $corrections = isset($headers['corrections']) && 
      'true' == $headers['corrections'];
    
    // read JSON from INPUT
    if (!($json = file_get_contents('php://input'))) {
        throw new \Exception('NoData');
    }
    
    if (!$response = json_decode($json)) {
        throw new \Exception('ProblemWithJSON');
    }
    
    // write JSON to file
    $filename = tempnam(RESPONSE_DIR, 'survey');
    if (!file_put_contents($filename, $json)) {
        throw new \Exception('ProblemWritingJSON');
    }
    
    // ingest response
    $responseID = 0;
    $community = '';
    $respDate = '';
      
    $csv = [];
    
    $errors = ingest($response, $corrections, 
                     $responseID, $community, $respDate);
    
    if (0 != $responseID) {
        $csv = export($responseID);
    }
    else {
        $errors[] = ['NoCSV'];
    }
    
    email($errors, $csv, $community, $respDate);
    
    if (!$errors) {
        $message = 'success';
    }
    else {
        throw new \Exception(implode(' ', $errors[0]));
    }
}
catch (\Throwable $e) {
    header('HTTP/1.0 400 Bad Request', true, 400);
    $message = $e->getMessage();
}

header('Content-type: application/javascript; charset=utf-8');
print json_encode(['message' => $message]);

?>