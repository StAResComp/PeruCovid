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
function ingest(\stdClass $response) : array { //{{{
    // delimiters for multi-answer fields
    $delims = ['|', ','];
    
    // array to hold errors
    $errors = [];
    
    // need community identifier and week
    if (!isset($response->community) ||
        !isset($response->weekStart)) {
        $errors[] = ['MissingCommunityOrWeek'];
    }
    
    // connect to DB - need to commit on success
    $db = new DB();
    
    // get community ID
    $communityID = NULL;
    if (!($cResp = $db->getCommunity($response->community))) {
        $errors[] = ['UnrecognisedCommunity', $response->community];
    }
    else {
        $communityID = $cResp[0]->community_id;
    }
    
    // get week ID
    if (!($wResp = $db->getWeek($response->weekStart))) {
        $errors[] = ['UnrecognisedDate', $response->community];
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
            $errors[] = ['ItemCountMismatch',
                         $response->community,
                         $field];
            continue;
        }

        $repeatingID = NULL;
        
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
                
                $answer = explode($delims[1], $answer);
            
                // get structure ID for multiple answers
                $sResp = $db->addStructure();
                $structureID = $sResp[0]->structure_id;
            }
            else {
                $answer = [$answer];
            }

            // is answer repeating
            if (NULL == $repeatingID && $qResp[0]->is_repeating) {
                $rResp = $db->addRepeating();
                $repeatingID = $rResp[0]->repeating_id;
            }
            
            foreach ($answer as $a) {
                // trim answer
                $a = is_string($a) ? trim($a) : $a;
                if ('' == $a) {
                    continue;
                }
                
                // get numeric value if needed
                $n = ((string) floatval($a) == $a) ? floatval($a) : NULL;
                
                $resp = $db->addAnswer($responseID, 
                                       $qResp[$i]->question_id, 
                                       $structureID, $repeatingID,
                                       $n, $a);
            
                if (!$resp || !$resp[0]->updated) {
                    $errors[] = ['ProblemAddingAnswer', $response->community,
                                 $field, $a];
                }
            }
        }
    }
    
    // got here, so commit on success
    $db->commit();
    
    return $errors;
}
//}}}

?>