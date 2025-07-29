<?php
session_start();
$message = "";

if (
    isset($_SESSION["loggedIn"]) && isset($_SESSION["profileName"]) && isset($_SESSION["friendId"]) &&
    !empty($_SESSION["loggedIn"]) && !empty($_SESSION["profileName"]) && !empty($_SESSION["friendId"]) // Fixed typo
) {
    $profileName = $_SESSION["profileName"];
    $friendId = $_SESSION["friendId"];
    $numOfFriends = $_SESSION["numOfFriends"];
    require_once("./functions/settings.php");
    $connection = new mysqli($hostname, $username, $db_password, $database);

    // Add friend logic (remains unchanged)
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_friend_id'])) {
        $add_friend_id = $_POST['add_friend_id'];
        
        // Insert a new record to myfriends table 
        $addNewFriendMyFriendQuerry = "INSERT INTO myfriends (friend_id1, friend_id2) VALUES (?,?)";
        $stmtInsertMyFriendTable = $connection->prepare($addNewFriendMyFriendQuerry);
        $stmtInsertMyFriendTable->bind_param("ii", $friendId, $add_friend_id);
        $stmtInsertMyFriendTable->execute();
        $stmtInsertMyFriendTable->close();

        // Update num_of_friends for both users
        $updateNumOfFriendQuerry = "UPDATE friends SET num_of_friends = num_of_friends + 1 WHERE friend_id IN (?, ?)";
        $stmtUpdateNum = $connection->prepare($updateNumOfFriendQuerry);
        $stmtUpdateNum->bind_param("ii", $friendId, $add_friend_id);
        $stmtUpdateNum->execute();
        $stmtUpdateNum->close();
        
        $_SESSION["numOfFriends"] += 1;
        $page = isset($_GET['page']) ? $_GET['page'] : 1;
        header("Location: friendadd.php?page=$page");
        exit;
    }


    // Get the list of people who are NOT friends yet

    // First, get the current user's friends
    $myFriendQuerryString = "
        SELECT friend_id FROM friends JOIN myfriends ON friend_id = friend_id2 WHERE friend_id1 = ?
        UNION
        SELECT friend_id FROM friends JOIN myfriends ON friend_id = friend_id1 WHERE friend_id2 = ?
    ";
    $stmt = $connection->prepare($myFriendQuerryString);
    $stmt->bind_param("ii", $friendId, $friendId);
    $stmt->execute();
    $result = $stmt->get_result();
    $listFriends = [];
    while ($row = $result->fetch_assoc()) {
        $listFriends[] = $row;
    }
    $stmt->close();

    // Second, get ALL users except the current one
    $allUsersQuery = "SELECT friend_id, profile_name FROM friends WHERE friend_id != ?";
    $stmtAllUser = $connection->prepare($allUsersQuery);
    $stmtAllUser->bind_param("i", $friendId);
    $stmtAllUser->execute();
    $allFriend = [];
    $allFriendQuerryResult = $stmtAllUser->get_result();
    while ($row = $allFriendQuerryResult->fetch_assoc()) {
        $allFriend[] = $row;
    }
    $stmtAllUser->close();

    // Third, find the difference to get the full list of users to add
    $fullFriendListToAdd = array_udiff($allFriend, $listFriends, function ($user1, $user2) {
        return $user1['friend_id'] - $user2['friend_id'];
    });

    // Calculate pagination variables using PHP
    $usersPerPage = 10;
    $totalUsersToAdd = count($fullFriendListToAdd);
    $totalPages = ceil($totalUsersToAdd / $usersPerPage);
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

    // Ensure page number is valid
    if ($page > $totalPages && $totalPages > 0) {
        $page = $totalPages;
    }
     if ($page < 1) {
        $page = 1;
    }

    // Slice the array to get users for the current page
    $offset = ($page - 1) * $usersPerPage;
    $friendToAdd = array_slice($fullFriendListToAdd, $offset, $usersPerPage);


    // Mutual friend query
    $mutualFriendQuerry = "
        SELECT COUNT(table1.id) AS mutual_count FROM
        (SELECT friend_id2 AS id FROM myfriends WHERE friend_id1 = ? UNION SELECT friend_id1 AS id FROM myfriends WHERE friend_id2 = ?) as table1
        INNER JOIN 
        (SELECT friend_id2 AS id FROM myfriends WHERE friend_id1 = ? UNION SELECT friend_id1 AS id FROM myfriends WHERE friend_id2 = ?) as table2
        ON table1.id = table2.id
    ";
    $stmtMutual = $connection->prepare($mutualFriendQuerry);

} else {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Friends</title>
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
        <h2><?php echo htmlspecialchars($profileName); ?>'s Add Friend Page</h2>
        <h2>Total number of users you can add is <?php echo count($fullFriendListToAdd); ?></h2>
        
        <?php if (empty($friendToAdd)) : ?>
            <p>No new users to add.</p>
        <?php else : ?>
             <?php foreach ($friendToAdd as $friend) : ?>
                <?php
                // Get mutual friend count
                $stmtMutual->bind_param('iiii', $friendId, $friendId, $friend['friend_id'], $friend['friend_id']);
                $stmtMutual->execute();
                $mutualResult = $stmtMutual->get_result();
                $mutualRow = $mutualResult->fetch_assoc();
                $mutualFriendCount = $mutualRow['mutual_count'];
                ?>
                <div class='container-friend-boxes'>
                    <div class='friend-box'>
                        <div>
                            <h3><?php echo htmlspecialchars($friend['profile_name']); ?></h3>
                            <p>Number of mutual friends: <?php echo $mutualFriendCount; ?></p>
                        </div>
                        <div>
                            <form action='friendadd.php?page=<?php echo $page; ?>' method='post'>
                                <input type='hidden' name='add_friend_id' value="<?php echo $friend["friend_id"]; ?>">
                                <button type='submit'>Add Friend</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <a href="friendlist.php" class="addfriend-listfriend-changepage">Let's check for list of friends</a>

        <?php
        // Pagination links
        if (isset($totalPages) && $totalPages > 1) {
            echo '<div class="pagination">';
            for ($i = 1; $i <= $totalPages; $i++) {
                if ($i == $page) {
                    echo "<strong>$i</strong> "; //Indicate the current page
                } else {
                    echo "<a href='friendadd.php?page=$i'>$i</a> "; //Move to other pages
                }
            }
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
<?php
// Close statements and connection
if (isset($stmtMutual)) $stmtMutual->close();
$connection->close();
?>