<?php
require('config.php');

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $input = json_decode(file_get_contents('php://input'));
    $email = $input->email;
    
    if(empty($email)){
        echo json_encode(['status' => false, 'message' => 'Email is required']);
    }elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        echo json_encode(['status' => false, 'message' => 'Invalid email address']);
    }else{
        $query = "SELECT * FROM `newsletter_subscribers` WHERE email='$email'";
        $result = mysqli_query($connection, $query);
        
        if(mysqli_num_rows($result) > 0){
            echo json_encode(['status' => false, 'message' => 'This email is already subscribed']);
        }else{
            $insertQuery = "INSERT INTO `newsletter_subscribers`(`email`, `subscribed_at`) VALUES ('$email', NOW())";
            $insert = mysqli_query($connection, $insertQuery);
            
            if($insert){
                echo json_encode(['status' => true, 'message' => 'Successfully subscribed to newsletter']);
            }else{
                echo json_encode(['status' => false, 'message' => 'Error subscribing to newsletter']);
            }
        }
    }
}

?>
    file_put_contents($subscribers_file, json_encode($subscribers, JSON_PRETTY_PRINT));
    
    // Send confirmation email to subscriber
    $subject = 'Newsletter Subscription Confirmation';
    $message = "Thank you for subscribing to my newsletter!\n\n";
    $message .= "You'll receive updates about my latest projects and blog posts.\n\n";
    $message .= "If you didn't subscribe, please ignore this email.\n";
    
    // Set email headers
    $headers = [];
    $headers[] = "From: " . FROM_EMAIL;
    $headers[] = "Reply-To: " . CONTACT_EMAIL;
    $headers[] = "Content-Type: text/plain; charset=UTF-8";
    
    // Send confirmation email using PHP mail()
    mail($email, $subject, $message, implode("\r\n", $headers));
    
    // Log successful subscription
    log_message("New newsletter subscriber: $email");
    
    // Send success response to frontend
    send_json_response([
        'success' => true,
        'message' => 'Successfully subscribed! Check your email for confirmation.'
    ]);
    
} catch (Exception $e) {
    // Log any errors that occur
    log_message("Newsletter subscription error: " . $e->getMessage(), 'ERROR');
    send_json_response([
        'success' => false,
        'error' => 'An error occurred'
    ], 500);
}
