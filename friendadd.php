<?php
session_start();
$message = "";

if (
    isset($_SESSION["loggedIn"]) && isset($_SESSION["profileName"]) && isset($_SESSION["friendId"]) &&
    !empty($_SESSION["loggedIn"]) && !empty($_SESSION["profileName"]) && !empty($_SESSION["profileName"])
) {
    $profileName = $_SESSION["profileName"];
    $friendId = $_SESSION["friendId"];
    $numOfFriends = $_SESSION["numOfFriends"];
    require_once("./functions/settings.php");
    $connection = new mysqli($hostname, $username, $db_password, $database);


    //Add friend querry (Only run if the button in the list of friends needed to added)
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_friend_id'])) {
        $add_friend_id = $_POST['add_friend_id'];
        //Insert a new record to myfriends table 
        $addNewFriendMyFriendQuerry = "
            INSERT INTO myfriends (friend_id1, friend_id2) 
            VALUE (?,?)
        ";
        $stmtInsertMyFriendTable = $connection->prepare("$addNewFriendMyFriendQuerry");
        $stmtInsertMyFriendTable->bind_param("ii", $friendId, $add_friend_id);
        $stmtInsertMyFriendTable->execute();
        $stmtInsertMyFriendTable->close();

        //Update num_of_friends of current user
        $updateNumOfFriendForCurrentUserQuerry = "
            UPDATE friends
            SET num_of_friends = num_of_friends + 1
            WHERE friend_id = ?
        ";
        $stmtUpdateMyFriendTableCurrentUser = $connection->prepare("$updateNumOfFriendForCurrentUserQuerry");
        $stmtUpdateMyFriendTableCurrentUser->bind_param("i", $friendId);
        $stmtUpdateMyFriendTableCurrentUser->execute();
        $stmtUpdateMyFriendTableCurrentUser->close();


        //Update num_of_friends of added friend
        $updateNumOfFriendForAddedFriendQuerry = "
            UPDATE friends
            SET num_of_friends = num_of_friends + 1
            WHERE friend_id = ?
        ";
        $stmtUpdateMyFriendTableAddedFriend = $connection->prepare("$updateNumOfFriendForAddedFriendQuerry");
        $stmtUpdateMyFriendTableAddedFriend->bind_param("i", $add_friend_id);
        $stmtUpdateMyFriendTableAddedFriend->execute();
        $stmtUpdateMyFriendTableAddedFriend->close();
        $_SESSION["numOfFriends"] += 1;
        header("Location: friendadd.php");
    }
    $myFriendQuerryString = "
            SELECT profile_name, friend_id FROM friends JOIN myfriends ON friend_id = friend_id2 WHERE friend_id1 = ?
            UNION
            SELECT profile_name, friend_id FROM friends JOIN myfriends ON friend_id = friend_id1 WHERE friend_id2 = ?
            ";
    //When the id of user is in column friend_id1 in myfriends table, take the information of column friend_id2 and vice versa.
    $stmt = $connection->prepare("$myFriendQuerryString");
    $stmt->bind_param("ii", $friendId, $friendId);
    $stmt->execute();
    $result = $stmt->get_result();
    $listFriends = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $listFriends[] = $row;
        }
    }
    $allUsersQuery = "SELECT friend_id, profile_name FROM friends WHERE friend_id != ?";
    $stmtAllUser = $connection->prepare($allUsersQuery);
    $stmtAllUser->bind_param("i", $friendId);
    $stmtAllUser->execute();
    $allFriend = array();
    $allFriendQuerryResult = $stmtAllUser->get_result();
    while ($row = $allFriendQuerryResult->fetch_assoc()) {
        $allFriend[] = $row;
    }
    $friendToAdd = array_udiff($allFriend, $listFriends, function ($user1, $user2) {
        return $user1['friend_id'] - $user2['friend_id'];
    });
    $mutualFriendQuerry = "
    SELECT COUNT(table1.id) AS mutual_count FROM
    (SELECT friend_id2 AS id FROM myfriends WHERE friend_id1 = ? UNION SELECT friend_id1 AS id FROM myfriends WHERE friend_id2 = ?) as table1
    INNER JOIN 
    (SELECT friend_id2 AS id FROM myfriends WHERE friend_id1 = ? UNION SELECT friend_id1 AS id FROM myfriends WHERE friend_id2 = ?) as table2
    ON table1.id = table2.id
    ";
    //SELECT the number of rows in table1 or table2 (both are equal after INNER JOIN). Find the list friend of user1 and user2 and then use INNER JOIN with condition id in table1 and table2 are equal 
    $stmtMutual = $connection->prepare("$mutualFriendQuerry");
} else {
    header("Location: login.php");
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
    if (isset($_SESSION["loggedIn"])) {
        require_once("./functions/signedInHeader.php");
    } else {
        require_once("./functions/noSignedInHeader.php");
    }
    ?>
    <div class="friendlist-content">
        <h1>My Friend System</h1>

        <?php
        echo "<h2>$profileName's Friend List Page</h2>";
        echo "<h2>Total number of friend is $numOfFriends</h2>";
        if (empty($listFriends)) {
            echo "<a href='friendadd.php'>$message</a>";
        } else {
            foreach ($friendToAdd as $friend) {
                $stmtMutual->bind_param('iiii', $friendId, $friendId, $friend['friend_id'], $friend['friend_id']);
                $stmtMutual->execute();
                $result = $stmtMutual->get_result();
                $row = $result->fetch_assoc();
                $mutualFriendCount = $row['mutual_count'];
                echo "
                            <div class='container-friend-boxes'>
                                <div class='friend-box'>
                                    <div>
                                        <h3>" . htmlspecialchars($friend['profile_name']) . "</h3>
                                        <p>Number of mutual friends: $mutualFriendCount</p>
                                    </div>
                                    <div>
                                        <form action='friendadd.php' method='post'>
                                            <input type='hidden' name='add_friend_id' value=" . $friend["friend_id"] . ">
                                            <button type='submit'>Add Friend</button>
                                        </form>                                       
                                    </div>
                                </div>
                            </div>
                        ";
            }
        }
        $stmtMutual->close();
        $connection->close();
        ?>
        <a href="friendlist.php" class="addfriend-listfriend-changepage">Let's check for list of friends</a>
    </div>
</body>