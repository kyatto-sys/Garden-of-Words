<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Use Composer's autoloader
require $_SERVER['DOCUMENT_ROOT'] . '/garden-of-words/vendor/autoload.php';

function sendVerificationEmail($to_email, $username, $verification_token) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';  // Gmail SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'k.angelfarofaldane@gmail.com';  
        $mail->Password   = 'gjyj lvsy olnm eyys';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Anti-spam settings
        $mail->SMTPDebug  = 0; // Disable debug output
        $mail->Debugoutput = 'html';
        $mail->CharSet    = 'UTF-8';
        
        // Recipients
        $mail->setFrom('your-email@gmail.com', 'Garden of Words');
        $mail->addAddress($to_email, $username);
        $mail->addReplyTo('your-email@gmail.com', 'Garden of Words Support');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Garden of Words Account';
        $mail->Priority = 3; // Normal priority (not urgent)
        
        $verification_link = "http://" . $_SERVER['HTTP_HOST'] . "/garden-of-words/verify.php?token=" . $verification_token;
        
        $mail->Body = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Email Verification</title>
        </head>
        <body style='margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f5f5f5;'>
            <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color: #f5f5f5; padding: 20px 0;'>
                <tr>
                    <td align='center'>
                        <table width='600' cellpadding='0' cellspacing='0' border='0' style='background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);'>
                            <!-- Header -->
                            <tr>
                                <td style='background: linear-gradient(135deg, #66bb6a, #43a047); padding: 30px; text-align: center;'>
                                    <h1 style='color: #ffffff; margin: 0; font-size: 28px; font-weight: bold;'>🌿 Garden of Words</h1>
                                </td>
                            </tr>
                            
                            <!-- Body -->
                            <tr>
                                <td style='padding: 40px 30px;'>
                                    <h2 style='color: #2e7d32; margin: 0 0 20px 0; font-size: 24px;'>Welcome, " . htmlspecialchars($username) . "!</h2>
                                    <p style='color: #555555; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;'>
                                        Thank you for joining our matcha postcard community. We're excited to have you here!
                                    </p>
                                    <p style='color: #555555; font-size: 16px; line-height: 1.6; margin: 0 0 30px 0;'>
                                        To get started, please verify your email address by clicking the button below:
                                    </p>
                                    
                                    <!-- Button -->
                                    <table width='100%' cellpadding='0' cellspacing='0' border='0'>
                                        <tr>
                                            <td align='center' style='padding: 20px 0;'>
                                                <a href='" . $verification_link . "' style='display: inline-block; padding: 16px 36px; background: linear-gradient(135deg, #66bb6a, #43a047); color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px;'>Verify Email Address</a>
                                            </td>
                                        </tr>
                                    </table>
                                    
                                    <p style='color: #888888; font-size: 14px; line-height: 1.6; margin: 30px 0 0 0;'>
                                        Or copy and paste this link into your browser:
                                    </p>
                                    <p style='color: #2e7d32; font-size: 13px; word-break: break-all; background-color: #f5f5f5; padding: 12px; border-radius: 4px; margin: 10px 0 0 0;'>
                                        " . $verification_link . "
                                    </p>
                                </td>
                            </tr>
                            
                            <!-- Footer -->
                            <tr>
                                <td style='background-color: #f5f5f5; padding: 30px; text-align: center; border-top: 1px solid #e0e0e0;'>
                                    <p style='color: #888888; font-size: 14px; margin: 0 0 10px 0;'>
                                        This link will expire in 24 hours for security reasons.
                                    </p>
                                    <p style='color: #888888; font-size: 14px; margin: 0 0 10px 0;'>
                                        If you didn't create an account with Garden of Words, please ignore this email.
                                    </p>
                                    <p style='color: #aaaaaa; font-size: 12px; margin: 20px 0 0 0;'>
                                        © 2024 Garden of Words. All rights reserved.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Welcome to Garden of Words! Please verify your email by visiting: " . $verification_link;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}
?>