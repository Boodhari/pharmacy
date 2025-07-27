<?php
function sendWhatsApp($to, $message) {
    $instance_id = "instance132245"; // replace with your UltraMsg instance ID
    $token = "8e4pt7yy0bro4q19";             // replace with your UltraMsg token

    $url = "https://api.ultramsg.com/$instance_id/messages/chat";
    

    $data = [
        "token" => $token,
        "to" => $to,
        "body" => $message
    ];

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
    ]);

    $response = curl_exec($curl);
    $error = curl_error($curl);

    curl_close($curl);

    if ($error) {
        echo "❌ Error sending message: $error";
    } else {
        echo "✅ Message sent to $to <br>";
    }
}
?>