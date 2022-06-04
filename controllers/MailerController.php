<?php

use PHPMailer\PHPMailer\PHPMailer;

require_once (__DIR__ . '/../vendor/autoload.php');
require_once (__DIR__ . '/../config/SmtpConfig.php');

function sendMail($subject, $message)
{
    $mail = new PHPMailer(true);
    try {
        //$mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
        $mail->isSMTP();                                            //Send using SMTP
        $mail->Host       = SmtpConfig::HOST;                     //Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
        $mail->Username   = SmtpConfig::USERNAME;                     //SMTP username
        $mail->Password   = SmtpConfig::PASSWORD;
        $mail->SMTPAutoTLS = false;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                              //SMTP password
        //$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
        $mail->Port       = SmtpConfig::PORT;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

        //Recipients
        $mail->setFrom(SmtpConfig::MAILFROM);
        $mail->addAddress(SmtpConfig::SENDTO);     //Add a recipient
        //Content
        $mail->isHTML(false);                                  //Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_host' => false,
                'verify_peer_name' => false,
            ],
        ];
        $mail->send();
    } catch (Exception $e) {
        $a = $e;
        return false;
    }
    return true;
}
