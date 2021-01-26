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
    if (!isset($response->identifier) ||
        !isset($response->weekStart)) {
        throw new \Exception('MissingIdentifierAndWeek');
    }
    
    // connect to DB - need to commit on success
    $db = new DB();
    
    // get monitora ID
    if (!($mResp = $db->getMonitora($response->identifier))) {
        throw new \Exception(sprintf('UnrecognisedMonitora|%s',
                                     $response->identifier));
    }
    
    // get week ID
    if (!($wResp = $db->getWeek($response->weekStart))) {
        throw new \Exception(sprintf('UnrecognisedDate|%s',
                                     $response->identifier));
    }
    
    // create new response
    $resp = $db->addResponse($mResp[0]->monitora_id,
                             $wResp[0]->week_id);
    $responseID = $resp[0]->response_id;
    
    // loop over fields in response
    foreach ($response as $field => $value) {
        // get information about question if value not empty
        if (!$value || !($qResp = $db->getQuestion($field))) {
            continue;
        }
        
        // series of answers, so split on ',', or single answer
        $answers = $qResp[0]->item_id ? explode(',', $value) : [$value];

        // make sure number of answers matches number of items in series
        if (count($answers) != count($qResp)) {
            throw new \Exception(sprintf('ItemCountMismatch|%s|%s',
                                         $response->identifier,
                                         $field));
        }
            
        foreach ($answers as $i => $answer) {
            // trim strings
            $answer = is_string($answer) ? trim($answer) : $answer;
            
            $resp = $db->addAnswer($responseID, 
                                   $qResp[$i]->question_id, 
                                   'numeric' == $qResp[$i]->data_type 
                                   ? $answer : NULL, 
                                   $answer);
            if (!$resp || !$resp[0]->updated) {
                throw new \Exception(sprintf('ProblemAddingAnswer|%s|%s|%s',
                                             $response->identifier,
                                             $field, $answer));
            }
        }
    }
    
    // got here, so commit on success
    $db->commit();
}
//}}}

?>