<?php

declare(strict_types=1);

namespace PERUCOVID;

require_once '../public/includes/globals.php';

// read field information
$fields = json_decode(file_get_contents('php://stdin'));

// connect to database
$db = new DB();

foreach ($fields as $field => $params) {
    // series information
    if (isset($params->items)) {
        // add series
        $resp = $db->addSeries($field);
        $seriesID = $resp[0]->series_id;
        
        // add items in series
        foreach ($params->items as $i => $item) {
            $db->addSeriesItem($seriesID, trim($item), $i + 1);
        }
    }
    // question information
    else {
        // get ID of meta question
        if (!($mResp = $db->addMetaQuestion())) {
            printf("error adding meta question\n");
        }
        $metaQuestionID = $mResp[0]->meta_question_id;
        
        // question is repeating
        $repeats = isset($params->repeats) ? $params->repeats : 1;
        
        // order of question
        $order = isset($params->order) ? $params->order : NULL;
        
        // question uses a series
        if (isset($params->series)) {
            $seriesItems = $db->getSeries($params->series);
            foreach ($seriesItems as $item) {
                if (!$db->addQuestion($metaQuestionID, $order,
                                      $field, $item->item_id, $repeats)) {
                    printf("error: %s, %d\n", $field, $item->item_id);
                    print_r($db->getError());
                }
            }
        }
        // single question
        else {
            if (!$db->addQuestion($metaQuestionID, $order, $field, NULL, 
                                  $repeats)) {
                printf("error: %s\n", $field);
                print_r($db->getError());
            }
        }
    }
}

// commit changes
$db->commit();

?>