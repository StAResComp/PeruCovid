<?php

declare(strict_types=1);

namespace PERUCOVID;

require_once 'SimpleXLSXGen.php';

// rotate array 90 degrees clockwise
// https://stackoverflow.com/questions/30087158/how-can-i-rotate-a-2d-array-in-php-by-90-degrees
// more of a transposition than a rotation

// generate report with responses grouped by community, ordered by date
function report() { //{{{
    // connect to database
    $db = new DB();
    $db->setFetch(\PDO::FETCH_NUM);
    
    // create spreadsheet
    $xlsx = new \SimpleXLSXGen();
    
    // get community data and remove header row
    $cResp = $db->getCommunities();
    array_shift($cResp);
    
    // array to hold full data from all communities
    $all = [];
    
    // loop over communities
    foreach ($cResp as $j => $c) {
        // get responses for community and remove header row
        $rResp = $db->getResponses((int) $c[0]);
        array_shift($rResp);
        
        $sheet = [];
        
        // loop over responses for community
        foreach ($rResp as $i => $r) {
            // get data for response and remove header row
            $resp = $db->report($r[0]);
            array_shift($resp);
            // transpose response array
            $resp = array_map(NULL, ...$resp);
            
            // only keep 3rd row after first response
            if ($i > 0) {
                $resp = [$resp[2]];
            }
            
            $sheet = array_merge($sheet, $resp);
        }

        $xlsx->addSheet($sheet, $c[1]);
        
        // remove first 2 rows from sheets after first one
        if ($j > 0) {
            unset($sheet[0]);
            unset($sheet[1]);
        }
        
        $all = array_merge($all, $sheet);
    }
    
    $xlsx->addSheet($all, 'All');
    
    $xlsx->saveAs('/tmp/test.xlsx');
}
//}}}

?>