<?php

declare(strict_types=1);

namespace PERUCOVID;

/****f* ingest.php/ingest
 * NAME
 * ingest
 * SYNOPSIS
 * Add response to database
 * ARGUMENTS
 * response - stdClass - JSON data as PHP object
 * RETURN VALUE
 * None
 ******
 */
function ingest(\stdClass $response) { //{{{
    // need identifier and week
    if (!isset($response->identifier) &&
        !isset($response->weekStart)) {
        throw new \Exception('Need identifier and week');
    }
    
    // connect to DB - need to commit on success
    $db = new DB();
    
    // get monitora ID
    $mResp = $db->getMonitora($response->identifier);
    if (!isset($mResp[0])) {
        throw new \Exception('Unrecognised monitora');
    }
    
    // get week ID
    $wResp = $db->getWeek($response->weekStart);
    if (!isset($wResp[0])) {
        throw new \Exception('Unrecognised date');
    }
    
    // create new response
    $resp = $db->addResponse($mResp[0]->monitora_id,
                             $wResp[0]->week_id);
    $responseID = $resp[0]->response_id;
    
    // loop over fields in response
    foreach ($response as $field => $value) {
        // reasons to skip this field
        if (!$value) {
            continue;
        }

        // get information about question
        if (!($qResp = $db->getQuestion($field))) {
            continue;
        }
        
        // series of answers, so split on ',', or single answer
        $answers = $qResp[0]->item_id ? explode(',', $value) : [$value];

        // make sure number of answers matches number of items in series
        if (count($answers) != count($qResp)) {
            throw new \Exception('Incorrect number of items in answer');
        }
            
        foreach ($answers as $i => $answer) {
            $resp = $db->addAnswer($responseID, 
                                   $qResp[$i]->question_id, 
                                   'numeric' == $qResp[$i]->data_type 
                                   ? $answer : NULL, 
                                   $answer);
            if (!$resp[0]->updated) {
                throw new \Exception('Problem adding answer');
            }
        }
    }
    
    // got here, so commit on success
    $db->commit();
}
//}}}

?>