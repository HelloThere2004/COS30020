<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - My Friend System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php
    if (isset($_SESSION["loggedIn"])) {
        require_once("./functions/signedInHeader.php");
    } else {
        require_once("./functions/noSignedInHeader.php");
    }
    ?>
    <div class="about-content">
        <h1>About This Assignment</h1>
        <h2>Assignment 2 - My Friend System</h2>
        <ul>
            <li><strong>Tasks not attempted or not completed:</strong>
                <ul>
                    <li>All of tasks are completed.</li>
                </ul>
            </li>
            <li><strong>Special features done or attempted:</strong>
                <ul>
                    <li>Implemented pagination for Add Friend and Friend List pages.</li>
                    <li>Mutual friend count displayed on Add Friend page.</li>
                    <li>Add friend and Unfriend features are implemented in Friend List and Add Friend pages.</li>
                    <li>Server-side validation for sign up and login forms.</li>
                    <li>Session management for user authentication.</li>
                </ul>
            </li>
            <li><strong>Parts I had trouble with:</strong>
                <ul>
                    <li>Ensuring correct pagination logic.</li>
                    <li>Ensuring correct SQL commands for unfriend and display mutual friends features.</li>
                </ul>
            </li>
            <li><strong>What I would like to do better next time:</strong>
                <ul>
                    <li>Improve UI/UX with more responsive design.</li>
                    <li>Refactor code for better reusability and maintainability.</li>
                </ul>
            </li>
            <li><strong>Additional features added:</strong>
                <ul>
                    <li>Clear error and success messages for user actions.</li>
                    <li>Consistent navigation and session-based headers.</li>
                    <li>Improve the schema by adding foreign key in <strong>myfriends</strong> table for ensuring data integrity.</li>
                </ul>
            </li>
        </ul>
        
    </div>
</body>
</html>
