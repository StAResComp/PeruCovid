#!/bin/bash

# get today's date
DATE=`date "+%Y-%m-%d"`

# create directory for CSVs
mkdir -p ${DATE}

# run R code to generate CSVs
./stats.R ${DATE}  2> /dev/null

# run PHP code to generate .xlsx and email to recipients
php -f stats.php ${DATE}

