<?php
require('config.php');
require('vendor/autoload.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

$message = "";

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $input = json_decode(file_get_contents('php://input'));
    
    $name = $input->name;
    $email = $input->email;
    $subject = $input->subject ?? 'Contact Form Submission';
    $userMessage = $input->message;
    
    if(empty($name) || empty($email) || empty($userMessage)){
        echo json_encode(['status' => false, 'message' => 'All fields are required']);
    }elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        echo json_encode(['status' => false, 'message' => 'Invalid email address']);
    }else{
        $mail = new PHPMailer();          
        $mail->isSMTP();                 
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $username; 
        $mail->Password   = $mail_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $port;           
        $mail->setFrom($email, $name);
        $mail->addAddress($contact_email, 'Portfolio Admin');

        $mail->isHTML(true);               
        $mail->Subject = $subject;
        $mail->Body    = "<h3>Message from: $name</h3>
                         <p><strong>Email:</strong> $email</p>
                         <p><strong>Message:</strong></p>
                         <p>$userMessage</p>";
        $mail->AltBody = "Message from: $name\nEmail: $email\n\n$userMessage";

        if(!$mail->send()){
            echo json_encode(['status' => false, 'message' => 'Failed to send email. Please try again later']);
        }else{
            echo json_encode(['status' => true, 'message' => 'Your message has been sent successfully']);
        }
    }
}

?>
 