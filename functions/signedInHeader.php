<?php
    $profileName = $_SESSION["profileName"];
    echo 
    "<header id='signed-in'>
        <nav >
            <a href='index.php'>Homepage</a>
            <a href='friendList.php'>List of Friends</a>
            <a href='friendadd.php'>Add more Friends</a>
            <a href='about.php'>About</a>
            <a href='signup.php'>Sign Up</a>        
            <a href='logout.php'>Log-out</a>
        </nav>
        <div class='username-header'>
            <h3>Hello, $profileName</h3>
        </div>
    </header>";
?>