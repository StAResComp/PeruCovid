<?php

declare(strict_types=1);

namespace PERUCOVID;

require_once 'SimpleXLSXGen.php';

/****f* email.php/email
 * NAME
 * email
 * SYNOPSIS
 * Send error to predefined recipients
 * ARGUMENTS
 *   * errors - array - array of error messages
 *   * csv - array - array to be turned into CSV string
 * RETURN VALUE
 * None
 * NOTES
 * https://stackoverflow.com/questions/12301358/send-attachments-with-php-mail
 ******
 */
function email(array $errors, array $csv, string $community, string $respDate) { //{{{
    // read strings from ini file
    $ini = parse_ini_file(INI_FILE);
    // correct newline for email
    $nl = "\r\n";
    // string to separate content
    $sep = md5((string) time());

    // format message
    $message = '';
    
    foreach ($errors as $error) {
        $messageKey = array_shift($error);
        $message .= vsprintf($ini['strings'][$messageKey], $error) . $nl;
    }
    
    // serialise CSV array
    $xlsx = \SimpleXLSXGen::fromArray($csv);

    $attachment = chunk_split(base64_encode((string) $xlsx));
    file_put_contents('/tmp/test.csv', $attachment);

    
    // prepare filename
    $filename = sprintf('%s_%s.xlsx', 
                        str_replace(' ', '', $community),
                        str_replace(' ', '', $respDate));
    
    $headers = [];
    $body = [];
    
    // header
    $headers[] = sprintf('From: %s <%s>', 
                         $ini['sender']['name'], $ini['sender']['address']);
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = sprintf('Content-Type: multipart/mixed; boundary="%s"',
                         $sep);
    $headers[] = 'Content-Transfer-Encoding: 7bit';
    $headers[] = 'This is a MIME encoded message.';
    
    // message
    $body[] = sprintf('--%s', $sep);
    $body[] = 'Content-Type: text/plain; charset="UTF-8"';
    $body[] = 'Content-Transfer-Encoding: 8bit';
    $body[] = $message;
    
    // attachment
    $body[] = sprintf('--%s', $sep);
    $body[] = sprintf('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; name="%s"',
                      $filename);
    $body[] = 'Content-Transfer-Encoding: base64';
    $body[] = 'Content-Disposition: attachment';
    $body[] = '';
    $body[] = $attachment;
    $body[] = sprintf('--%s--', $sep);
                        
    return mail(implode(', ', $ini['recipients']),
                $ini['subject'],
                implode($nl, $body), implode($nl, $headers));
}
//}}}