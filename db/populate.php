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
        
        // some questions in series might be multiple
        $multiple = isset($params->multiple) ? $params->multiple 
          : array_fill(0, count($params->items), 'false');
        
        // add items in series
        foreach ($params->items as $i => $item) {
            $db->addSeriesItem($seriesID, trim($item), $i + 1, 
                               $multiple[$i]);
        }
    }
    // question information
    else {
        // question is repeating
        $repeating = isset($params->repeating) ? 'true' : 'false';
        
        // question uses a series
        if (isset($params->series)) {
            $seriesItems = $db->getSeries($params->series);
            foreach ($seriesItems as $item) {
                $db->addQuestion($field, $item->item_id, $repeating,
                                 $item->is_multiple ? 'true' : 'false');
            }
        }
        // single question
        else {
            $db->addQuestion($field, NULL, $repeating, 'false');
        }
    }
}

// commit changes
$db->commit();

?>