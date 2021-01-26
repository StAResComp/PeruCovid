<?php

declare(strict_types=1);

namespace PERUCOVID;

require_once '../includes/globals.php';
require_once '../includes/ingest.php';
require_once '../includes/email.php';

$message = 'UnknownError';

try {
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
    try {
        ingest($response);
        $message = 'success';
    }
    catch (\Throwable $e) {
        // split error message on |
        $strings = explode('|', $e->getMessage());
        
        // email error
        email($strings);
        
        throw new \Exception($strings[0]);
    }
}
catch (\Throwable $e) {
    header('HTTP/1.0 400 Bad Request', true, 400);
    $message = $e->getMessage();
}

header('Content-type: application/javascript; charset=utf-8');
print json_encode(['message' => $message]);

?>