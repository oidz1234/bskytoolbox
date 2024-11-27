<?php
// Define the database file
$dbFile = 'database.sqlite';

try {
    // Create a new SQLite database connection
    $db = new PDO("sqlite:$dbFile");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Initialize variables
    $messages = [];
    $email = '';

    // Check if email is submitted
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $email = $_POST['email'];


        // Fetch messages for the user's phrases
        $fetchMessagesSQL = "
            SELECT m.message_text, m.source, m.timestamp, up.phrase 
            FROM messages m
            JOIN user_phrases up ON m.phrase_id = up.id
            WHERE up.email = :email
            ORDER BY m.timestamp DESC
        ";
        $stmt = $db->prepare($fetchMessagesSQL);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Messages - Bluesky Mention Tracker</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f0f4ff 0%, #e6e9ff 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
            padding: 2rem;
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        h1 {
            color: #1a1a1a;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        input, button {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }

        button {
            background: #4f46e5;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        button:hover {
            background: #4338ca;
        }

        .messages {
            margin-top: 2rem;
        }

        .message-item {
            background: #f8f8f8;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #4f46e5;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            color: #666;
            font-size: 0.9rem;
        }

        .no-messages {
            text-align: center;
            color: #666;
            padding: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>View Your Messages</h1>
            <i> This page does not auto refresh </i>
<br>
<p> For feedback <a href="mailto:mark@mcnally.je">Email me</a> </p>
        </div>

        <?php if ($_SERVER["REQUEST_METHOD"] != "POST"): ?>
        <form method="post">
            <input type="email" name="email" placeholder="Enter your email" required>
            <button type="submit">View Messages</button>
        </form>
         <?php endif; ?>

        <div class="messages">
            <?php if ($_SERVER["REQUEST_METHOD"] === "POST"): ?>
                <?php if (empty($messages)): ?>
                    <div class="no-messages">
                        No messages found for <?php echo htmlspecialchars($email); ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message-item">
                            <div class="message-header">
                                <span>Phrase: <?php echo htmlspecialchars($message['phrase']); ?></span>
                                <span><?php echo htmlspecialchars($message['timestamp']); ?></span>
                            </div>
                            <div class="message-text">
                                <?php echo htmlspecialchars($message['message_text']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
