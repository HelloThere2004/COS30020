<?php
// update_counts.php
// Script này sẽ lặp qua tất cả người dùng và cập nhật lại cột num_of_friends của họ.

// Bao gồm file cấu hình kết nối CSDL của bạn
require_once("./functions/settings.php");

// 1. Kết nối đến cơ sở dữ liệu
$connection = new mysqli($hostname, $username, $db_password, $database);

// Kiểm tra lỗi kết nối
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

echo "<h1>Starting friend count update...</h1>";

// 2. Lấy ID của tất cả người dùng từ bảng 'friends'
$sql_get_users = "SELECT friend_id, profile_name FROM friends";
$users_result = $connection->query($sql_get_users);

if ($users_result && $users_result->num_rows > 0) {
    // 3. Lặp qua từng người dùng
    while ($user = $users_result->fetch_assoc()) {
        $userId = $user['friend_id'];
        $profileName = $user['profile_name'];

        // 4. Đếm số bạn bè của người dùng hiện tại từ bảng 'myfriends'
        // Chúng ta cần đếm số lần ID của họ xuất hiện ở cả hai cột
        $sql_count = "SELECT COUNT(*) as friend_count FROM myfriends WHERE friend_id1 = ? OR friend_id2 = ?";
        
        $stmt_count = $connection->prepare($sql_count);
        $stmt_count->bind_param("ii", $userId, $userId);
        $stmt_count->execute();
        $count_result = $stmt_count->get_result()->fetch_assoc();
        $friendCount = $count_result['friend_count'];
        $stmt_count->close();

        // 5. Cập nhật lại cột 'num_of_friends' trong bảng 'friends'
        $sql_update = "UPDATE friends SET num_of_friends = ? WHERE friend_id = ?";
        
        $stmt_update = $connection->prepare($sql_update);
        $stmt_update->bind_param("ii", $friendCount, $userId);
        
        if ($stmt_update->execute()) {
            echo "<p>Successfully updated user '" . htmlspecialchars($profileName) . "' (ID: $userId) with $friendCount friends.</p>";
        } else {
            echo "<p style='color: red;'>Failed to update user '" . htmlspecialchars($profileName) . "' (ID: $userId).</p>";
        }
        $stmt_update->close();
    }
} else {
    echo "<p>No users found in the 'friends' table.</p>";
}

// 6. Đóng kết nối
$connection->close();
echo "<h2>Update process complete!</h2>";

?>
