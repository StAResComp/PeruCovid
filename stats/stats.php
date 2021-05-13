<?php

declare(strict_types=1);

namespace PERUCOVID;

require_once '../public/includes/globals.php';
require_once '../public/includes/email.php';

// get date from command line
$dat = $_SERVER['argv'][1];

// get CSV files in date directory
$files = glob(sprintf('%s/*.csv', $dat));

$sheets = [];

// loop over files found
foreach ($files as $f) {
    // get name for sheet
    $sheetName = basename($f, '.csv');
    
    // open file
    $fh = fopen($f, 'r');
    $sheet = [];
    
    // read rows from file into sheet array
    while (false !== ($row = fgetcsv($fh))) {
        $sheet[] = $row;
    }
    
    // add sheet to sheets
    $sheets[$sheetName] = $sheet;
}

emailStats($sheets);

?>