<?php

declare(strict_types=1);

namespace PERUCOVID;

/****f* email.php/email
 * NAME
 * email
 * SYNOPSIS
 * Send error to predefined recipients
 * ARGUMENTS
 * message - string - error message to send
 * RETURN VALUE
 * None
 ******
 */
function email(array $strings) { //{{{
    // read strings from ini file
    $ini = parse_ini_file(INI_FILE);
    
    // format message
    $messageKey = array_shift($strings);
    $message = vsprintf($ini['strings'][$messageKey], $strings);
    
    return mail(implode(', ', $ini['recipients']),
                $ini['subject'],
                wordwrap($message, 70, "\r\n"));
}
//}}}