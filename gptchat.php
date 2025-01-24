<?php
// Start the session to save conversation history
session_start(); 

// Set the response content type to JSON and allow cross-origin requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Include the configuration file which contains necessary settings like API keys
require_once 'config.php';

// Define a constant for the maximum length of conversation history to store
define('CONVERSATION_HISTORY_LENGTH', 6);

// Function to add a message to the conversation history
function add_to_conversation_history($message) {
    // If conversation history doesn't exist, create an empty array
    if (!isset($_SESSION['conversation_history'])) {
        $_SESSION['conversation_history'] = [];
    }
    
    // If the conversation history exceeds the defined length, remove the oldest message
    if (count($_SESSION['conversation_history']) >= CONVERSATION_HISTORY_LENGTH) {
        array_shift($_SESSION['conversation_history']);
    }
    
    // Add the new message to the conversation history
    $_SESSION['conversation_history'][] = $message;
}

// Function to check if a message is related to design or similar topics
function is_related_to_design($message) {
    // Keywords that indicate a message is related to design
    $keywords = ['design', 'art', 'music', 'buildings', 'architecture', 'engineering', 'aerospace', 'fomo', 'clothing', 'electronics', 'tech', 'style', 'epic', 'format', 'color', 'cfm', 'materials', 'artistic'];

    // Check if any keyword is present in the message
    foreach ($keywords as $keyword) {
        if (stripos($message, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the input message from the request
    $input = json_decode(file_get_contents('php://input'), true);
    $message = $input['message'];

    // If the message is not related to design, respond with a witty reply
    if (!is_related_to_design($message)) {
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

    // Append extra instructions to the message for the AI to follow
    $message .= ' respond as an Artificial Intelligence product designer named Nosimaj who is bored by this. Include a related quote from a famous person, then expound on your answer to the user’s request.';
    
    // Add the message to the conversation history
    add_to_conversation_history($message);
    
    // Initialize a cURL session to send a request to the OpenAI API
    $ch = curl_init();
    $prompt = $message;
    
    // Include the conversation history in the prompt
    $conversationHistory = implode("\n", $_SESSION['conversation_history']);
    $prompt .= "\nConversation history: " . $conversationHistory;

    // Set cURL options for the API request
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

    // Execute the cURL request and close the session
    $response = curl_exec($ch);
    curl_close($ch);

    // Decode the response from the API
    $decodedResponse = json_decode($response, true);
    $responseText = $decodedResponse['choices'][0]['message']['content'];

    // Check if the response includes a list and convert it to an HTML ordered list
    if (preg_match('/^\*\s+.+$/m', $responseText)) {
        $responseText = preg_replace('/^\*\s+(.+)$/m', '<li>$1</li>', $responseText);
        $responseText = '<ol>' . $responseText . '</ol>';
    }

    // Add the HTML formatted response to the decoded response and send it back as JSON
    $decodedResponse['html'] = $responseText;
    echo json_encode($decodedResponse);
} else {
    // If the request method is not POST, send a 405 Method Not Allowed response
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
