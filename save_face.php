<?php
include 'config.php';
include 'api_key.php';
include 'db_config.php';

// Check connection
if ($conn->connect_error) {
  // die("Connection failed: " . $conn->connect_error);
  $import_ok = false;
	die(json_encode([
		"status" => "error",
		"message" => "An error occured when connecting to the DB and has been logged"
	]));
}

// if post request has b_id and face_data
if (isset($_POST["b_id"]) and isset($_POST["face_data"])) {
    $b_id = $_POST["b_id"];
    $face_data = $_POST["face_data"];

    // see if the b_id exists
    $sql = "SELECT * FROM `face_only` WHERE `b_id` = '$b_id'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // b_id exists, update the face_data
        $sql = "UPDATE `face_only` SET `face_data` = '$face_data' WHERE `b_id` = '$b_id'";
        $result = $conn->query($sql);

        if ($result) {
            die(json_encode([
                "status" => "success",
                "message" => "Face data has been updated"
            ]));
        } else {
            die(json_encode([
                "status" => "error",
                "message" => "An error occured when updating the face data"
            ]));
        }
    } else {
        // b_id does not exist, insert the face_data
        $sql = "INSERT INTO `face_only` (`b_id`, `face_data`) VALUES ('$b_id', '$face_data')";
        $result = $conn->query($sql);

        if ($result) {
            die(json_encode([
                "status" => "success",
                "message" => "Face data has been inserted"
            ]));
        } else {
            die(json_encode([
                "status" => "error",
                "message" => "An error occured when inserting the face data"
            ]));
        }
    }
} else {
    die(json_encode([
        "status" => "error",
        "message" => "No b_id or face_data was provided"
    ]));
}

?>
