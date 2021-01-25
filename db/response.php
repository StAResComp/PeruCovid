<?php

declare(strict_types=1);

namespace PERUCOVID;

require_once '../public/includes/globals.php';

// read field information
$fieldFile = 'fields.json';
$fields = json_decode(file_get_contents($fieldFile));

// connect to database
$db = DB::getInstance();

do {
    $responseFile = '../dummy/dummy1.json';
    $response = json_decode(file_get_contents($responseFile));
    // need identifier and week
    if (!isset($response->identifier) &&
        !isset($response->weekStart)) {
        print "Need identifier and start of week\n";
        break;
    }
    
    // get monitora ID
    $resp = $db->getMonitora($response->identifier);
    if (!isset($resp[0])) {
        print "Unrecognised monitora\n";
        break;
    }
    $monitoraID = $resp[0]->monitora_id;
    
    // get week ID
    $resp = $db->getWeek($response->weekStart);
    $weekID = $resp[0]->week_id;
    
    // create new response
    $resp = $db->addResponse($monitoraID, $weekID);
    $responseID = $resp[0]->response_id;
    
    foreach ($response as $field => $value) {
        if (!$value ||
            !isset($fields->$field) ||
            'question' != $fields->$field->type) {
            continue;
        }
        
        // get information about question
        $qResp = $db->getQuestion($field);
        
        // series of answers
        if (isset($fields->$field->series)) {
            $l = count($qResp);
            $answers = explode(',', $value);
            
            for ($i = 0; $i < $l; ++ $i) {
                $string = trim($answers[$i]);
                
                if (!$string) {
                    continue;
                }
                
                $numeric = 'numeric' == $fields->$field->data 
                  ? $string : NULL;
                $db->addAnswer($responseID, $qResp[$i]->question_id, 
                               $numeric, $string);
            }
        }
        // single answer
        else {
            $numeric = 'numeric' == $fields->$field->data 
              ? $value : NULL;
            $db->addAnswer($responseID, $qResp[0]->question_id,
                           $numeric, $value);
        }
    }
} while (False);

?>