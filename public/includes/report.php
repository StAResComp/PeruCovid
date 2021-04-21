<?php

declare(strict_types=1);

namespace PERUCOVID;

require_once 'email.php';

// rotate array 90 degrees clockwise
// https://stackoverflow.com/questions/30087158/how-can-i-rotate-a-2d-array-in-php-by-90-degrees
// more of a transposition than a rotation

// generate report with responses grouped by community, ordered by date
function report() { //{{{
    // answers to reorder
    $reorder = ['landing' => 'Species'];
    $reorders = [];
    
    // connect to database, no transaction right now
    $db = new DB(false);
    $db->setFetch(\PDO::FETCH_NUM);
    
    // get data needed for reordering
    foreach ($reorder as $q => $i) {
        $reorders[$q] = $db->getQuestion($q);
    }
    
    // get community data and remove header row
    $cResp = $db->getCommunities();
    array_shift($cResp);
    
    // array to hold full data from all communities
    $sheets = [];
    $all = [];
    
    // loop over communities
    foreach ($cResp as $j => $c) {
        // get responses for community and remove header row
        $rResp = $db->getResponses((int) $c[0]);
        array_shift($rResp);
        
        $sheet = [];
        
        // loop over responses for community
        foreach ($rResp as $i => $r) {
            // start transaction
            $db->beginTransaction();
            
            // reorder some rows
            foreach ($reorder as $question => $item) {
                $db->reorder($r[0], $question, $item);
            }
            
            // get data for response and remove header row
            $resp = $db->report($r[0]);
            array_shift($resp);
            
            // rollback database
            $db->rollback();
            
            // transpose response array
            $resp = array_map(NULL, ... $resp);
            
            // only keep 3rd row after first response
            if ($i > 0) {
                $resp = [$resp[2]];
            }
            
            $sheet = array_merge($sheet, $resp);
        }
        
        $sheets[$c[1]] = $sheet;
        
        // remove first 2 rows from sheets after first one
        if ($j > 0) {
            unset($sheet[0]);
            unset($sheet[1]);
        }
        
        $all = array_merge($all, $sheet);
    }
    
    $sheets['all'] = $all;
    
    // landing data
    $lResp = $db->landings();
    
    // add headers
    $headers = array_shift($lResp);
    for ($i = 0; $i < 56; ++ $i) {
        $headers = array_merge($headers, array_slice($headers, 2, 9));
    }
    
    $landing = [$headers];
    
    // start with first row
    $landing[] = array_shift($lResp);
    $l = 1;
    
    foreach ($lResp as $row) {
        // different community/week
        if ($row[0] != $landing[$l][0] || $row[1] != $landing[$l][1]) {
            // start new row
            $landing[] = $row;
            ++ $l;
        }
        else {
            $landing[$l] = array_merge($landing[$l], array_slice($row, 2));
        }
    }
    
    $sheets['landing'] = $landing;
    
    // send report
    emailReport($sheets);
}
//}}}

?>