<?php
// Define the database file
$dbFile = 'database.sqlite';

// Initialize message variables
$messageText = '';
$messageClass = '';

try {
    // Create a new SQLite database if it doesn't exist
    $db = new PDO("sqlite:$dbFile");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create tables if they don't exist
    $createTablesSQL = "
    -- Users table
    CREATE TABLE IF NOT EXISTS users (
        email TEXT PRIMARY KEY,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    -- User Phrases table
    CREATE TABLE IF NOT EXISTS user_phrases (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT,
        phrase TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (email) REFERENCES users(email),
        UNIQUE (email, phrase)
    );

    -- Messages table
    CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        phrase_id INTEGER,
        message_text TEXT,
        source TEXT,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (phrase_id) REFERENCES user_phrases(id)
    )";
    $db->exec($createTablesSQL);

    // Check if form was submitted
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        // Capture form values
        $email = $_POST['email'];
        $phrase = trim($_POST['phrase']); // Trim whitespace

        // Start a transaction
        $db->beginTransaction();

        try {
            // Insert user if not exists
            $insertUserSQL = "INSERT OR IGNORE INTO users (email) VALUES (:email)";
            $stmt = $db->prepare($insertUserSQL);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();

            // Only insert phrase if it's not empty and longer than 3 characters
            if (!empty($phrase) && strlen($phrase) > 3) {
                // Insert phrase for the user
                $insertPhraseSQL = "INSERT OR REPLACE INTO user_phrases (email, phrase) VALUES (:email, :phrase)";
                $stmt = $db->prepare($insertPhraseSQL);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':phrase', $phrase, PDO::PARAM_STR);
                $stmt->execute();

                $messageText = "Yay, you can now view your matches, just type your email in";
    echo '
    <form id="redirectForm" action="./mentions/view-messages.php" method="POST">
        <input type="hidden" name="email" value="' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '">
    </form>
    <script>
        document.getElementById("redirectForm").submit();
    </script>
    ';

                $messageClass = "success";
            } else {
                // Check if user already has phrases
                $checkPhrasesSQL = "SELECT COUNT(*) FROM user_phrases WHERE email = :email";
                $stmt = $db->prepare($checkPhrasesSQL);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->execute();
                $phraseCount = $stmt->fetchColumn();

                // Redirect to view messages if user has existing phrases
                if ($phraseCount > 0) {
    echo '
    <form id="redirectForm" action="./mentions/view-messages.php" method="POST">
        <input type="hidden" name="email" value="' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '">
    </form>
    <script>
        document.getElementById("redirectForm").submit();
    </script>
    ';
    exit;

                } else {
                    $messageText = "Please enter a phrase longer than 3 characters";
                    $messageClass = "error";
                }
            }

            // Commit the transaction
            $db->commit();
        } catch (PDOException $e) {
            // Rollback the transaction on error
            $db->rollBack();
            $messageText = "Failed to process your request: " . $e->getMessage();
            $messageClass = "error";
        }
    }
} catch (PDOException $e) {
    // Handle database connection and query errors
    $messageText = "Database error: " . $e->getMessage();
    $messageClass = "error";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bluesky Mention Tracker</title>
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
            max-width: 400px;
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

        .subtitle {
            color: #666;
            font-size: 1rem;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        label {
            color: #4a4a4a;
            font-size: 0.9rem;
            font-weight: 500;
        }

        input {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        input:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.1);
        }

        button {
            background: #4f46e5;
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        button:hover {
            background: #4338ca;
        }

        .divider {
            margin: 2rem 0;
            position: relative;
            text-align: center;
        }

        .divider::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #ddd;
        }

        .divider span {
            background: white;
            padding: 0 1rem;
            color: #666;
            font-size: 0.9rem;
            position: relative;
        }

        .features {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #4a4a4a;
        }

        .feature-item svg {
            width: 20px;
            height: 20px;
            color: #4f46e5;
        }

        .message {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 6px;
            display: block;
        }
        
        .message.success {
            background: #e6ffe6;
            color: #006600;
            border: 1px solid #00cc00;
        }
        
        .message.error {
            background: #ffe6e6;
            color: #cc0000;
            border: 1px solid #ff0000;
        }

        #debug {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f8f8;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.8rem;
            white-space: pre-wrap;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bluesky Mention Tracker</h1>
            <p class="subtitle">Insert a word, phrase or sentence, see when someone says that on Bluesky</p>
            <br>
            <p class="subtitle">CaSe don't matter</p>
            <br>
            <p class="subtitle">No registration, your email is your key to everything.</p>
        </div>

        <?php if (!empty($messageText)): ?>
            <div id="message" class="message <?php echo $messageClass; ?>">
                <?php echo htmlspecialchars($messageText); ?>
            </div>
        <?php endif; ?>

        <div id="debug"></div>
        <form id="signupForm" method="post">
            <div class="form-group">
                <label for="email">Email address</label>
                <input type="email" name="email" id="email" required>
            </div>

            <div class="form-group">
                <label for="phrase">Phrase (not needed if you just want to login)</label>
                <input type="text" name="phrase" id="phrase">
            </div>

            <button type="submit">Start Tracking</button>
        </form>

        <div class="divider">
            <span>Features</span>
        </div>

        <div class="features">
            <div class="feature-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>This was going to be email but it kept going to spam :(</span>
            </div>
            <div class="feature-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>don't be a silly billy</span>
            </div>
            <div class="feature-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>It's free yo, like I cba to set up a payments system for this</span>
            </div>
            <div class="feature-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span><a href="https://ko-fi.com/mark_mcnally_je">thanks for the gold kind stranger</a></span>
            </div>
        </div>
    </div>
</body>
</html>
