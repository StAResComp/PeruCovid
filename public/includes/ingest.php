<?php

declare(strict_types=1);

namespace PERUCOVID;

/****f* ingest.php/ingest
 * NAME
 * ingest
 * SYNOPSIS
 * Add response to database
 * ARGUMENTS
 *   * response - stdClass - JSON data as PHP object
 *   * corrections - bool - true when correcting answers
 *   * responseID - integer - passed by reference
 *   * community - string - passed by reference
 *   * respDate - string - passed by reference
 * RETURN VALUE
 * Array of errors
 ******
 */
function ingest(\stdClass $response, bool $corrections, int &$responseID, string &$community, string &$respDate) : array { //{{{
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
    
    // make sure that weekStart is from last week (not for corrections)
    if (!$corrections) {
        $response->weekStart = lastMonday();
    }
    
    $community = $response->community;
    $respDate = $response->weekStart;
    
    // get community ID
    if (!($cResp = $db->getCommunity($response->community))) {
        //$errors[] = ['UnrecognisedCommunity', $response->community];
        $errors[] = ['UnrecognisedCommunity', print_r($db->getError(), true)];
    }
    
    // get week ID
    if (!($wResp = $db->getWeek($response->weekStart))) {
        $errors[] = ['UnrecognisedDate', 
                     $response->community, 
                     $response->weekStart];
    }
    
    // create new response
    $resp = NULL;
    
    if ($corrections) {
        $resp = $db->getResponse($cResp[0]->community_id,
                                 $wResp[0]->week_id);
    }
    else {
        $resp = $db->addResponse($cResp[0]->community_id,
                                 $wResp[0]->week_id);
    }
    
    $responseID = $resp[0]->response_id;
    
    // array to remember how many repeats an answer has had
    $repeats = [];
    
    // loop over fields in response
    foreach ($response as $field => $value) {
        // get information about question if value not empty
        if ('' === $value ||  // test for empty string, so that numeric 0s pass through
            '' == str_replace(array_merge($delims, [' ']), '', $value) ||
            !($qResp = $db->getQuestion($field))) {
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
        
        $qName = $qResp[0]->question_string;
        if (!isset($repeats[$qName])) {
            $repeats[$qName] = 0;
        }
        
        ++ $repeats[$qName];
        
        foreach ($answers as $i => $answer) {
            // trim string
            $answer = is_string($answer) ? trim($answer) : $answer;
            
            // check for empty answer or negative number
            if ('' === $answer || $answer < 0) {
                continue;
            }
            
            // trim answer
            $answer = is_string($answer) ? trim($answer) : $answer;
            if ('' === $answer) {
                continue;
            }
            
            // get numeric value if possible
            $n = ((string) floatval($answer) == $answer) 
              ? floatval($answer) : NULL;
            
            $resp = NULL;
            
            if ($corrections) {
                $resp = $db->correctAnswer($responseID, 
                                           $qResp[$i]->question_id, 
                                           $repeats[$qName],
                                           $n, $answer);
            }
            else {
                $resp = $db->addAnswer($responseID, 
                                       $qResp[$i]->question_id, 
                                       $repeats[$qName],
                                       $n, $answer);
            }
            
            if (!$resp || !$resp[0]->updated) {
                $errors[] = ['ProblemAddingAnswer', $response->community,
                             $field, $answer];
            }
        }
    }
    
    // got here, so commit on success
    $db->commit();
    
    return $errors;
}
//}}}

/****f* ingest.php/reorder
 * NAME
 * reorder
 * SYNOPSIS
 * Reorder repeated fields
 * ARGUMENTS
 *   * responseID - INTEGER - ID of response
 ******
 */
function reorder(int $responseID) { //{{{
    // question string and series item to use for ordering
    // e.g. reorder repeating 'landing' answers using 'Species' item
    $fields = ['landing' => 'Species'];
    
    // connect to database
    $db = new DB();
    
    foreach ($fields as $qField => $sField) {
        $db->reorder($responseID, $qField, $sField);
    }
}
//}}}

/****f* ingest.php/lastMonday
 * NAME
 * lastMonday
 * SYNOPSIS
 * Get date of last Monday
 * RETURN VALUE
 * Date string - last Monday
 ******
 */
function lastMonday() : string { //{{{
    // get today's day of the week
    $now = new \DateTime();
    $dayOfWeek = $now->format('w'); // Sunday = 0, Saturday = 6
    
    // how many days since this Monday
    $dayDiff = ($dayOfWeek + 6) % 7;
    // go back to this Monday
    $thisMonday = $now->sub(new \DateInterval(sprintf('P%dD',
                                                      $dayDiff)));
    // Sunday today, so go to tomorrow
    if ($dayOfWeek == 0) {
        $thisMonday = $thisMonday->add(new \DateInterval('P1W'));
    }
    
    // go to last Monday
    $lastMonday = $thisMonday->sub(new \DateInterval('P1W'));
    return $lastMonday->format('Y-m-d');
}
//}}}


?>