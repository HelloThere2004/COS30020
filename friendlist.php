<?php
session_start();
$message = "";

if (
    isset($_SESSION["loggedIn"]) && isset($_SESSION["profileName"]) && isset($_SESSION["friendId"]) &&
    !empty($_SESSION["loggedIn"]) && !empty($_SESSION["profileName"]) && !empty($_SESSION["friendId"]) // Fixed a small typo in your original session check
) {
    $profileName = $_SESSION["profileName"];
    $friendId = $_SESSION["friendId"];
    $numOfFriends = $_SESSION["numOfFriends"]; // Used to display the total count
    require_once("./functions/settings.php");
    $connection = new mysqli($hostname, $username, $db_password, $database);

    // Unfriend logic (remains unchanged)
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['unfriend_id'])) {
        $unfriend_id = $_POST['unfriend_id'];
        
        // A more efficient delete query
        $deleteMyFriendQuerry = "DELETE FROM myfriends WHERE (friend_id1 = ? AND friend_id2 = ?) OR (friend_id1 = ? AND friend_id2 = ?)";
        $stmtDeleteMyFriend = $connection->prepare($deleteMyFriendQuerry);
        $stmtDeleteMyFriend->bind_param("iiii", $friendId, $unfriend_id, $unfriend_id, $friendId);
        $stmtDeleteMyFriend->execute();
        $stmtDeleteMyFriend->close();

        // Update num_of_friends for both users in one query
        $updateNumOfFriendQuerry = "UPDATE friends SET num_of_friends = num_of_friends - 1 WHERE friend_id IN (?, ?)";
        $stmtUpdateNumOfFriend = $connection->prepare($updateNumOfFriendQuerry);
        $stmtUpdateNumOfFriend->bind_param("ii", $friendId, $unfriend_id);
        $stmtUpdateNumOfFriend->execute();
        $stmtUpdateNumOfFriend->close();
        
        $_SESSION["numOfFriends"] -= 1;
        $page = isset($_GET['page']) ? $_GET['page'] : 1;
        header("Location: friendlist.php?page=$page");
        exit;
    }

    // --- NEW SIMPLIFIED PAGINATION LOGIC ---

    // 1. Get ALL friends from the database (without LIMIT/OFFSET)
    $myFriendQuerryString = "
        (SELECT profile_name, friend_id FROM friends JOIN myfriends ON friend_id = friend_id2 WHERE friend_id1 = ?)
        UNION
        (SELECT profile_name, friend_id FROM friends JOIN myfriends ON friend_id = friend_id1 WHERE friend_id2 = ?)
    ";
    $stmt = $connection->prepare($myFriendQuerryString);
    $stmt->bind_param("ii", $friendId, $friendId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $allFriends = []; // An array to hold every friend
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $allFriends[] = $row;
        }
    } else {
        $message = "No friend found. Let's add a friend";
    }
    $stmt->close();

    // 2. Calculate pagination variables using PHP
    $friendsPerPage = 10;
    $totalFriends = count($allFriends); // Count friends from the PHP array
    $totalPages = ceil($totalFriends / $friendsPerPage);
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

    // Ensure page number is valid
    if ($page > $totalPages && $totalPages > 0) {
        $page = $totalPages;
    }
    if ($page < 1) {
        $page = 1;
    }

    // 3. Slice the array to get only the friends for the current page
    $offset = ($page - 1) * $friendsPerPage;
    $listFriends = array_slice($allFriends, $offset, $friendsPerPage);

    
    // Mutual friend query (remains unchanged)
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
    <title>Friend List</title>
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

        <h2><?php echo htmlspecialchars($profileName); ?>'s Friend List Page</h2>
        <h2>Total number of friends is <?php echo $numOfFriends; ?></h2>
        
        <?php if (empty($listFriends)) : ?>
            <p><?php echo $message; ?></p>
        <?php else : ?>
            <?php foreach ($listFriends as $friend) : ?>
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
                            <form action='friendlist.php?page=<?php echo $page; ?>' method='post'>
                                <input type='hidden' name='unfriend_id' value="<?php echo $friend["friend_id"]; ?>">
                                <button type='submit'>Unfriend</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <a href="friendadd.php" class="addfriend-listfriend-changepage">Let's add more friends</a>
        
        <?php
        // Pagination links (remains unchanged)
        if (isset($totalPages) && $totalPages > 1) {
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
<?php
// Close statements and connection
if (isset($stmtMutual)) $stmtMutual->close();
$connection->close();
?>