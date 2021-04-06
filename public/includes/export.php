<?php

declare(strict_types=1);

namespace PERUCOVID;

/****f* export.php/export
 * NAME
 * ingest
 * SYNOPSIS
 * Export response as CSV
 * ARGUMENTS
 * responseID - integer - ID of response
 * RETURN VALUE
 * Array
 ******
 */
function export(int $responseID) : array { //{{{
    // connect to DB, getting data as numerically indexed array
    $db = new DB();
    $db->setFetch(\PDO::FETCH_NUM);
    
    return $db->export($responseID);
}
//}}}