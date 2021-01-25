<?php

declare(strict_types=1);

namespace PERUCOVID;

require_once '../public/includes/globals.php';

// read field information
$fields = json_decode(file_get_contents('php://stdin'));

// connect to database
$db = new DB();

foreach ($fields as $field => $params) {
    switch ($params->type) {
        // series information
     case 'series':
        // add series
        $resp = $db->addSeries($field);
        $seriesID = $resp[0]->series_id;
        
        // add items in series
        $items = explode(',', $params->items);
        foreach ($items as $i => $item) {
            $resp = $db->addSeriesItem($seriesID, trim($item), $i + 1);
        }
        break;
        
        // question information
     case 'question':
        // question uses a series
        if (isset($params->series)) {
            $seriesItems = $db->getSeries($params->series);
            foreach ($seriesItems as $item) {
                $db->addQuestion($field, $item->item_id, $params->data);
            }
        }
        // single question
        else {
            $db->addQuestion($field, NULL, $params->data);
        }
        break;
        
     default:
        break;
    }
}

// commit changes
$db->commit();

?>