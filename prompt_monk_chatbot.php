<?php
session_start();
require 'vendor/autoload.php';
require 'db_connect.php'; // Include your database connection file

use GeminiAPI\Client;
use GeminiAPI\Resources\Parts\TextPart;

header('Content-Type: text/html');

$responseData = ['error' => '', 'text' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['delete'])) {
        // Handle prompt deletion
        $id = $_POST['delete'];

        if (!empty($id)) {
            try {
                $stmt = $conn->prepare("DELETE FROM prompts_results WHERE id = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();
            } catch (Exception $e) {
                $responseData['error'] = 'Error deleting prompt: ' . $e->getMessage();
            }
        }
    } else {
        // Handle new prompt submission
        $prompt = $_POST['prompt'] ?? '';

        if (!empty($prompt)) {
            try {
                $client = new Client('AIzaSyDHUvGcRskCJWUQykkCHFKKM5li3AAD_H4'); // Replace with your actual API key

                $response = $client->geminiPro()->generateContent(new TextPart($prompt));
                $text = $response->text();

                // Format the response text
                $formattedText = formatText($text);

                // Save prompt and result to the database
                $stmt = $conn->prepare("INSERT INTO prompts_results (prompt, result) VALUES (?, ?)");
                $stmt->bind_param('ss', $prompt, $formattedText);
                $stmt->execute();
                $stmt->close();

                $responseData['text'] = $formattedText;
            } catch (Exception $e) {
                $responseData['error'] = 'Error generating text: ' . $e->getMessage();
            }
        } else {
            $responseData['error'] = 'Prompt cannot be empty.';
        }
    }
}

// Fetch all prompts and results from the database
$results = [];
$query = "SELECT * FROM prompts_results ORDER BY created_at DESC";
if ($result = $conn->query($query)) {
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    $result->free();
}

function formatText($text) {
    // Convert bold text
    $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);

    // Convert italic text
    $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);

    // Convert list items (assuming list items start with "* ")
    $text = preg_replace('/\* (.*?)(\n|$)/', '<li>$1</li>', $text);

    // Wrap list items with <ul> if there are list items
    if (strpos($text, '<li>') !== false) {
        $text = '<ul>' . $text . '</ul>';
    }

    // Replace newlines with paragraphs
    $text = preg_replace('/\n\n/', '</p><p>', $text);

    // Wrap text in <p> tags if not already wrapped
    if (!preg_match('/^<p>.*<\/p>$/s', $text)) {
        $text = '<p>' . $text . '</p>';
    }

    // Convert newlines to <br> for better readability
    $text = nl2br($text);

    return $text;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prompt Monk Chat BOT</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
       body {
            background-color: #F7EFE5;
            color: #674188;
        }
        .chat-container {
            background-color: #ffffff;
            border-radius: 8px;
            width: 90%;
            max-width: 100%;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            height: 900px;
            position: relative;
            margin: auto;
            overflow: auto;
            padding: 30px;
        }
        .chat-header {
            background-color: #2F3645;
            color: #DCA47C;
            padding: 15px;
            border-radius: 8px 8px 0 0;
            font-size: 18px;
            text-align: center;
        }
        .results {
            padding: 15px;
            flex: 1;
            overflow-y: auto;
            border-bottom: 1px solid #ddd;
        }
        .result {
            background-color: #F1F1F1;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            word-wrap: break-word;
            position: relative; /* For positioning delete button */
        }
        .userprompt {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .input-container {
            display: flex;
            padding: 15px;
            border-top: 1px solid #ddd;
            background-color: #F9F9F9;
            position: absolute;
            bottom: 0;
            width: 95%;
        }
        textarea {
            width: 100%;
            height: 60px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: none;
            font-size: 16px;
            box-sizing: border-box;
            margin-right: 10px;
        }
        button {
            background-color: #2F3645;
            color: #EEEDEB;
            border: none;
            border-radius: 4px;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #1C1D21;
        }
        .delete-button {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            color: #DC3545;
            font-size: 20px;
            cursor: pointer;
        }
        .delete-button:hover {
            color: #C82333;
        }
        .formatted-text {
            margin: 10px 0;
        }
        .formatted-text h2 {
            font-size: 15px;
        }
        
        /* Loader Styles */
        .loader {
            border: 16px solid #f3f3f3; /* Light grey */
            border-top: 16px solid #3498db; /* Blue */
            border-radius: 50%;
            width: 120px;
            height: 120px;
            animation: spin 1s linear infinite;
            position: absolute;
            top: 50%;
            left: 50%;
            margin-top: -60px;
            margin-left: -60px;
            z-index: 1000;
            display: none; /* Hidden by default */
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="chat-container">
        <div class="chat-header">Gemini AI</div>
        <div class="results" id="results">
            <?php foreach ($results as $result): ?>
                <div class="result">
                    <div class="userprompt"><?php echo htmlspecialchars($result['prompt']); ?></div>
                    <div class="response formatted-text"><?php echo $result['result']; ?></div>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="delete" value="<?php echo htmlspecialchars($result['id']); ?>">
                        <button type="submit" class="delete-button" onclick="return confirm('Are you sure you want to delete this prompt?')">&times;</button>
                    </form>
                </div>
                <br>
            <?php endforeach; ?>
        </div>
        <br>
        
        <div class="input-container">
            <form method="post" style="width: 100%;">
                <textarea name="prompt" placeholder="Enter your prompt here..." required></textarea>
                <button type="submit">Generate</button>
            </form>
        </div>
    </div>
    
    <!-- Loader Element -->
    <div class="loader" id="loader"></div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const loader = document.getElementById('loader');

        form.addEventListener('submit', function() {
            loader.style.display = 'block'; // Show loader
        });
    });
    </script>
</body>
</html>
