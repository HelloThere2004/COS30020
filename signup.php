<?php

$errors = array();
$successMessage = "";
$email = "";
$profileName = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = isset($_POST["email"]) ? trim($_POST["email"]) : "";
    $profileName = isset($_POST["profileName"]) ? trim($_POST["profileName"]) : "";
    $password = isset($_POST["password"]) ? $_POST["password"] : "";
    $confirmPassword = isset($_POST["confirmPassword"]) ? $_POST["confirmPassword"] : "";

    //Validate form
    if (empty($email)) {
        $errors["email"] = "Email cannot be empty";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors["email"] = "This is not a valid email format";
    } else if (strlen($email) > 50) {
        $errors["email"] = "Email cannot be more than 50 characters";
    }

    if (empty($profileName)) {
        $errors["profileName"] = "Profile name cannot be empty";
    } else if (strlen($profileName) > 30) { // Sửa lỗi: kiểm tra độ dài của profileName
        $errors["profileName"] = "Profile name cannot be more than 30 characters";
    }

    if (empty($password)) {
        $errors["password"] = "Password cannot be empty";
    } else if (strlen($password) > 20) {
        $errors["password"] = "Password cannot be more than 20 characters";
    }

    if (empty($confirmPassword)) {
        $errors["confirmPassword"] = "Confirm password cannot be empty";
    } else if ($password !== $confirmPassword) {
        $errors["confirmPassword"] = "Password does not match";
    }

    if (empty($errors)) {
        require_once("./functions/settings.php");
        $connection = new mysqli($hostname, $username, $db_password, $database);

        if ($connection->connect_error) {
            die("Connection failed: " . $connection->connect_error);
        }

        $checkEmailSql = "SELECT friend_id FROM friends WHERE friend_email = ?";
        $checkMailStmt = $connection->prepare($checkEmailSql);
        $checkMailStmt->bind_param("s", $email);
        $checkMailStmt->execute();
        $result = $checkMailStmt->get_result();

        if ($result->num_rows > 0) {
            $errors['email'] = "This email address is already registered.";
        } else {
            $currentDate = date("Y-m-d");
            $num_of_friends = 0;

            $insertSql = "INSERT INTO friends (friend_email, password, profile_name, date_started, num_of_friends) VALUES (?,?,?,?,?)";
            $insertStmt = $connection->prepare($insertSql);


            $insertStmt->bind_param("ssssi", $email, $password, $profileName, $currentDate, $num_of_friends);

            if ($insertStmt->execute()) {
                $successMessage = "<p>The account was successfully created.</p>";
                $email = "";
                $profileName = "";
            } else {
                $errors['db'] = "An error occurred. Please try again later.";
            }
            $insertStmt->close();
        }
        $checkMailStmt->close();
        $connection->close();
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php
    session_start();
    if (isset($_SESSION["loggedIn"])) {
        require_once("./functions/signedInHeader.php");
    } else {
        require_once("./functions/noSignedInHeader.php");
    }
    ?>
    <div class="signup-content">
        <h1>Sign-Up Form</h1>
        <form action="signup.php" method="post">
            <div>
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>">
                <?php if (isset($errors['email'])) echo '<span class="error">' . $errors['email'] . '</span>'; ?>
            </div>
            <br>
            <div>
                <label for="profileName">Profile Name:</label>
                <input type="text" name="profileName" id="profileName" value="<?php echo htmlspecialchars($profileName); ?>">
                <?php if (isset($errors['profileName'])) echo '<span class="error">' . $errors['profileName'] . '</span>'; ?>
            </div>
            <br>
            <div>
                <label for="password">Password:</label>
                <input type="password" name="password" id="password">
                <?php if (isset($errors['password'])) echo '<span class="error">' . $errors['password'] . '</span>'; ?>
            </div>
            <br>
            <div>
                <label for="confirmPassword">Confirm Password:</label>
                <input type="password" name="confirmPassword" id="confirmPassword">
                <?php if (isset($errors['confirmPassword'])) echo '<span class="error">' . $errors['confirmPassword'] . '</span>'; ?>
            </div>
            <br>
            <?php if (isset($successMessage)) echo '<div class="success-message">' . $successMessage . '</div>'; ?>
            <?php if (isset($errors['db'])) echo '<span class="error">' . $errors['db'] . '</span>' ?>
            <button type="submit">Sign Up</button>
        </form>
    </div>

</body>

</html>