<?php
	$servername = "aa1xcexdwrt316h.cj04verszjp4.us-east-1.rds.amazonaws.com";
	$dbname = "ebdb";
	$username = "dnexus";
	$password = "46ZS3MnwcJ7sVefHPm7J";

	// $servername = "localhost";
	// $dbname = "test";
	// $username = "root";
	// $password = "";

	// Create connection
	$conn = new mysqli($servername, $username, $password, $dbname);

	// Check connection
	if ($conn->connect_error) {
	    die("Connection failed: " . $conn->connect_error);
	} 

	$sql = "INSERT INTO log (ip_address) VALUES ('234.0.0.2')";

	if ($conn->query($sql) === TRUE) {
	    echo "New record created successfully";
	} else {
	    echo "Error: " . $sql . "<br>" . $conn->error;
	}

	$conn->close();
?>