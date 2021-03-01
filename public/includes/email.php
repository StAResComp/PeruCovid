<?php

declare(strict_types=1);

namespace PERUCOVID;

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
    $fh = fopen('php://memory', 'w');
    foreach ($csv as $row) {
        fputcsv($fh, $row);
    }
    
    rewind($fh);
    $attachment = stream_get_contents($fh);
    fclose($fh);
    $attachment = chunk_split(base64_encode($attachment));
    file_put_contents('/tmp/test.csv', $attachment);
    
    // prepare filename
    $filename = sprintf('%s_%s.csv', 
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
    $body[] = sprintf('Content-Type: text/csv; name="%s"',
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