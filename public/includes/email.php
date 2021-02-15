<?php

declare(strict_types=1);

namespace PERUCOVID;

/****f* email.php/email
 * NAME
 * email
 * SYNOPSIS
 * Send error to predefined recipients
 * ARGUMENTS
 * errors - array - array of error messages
 * RETURN VALUE
 * None
 ******
 */
function email(array $errors) { //{{{
    // read strings from ini file
    $ini = parse_ini_file(INI_FILE);
    $nl = "\r\n";
    
    // format message
    $message = '';
    
    foreach ($errors as $error) {
        $messageKey = array_shift($error);
        $message .= vsprintf($ini['strings'][$messageKey], $error) . $nl;
    }
    
    return mail(implode(', ', $ini['recipients']),
                $ini['subject'],
                wordwrap($message, 70, $nl));
}
//}}}