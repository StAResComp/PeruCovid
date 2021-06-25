<?php

declare(strict_types=1);

namespace PERUCOVID;

require_once '../public/includes/globals.php';
require_once '../public/includes/ingest.php';
require_once '../public/includes/email.php';

$message = 'UnknownError';

try {
    // read JSON from INPUT
    if (!($json = file_get_contents('php://stdin'))) {
        throw new \Exception('NoData');
    }
    
    if (!$response = json_decode($json)) {
        throw new \Exception('ProblemWithJSON');
    }
    
    // ingest response
    $responseID = 0;
    $community = '';
    $respDate = '';
    
    ingest($response, false, $responseID, $community, $respDate);
    
    $message = 'success';
}
catch (\Throwable $e) {
    $message = $e->getMessage();
}

print json_encode(['message' => $message]);

?>