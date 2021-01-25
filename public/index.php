<?php

declare(strict_types=1);

namespace PERUCOVID;

require_once 'includes/globals.php';
require_once 'includes/ingest.php';

$message = 'Unknown error';

try {
    // read JSON from INPUT
    if (!($json = file_get_contents('php://input'))) {
        throw new \Exception('No data sent');
    }
    
    if (!$response = json_decode($json)) {
        throw new \Exception('Problem decoding JSON string');
    }
    
    // ingest response
    ingest($response);
    $message = 'success';
}
catch (\Throwable $e) {
    header('HTTP/1.0 400 Bad Request', true, 400);
    $message = $e->getMessage();
}

header('Content-type: application/javascript; charset=utf-8');
print json_encode(['message' => $message]);

?>