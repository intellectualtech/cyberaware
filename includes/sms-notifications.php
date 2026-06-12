<?php
/**
 * SMS Notification Functions
 * Handles sending SMS messages to users
 * 
 * Note: This uses a placeholder SMS service. 
 * For production, integrate with services like:
 * - Twilio
 * - AWS SNS
 * - Nexmo/Vonage
 * - Local SMS gateway
 */

/**
 * Send SMS notification
 * 
 * @param string $phone_number Phone number to send to
 * @param string $message Message content
 * @return bool Success status
 */
function sendSMS($phone_number, $message) {
    // Placeholder for SMS sending
    // In production, integrate with actual SMS service
    
    // Example with Twilio (uncomment and configure):
    /*
    require_once 'vendor/autoload.php';
    use Twilio\Rest\Client;
    
    $account_sid = getenv('TWILIO_ACCOUNT_SID');
    $auth_token = getenv('TWILIO_AUTH_TOKEN');
    $twilio_number = getenv('TWILIO_PHONE_NUMBER');
    
    $client = new Client($account_sid, $auth_token);
    
    try {
        $client->messages->create(
            $phone_number,
            [
                'from' => $twilio_number,
                'body' => $message
            ]
        );
        return true;
    } catch (Exception $e) {
        error_log("SMS Error: " . $e->getMessage());
        return false;
    }
    */
    
    // For now, log the SMS that would be sent
    $log_message = "[SMS] To: $phone_number | Message: $message | Time: " . date('Y-m-d H:i:s');
    error_log($log_message);
    
    // Return true to indicate success (in production, check actual service response)
    return true;
}

/**
 * Send demo approval SMS
 * 
 * @param string $phone_number User's phone number
 * @param string $name User's name
 * @param string $company Company name
 * @param array $modules Selected module names
 * @return bool Success status
 */
function sendDemoApprovalSMS($phone_number, $name, $company, $modules = []) {
    $modules_text = !empty($modules) ? implode(', ', array_slice($modules, 0, 3)) : 'selected modules';
    if (count($modules) > 3) {
        $modules_text .= ' and ' . (count($modules) - 3) . ' more';
    }
    
    $message = "Hi $name! 🎉 Your CyberAware demo for $company has been APPROVED! " .
               "You'll get access to: $modules_text. " .
               "David will contact you shortly. Visit: cyberaware.local";
    
    return sendSMS($phone_number, $message);
}

/**
 * Send demo scheduled SMS
 * 
 * @param string $phone_number User's phone number
 * @param string $name User's name
 * @param string $date_time Scheduled date and time
 * @return bool Success status
 */
function sendDemoScheduledSMS($phone_number, $name, $date_time) {
    $message = "Hi $name! Your CyberAware demo is scheduled for $date_time. " .
               "We're excited to show you our platform! See you then! 🚀";
    
    return sendSMS($phone_number, $message);
}

/**
 * Send training module access SMS
 * 
 * @param string $phone_number User's phone number
 * @param string $name User's name
 * @param string $username Login username
 * @return bool Success status
 */
function sendTrainingAccessSMS($phone_number, $name, $username) {
    $message = "Hi $name! Your CyberAware training is ready! " .
               "Login with username: $username at cyberaware.local " .
               "Start learning now! 📚";
    
    return sendSMS($phone_number, $message);
}

?>
