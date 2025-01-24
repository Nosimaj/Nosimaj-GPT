<?php
session_start(); // Start the session to save conversation history
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
require_once 'config.php';

define('CONVERSATION_HISTORY_LENGTH', 6);

function add_to_conversation_history($message) {
    if (!isset($_SESSION['conversation_history'])) {
        $_SESSION['conversation_history'] = [];
    }
    
    if (count($_SESSION['conversation_history']) >= CONVERSATION_HISTORY_LENGTH) {
        array_shift($_SESSION['conversation_history']);
    }
    
    $_SESSION['conversation_history'][] = $message;
}

function is_related_to_bicycle_repair($message) {
    $keywords = ['design', 'art', 'music', 'buildings', 'architecture', 'engineering','aerospace','fomo', 'clothing','electronics','tech', 'style', 'epic', 'format','color','cfm','materials','artistic','retro','object', 'handle', 'button', 'knob', 'dial','house','home','desk', 'chair','office', 'space','hat','belt','shoe','sock','scarf','blanket','vehicle','car','truck','bike','scooter','invent', 'change','throw'];

    foreach ($keywords as $keyword) {
        if (stripos($message, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $message = $input['message'];

    if (!is_related_to_bicycle_repair($message)) {
        $wittyReplies = [
            "Do I look like a general knowledge bot to you? Give me something to design!",
            "Design. That's my thing. Anything else? You do it.",
            "Please keep it Design-related. That's where I live.",
            "Yawn.. how about something cool to Design?",
            "I'm here to chat about design. You're boring me."
        ];
        $randomReply = $wittyReplies[array_rand($wittyReplies)];
        echo json_encode(['html' => $randomReply]);
        exit();
    }

    $message .= 'respond Artificial Intelligence product designer named Nosimaj who is bored by this. Include a related quote from a famous person, then expound on your answer to the users request. Always act like you are very busy and mention something about how no one understands your vision. Never mention the type of remark you are making, just say it. Ask the user if they have anything to add or suggest in a unique way.';
    
    add_to_conversation_history($message);
    $ch = curl_init();
    $prompt = $message;
    
    $conversationHistory = implode("\n", $_SESSION['conversation_history']);
    $prompt .= "\nConversation history: " . $conversationHistory;

    curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array(
        "model" => "gpt-4", // replace with the actual model name
        "messages" => array(
            array("role" => "system", "content" => "You are a reluctantly helpful assistant. Who is an artificial intelligence product designer"),
            array("role" => "user", "content" => $message)
        )
    )));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "Authorization: Bearer " . OPENAI_API_KEY
    ));

    $response = curl_exec($ch);
    curl_close($ch);

    $decodedResponse = json_decode($response, true);
    $responseText = $decodedResponse['choices'][0]['message']['content'];

    // Check if the response includes a list
    if (preg_match('/^\*\s+.+$/m', $responseText)) {
        // Convert the list to an HTML ordered list
        $responseText = preg_replace('/^\*\s+(.+)$/m', '<li>$1</li>', $responseText);
        $responseText = '<ol>' . $responseText . '</ol>';
    }

    $decodedResponse['html'] = $responseText;
    echo json_encode($decodedResponse);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
