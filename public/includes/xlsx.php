<?php

declare(strict_types=1);

namespace PERUCOVID;

require_once 'SimpleXLSXGen.php';

/****f* xlsx.php/xlsx
 * NAME
 * xlsx
 * SYNOPSIS
 * Create XLSX file using given sheets arrays
 * ARGUMENTS
 *   * sheets - array - associative array of 2D arrays, using keys for names of sheets
 * RETURN VALUE
 * XLSX as string, base64 encoded and split for email attachment
 ******
 */
function xlsx(array $sheets) : string { //{{{
    // create spreadsheet
    $xlsx = new \SimpleXLSXGen();

    // add sheets for each community
    foreach ($sheets as $name => $sheet) {
        $xlsx->addSheet($sheet, $name);
    }
    
    // send back XLSX as encoded and split string
    return chunk_split(base64_encode((string) $xlsx));
}
//}}}

?>