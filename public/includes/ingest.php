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
    // delimiters for multi-answer fields
    $delims = ['|', ','];
    
    // need community identifier and week
    if (!isset($response->community) ||
        !isset($response->weekStart)) {
        throw new \Exception('MissingCommunityOrWeek');
    }
    
    // connect to DB - need to commit on success
    $db = new DB();
    
    // get community ID
    if (!($cResp = $db->getCommunity($response->community))) {
        throw new \Exception(sprintf('UnrecognisedComunity|%s',
                                     $response->community));
    }
    
    // get week ID
    if (!($wResp = $db->getWeek($response->weekStart))) {
        throw new \Exception(sprintf('UnrecognisedDate|%s',
                                     $response->community));
    }
    
    // create new response
    $resp = $db->addResponse($cResp[0]->community_id,
                             $wResp[0]->week_id);
    $responseID = $resp[0]->response_id;
    
    // loop over fields in response
    foreach ($response as $field => $value) {
        // get information about question if value not empty
        if (!$value || !($qResp = $db->getQuestion($field))) {
            continue;
        }
        
        // series of answers, so split on delimiter, or single answer
        $answers = $qResp[0]->item_id ? 
          explode($delims[0], $value) : [$value];

        // make sure number of answers matches number of items in series
        if (count($answers) != count($qResp)) {
            throw new \Exception(sprintf('ItemCountMismatch|%s|%s',
                                         $response->community,
                                         $field));
        }
        
        foreach ($answers as $i => $answer) {
            // trim strings
            $answer = is_string($answer) ? trim($answer) : $answer;
            // check for empty answer
            if ('' == $answer) {
                continue;
            }
            
            $structureID = NULL;
            
            // is answer a multiple one?
            if ($qResp[$i]->is_multiple) {
                // all multiple answers empty
                if ('' == str_replace($delims[1], '', $answer)) {
                    continue;
                }
                
                $answer = explode($delims[1], $answer[0]);
            
                // get structure ID for multiple answers
                $sResp = $db->addStructure();
                $structureID = $sResp[0]->structure_id;
            }
            else {
                $answer = [$answer];
            }
            
            foreach ($answer as $a) {
                // trim answer
                $a = is_string($a) ? trim($a) : $a;
                if ('' == $a) {
                    continue;
                }
                
                // get numeric value if needed
                $n = ('numeric' == $qResp[$i]->data_type) 
                  && ((string) floatval($a) == $a) ? floatval($a) : NULL;
                
                $resp = $db->addAnswer($responseID, 
                                       $qResp[$i]->question_id, 
                                       $structureID,
                                       $n, $a);
            
                if (!$resp || !$resp[0]->updated) {
                    throw new \Exception(sprintf('ProblemAddingAnswer|%s|%s|%s',
                                                 $response->community,
                                                 $field, $a));
                }
            }
        }
    }
    
    // got here, so commit on success
    $db->commit();
}
//}}}

?>