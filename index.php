<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Page</title>
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
    <div class="index-content">
        <h1>My Friend System</h1>
        <h1>Assignment Home Page</h1>
        <br>
        <table>
            <tr>
                <td>
                    <p>Name: Binh Ca Nguyen</p>
                </td>
                <td>
                    <p>Student ID: 104225904</p>
                </td>
            </tr>
        </table>
        <br>
        <p>Email: 104225904@student.swin.edu.au</p>
        <p>I declare that this assignment is my individual work. I have not worked collaboratively
            nor have I copied from any other student’s work or from any other source.</p>
        <br>
        <p>Tables successfully created and populated.</p>
    </div>
    <?php
    require_once("./functions/settings.php");
    try {
        $connection = new mysqli($hostname, $username, $db_password, $database);
        $createTableFriend = "CREATE TABLE IF NOT EXISTS friends (
            friend_id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
            friend_email VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(20) NOT NULL,
            profile_name VARCHAR(30) NOT NULL,
            date_started DATE NOT NULL,
            num_of_friends INT UNSIGNED 
        )";

        $createTableMyFriend = "CREATE TABLE IF NOT EXISTS myfriends (
            friend_id1 INT NOT NULL,
            friend_id2 INT NOT NULL,
            FOREIGN KEY (friend_id1) REFERENCES friends(friend_id) ON DELETE CASCADE,
            FOREIGN KEY (friend_id2) REFERENCES friends(friend_id) ON DELETE CASCADE
        )";
        $resultTableFriend = $connection->query($createTableFriend);
        $resultTableMyFriend = $connection->query($createTableMyFriend);
        if (!$resultTableFriend || !$resultTableMyFriend) {
            echo "<div class='db-error'>Cannot create table: " . htmlspecialchars($connection->error) . "</div>";
        }
        $connection->close();
    } catch (mysqli_sql_exception $e) {
        echo "<div class='db-error'>Cannot connect to the database: " . htmlspecialchars($e->getMessage()) . "</div>";
        exit; // Stop further execution
    }
    ?>
</body>

</html>