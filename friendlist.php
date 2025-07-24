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

    // Pagination setup
    $friendsPerPage = 10;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $friendsPerPage;

    // Count total friends for pagination
    $countQuery = "
        SELECT COUNT(*) as total FROM (
            (SELECT friend_id2 as fid FROM myfriends WHERE friend_id1 = ?)
            UNION ALL
            (SELECT friend_id1 as fid FROM myfriends WHERE friend_id2 = ?)
        ) as all_friends
    ";
    $stmtCount = $connection->prepare($countQuery);
    $stmtCount->bind_param("ii", $friendId, $friendId);
    $stmtCount->execute();
    $resultCount = $stmtCount->get_result();
    $totalFriends = $resultCount->fetch_assoc()['total'];
    $totalPages = ceil($totalFriends / $friendsPerPage);
    $stmtCount->close();

    //Add friend querry (Only run if the button in the list of friends needed to added)
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['unfriend_id'])) {
        $unfriend_id = $_POST['unfriend_id'];
        //Insert a new record to myfriends table 
        $deleteMyFriendQuerry = "
            DELETE FROM myfriends WHERE
            friend_id1 = ? AND friend_id2 = ?
        ";
        $stmtDeleteMyFriend = $connection->prepare("$deleteMyFriendQuerry");
        $stmtDeleteMyFriend->bind_param("ii", $friendId, $unfriend_id);
        $stmtDeleteMyFriend->execute();
        if ($stmtDeleteMyFriend->affected_rows == 0) {
            $stmtDeleteMyFriendReverse = $connection->prepare("$deleteMyFriendQuerry");
            $stmtDeleteMyFriendReverse->bind_param("ii", $unfriend_id, $friendId);
            $stmtDeleteMyFriendReverse->execute();
        }


        $stmtDeleteMyFriend->close();

        //Update num_of_friends of current user
        $updateNumOfFriendForCurrentUserQuerry = "
            UPDATE friends
            SET num_of_friends = num_of_friends - 1
            WHERE friend_id = ?
        ";
        $stmtUpdateMyFriendTableCurrentUser = $connection->prepare("$updateNumOfFriendForCurrentUserQuerry");
        $stmtUpdateMyFriendTableCurrentUser->bind_param("i", $friendId);
        $stmtUpdateMyFriendTableCurrentUser->execute();
        $stmtUpdateMyFriendTableCurrentUser->close();


        //Update num_of_friends of added friend
        $updateNumOfFriendForAddedFriendQuerry = "
            UPDATE friends
            SET num_of_friends = num_of_friends - 1
            WHERE friend_id = ?
        ";
        $stmtUpdateMyFriendTableDeletedFriend = $connection->prepare("$updateNumOfFriendForAddedFriendQuerry");
        $stmtUpdateMyFriendTableDeletedFriend->bind_param("i", $unfriend_id);
        $stmtUpdateMyFriendTableDeletedFriend->execute();
        $stmtUpdateMyFriendTableDeletedFriend->close();
        $_SESSION["numOfFriends"] -= 1;
        header("Location: friendlist.php");
    }
    $myFriendQuerryString = "
        (SELECT profile_name, friend_id FROM friends JOIN myfriends ON friend_id = friend_id2 WHERE friend_id1 = ?)
        UNION
        (SELECT profile_name, friend_id FROM friends JOIN myfriends ON friend_id = friend_id1 WHERE friend_id2 = ?)
        LIMIT ? OFFSET ?
        ";
    //When the id of user is in column friend_id1 in myfriends table, take the information of column friend_id2 and vice versa.
    $stmt = $connection->prepare($myFriendQuerryString);
    $stmt->bind_param("iiii", $friendId, $friendId, $friendsPerPage, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $listFriends = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $listFriends[] = $row;
        }
    } else {
        $message = "No friend found. Let's add a friend";
    }
    $stmt->close();

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
            foreach ($listFriends as $friend) {
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
                                        <form action='friendlist.php' method='post'>
                                            <input type='hidden' name='unfriend_id' value=" . $friend["friend_id"] . ">
                                            <button type='submit'>Unriend</button>
                                        </form>                                       
                                    </div>
                                </div>
                            </div>
                        ";
            }
            $stmtMutual->close();
            $connection->close();
        }
        ?>
        <a href="friendadd.php" class="addfriend-listfriend-changepage">Let's add more friend</a>
        <?php
        // Pagination links
        if (isset(
            $totalPages) && $totalPages > 1) {
            echo '<div class="pagination">';
            for ($i = 1; $i <= $totalPages; $i++) {
                if ($i == $page) {
                    echo "<strong>$i</strong> ";
                } else {
                    echo "<a href='friendlist.php?page=$i'>$i</a> ";
                }
            }
            echo '</div>';
        }
        ?>
    </div>
</body>

</html>