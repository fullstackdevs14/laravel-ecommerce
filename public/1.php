<?php
	
	$servername = env('DB_HOST', '127.0.0.1');
	$dbname = env('DB_DATABASE', 'forge');
	$username = env('DB_USERNAME', 'forge');
	$password = env('DB_PASSWORD', '');

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

	$sql = "INSERT INTO log (ip_address) VALUES ('127.0.0.2')";

	if ($conn->query($sql) === TRUE) {
	    echo "New record created successfully";
	} else {
	    echo "Error: " . $sql . "<br>" . $conn->error;
	}

	$conn->close();
?>