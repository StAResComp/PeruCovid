<?php

declare(strict_types=1);

namespace PERUCOVID;

require_once 'xlsx.php';

/****f* email.php/email
 * NAME
 * email
 * SYNOPSIS
 * Send response to predefined recipients
 * ARGUMENTS
 *   * errors - array - array of error messages
 *   * csv - array - array to be turned into CSV string
 *   * community - string - name of community response is from
 *   * respDate - string - week response covers
 * RETURN VALUE
 * Boolean - true when email is sent
 * NOTES
 * https://stackoverflow.com/questions/12301358/send-attachments-with-php-mail
 ******
 */
function emailResponse(array $errors, array $csv, string $community, string $respDate) : bool { //{{{
    // read strings from ini file
    $ini = parse_ini_file(INI_FILE);
    // correct newline for email
    $nl = "\r\n";

    // format message
    $message = '';
    
    foreach ($errors as $error) {
        $messageKey = array_shift($error);
        $message .= vsprintf($ini['strings'][$messageKey], $error) . $nl;
    }
    
    // serialise CSV array
    $sheet = sprintf('%s %s', $community, $respDate);
    $attachment = xlsx([$sheet => $csv]);
    
    // prepare filename
    $filename = sprintf('%s_%s.xlsx', 
                        str_replace(' ', '', $community),
                        str_replace(' ', '', $respDate));
    
    return email($ini['sender']['name'], $ini['sender']['address'],
                 $ini['subject'], $ini['recipients'],
                 $message, $attachment, $filename);
}
//}}}

function emailReport(array $sheets) : bool { //{{{
    // read strings from ini file
    $ini = parse_ini_file(INI_FILE);
    // correct newline for email
    $nl = "\r\n";
    
    // get current date
    $today = date('Y-m-d');

    // format message and filename
    $message = sprintf('Report of responses submitted by %s%s', $today, $nl);
    $filename = sprintf('Report_%s.xlsx', $today);
    
    // serialise XLSX
    $attachment = xlsx($sheets);

    return email($ini['sender']['name'], $ini['sender']['address'],
                 $ini['report_subject'], $ini['report_recipients'],
                 $message, $attachment, $filename);
}
//}}}

function email(string $senderName, string $senderEmail, string $subject, array $recipients, string $message, string $attachment, string $filename) : bool { //{{{
    // string to separate content
    $sep = md5((string) time());
    // correct newline for email
    $nl = "\r\n";
    
    // header
    $headers = [];
    $headers[] = sprintf('From: %s <%s>', $senderName, $senderEmail);
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = sprintf('Content-Type: multipart/mixed; boundary="%s"', $sep);
    $headers[] = 'Content-Transfer-Encoding: 7bit';
    $headers[] = 'This is a MIME encoded message.';
    
    // message
    $body = [];
    $body[] = sprintf('--%s', $sep);
    $body[] = 'Content-Type: text/plain; charset="UTF-8"';
    $body[] = 'Content-Transfer-Encoding: 8bit';
    $body[] = '';
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

    return mail(implode(', ', $recipients),
                $subject,
                implode($nl, $body), implode($nl, $headers));
}
//}}}