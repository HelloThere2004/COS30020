<?php
session_start();
$errors = array();
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = isset($_POST["email"]) ? trim($_POST["email"]) : "";
    $password = isset($_POST["password"]) ? trim($_POST["password"]) : "";

    //Validate form
    if (empty($email)) {
        $errors["email"] = "Email cannot be empty";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors["email"] = "This is not a valid email format";
    }

    if (empty($password)) {
        $errors["password"] = "Password cannot be empty";
    }

    if (empty($errors)) {
        require_once("./functions/settings.php");
        $connection = new mysqli($hostname, $username, $db_password, $database);
        if ($connection->connect_error) {
            die("Connection failed: " . $connection->connect_error);
        }

        $queryString = "SELECT friend_id, profile_name, friend_email, password, num_of_friends FROM friends WHERE friend_email = ?";
        $stmt = $connection->prepare($queryString);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $password_from_db = $user["password"];

            if ($password === $password_from_db) {
                $_SESSION["loggedIn"] = true;
                $_SESSION["profileName"] = $user["profile_name"];
                $_SESSION["friendId"] = $user["friend_id"];
                $_SESSION["numOfFriends"] = $user["num_of_friends"];
                header("Location: friendlist.php");
                exit;
            } else {
                $errors["password"] = "Incorrect password";
            }
        } else {
            $errors["email"] = "Cannot find the user with this email";
        }
        $stmt->close();
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
    require_once("./functions/noSignedInHeader.php");
    ?>
    <div class="login-content">
        <h1>My Friend System</h1>
        <h1>Log-In Page</h1>
        <form action="login.php" method="post">
            <div class="login-form-field">
                <label for="email">Email: </label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email) ?>">
                <?php if (isset($errors['email'])) echo '<span class="error">' . $errors['email'] . '</span>'; ?>
            </div>
            <br>
            <div class="login-form-field">
                <label for="password">Password: </label>
                <input type="password" name="password" id="email">
                <?php if (isset($errors['password'])) echo '<span class="error">' . $errors['password'] . '</span>'; ?>
            </div>
            <div class="login-button">
                <button type="submit">Log in</button> <button type="reset">Clear</button>
            </div>

        </form>

    </div>
</body>

</html>