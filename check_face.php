<?php
include 'config.php';
include 'api_key.php';

$facelocalUploadDir = "face/";
$fileName = "input.jpg";
$targetFile = $facelocalUploadDir . $fileName;
$uploadOk = 1;
$imageFileType = strtolower(pathinfo($targetFile,PATHINFO_EXTENSION));

include 'pre_checks.php';
include 'db_config.php';

if (isset($_GET["bidno"]) and !empty($_GET["bidno"])) {
  $bidno = $_GET["bidno"];

  // Check connection
  if ($conn->connect_error) {
    // die("Connection failed: " . $conn->connect_error);
    $import_ok = false;
  	die(json_encode([
  		"status" => "error",
  		"message" => "An error occured when connecting to the DB and has been logged"
  	]));
  }

  // see if the $uploadOk variable has been set to 0 by an error
  if ($uploadOk == 0) {
  	die(json_encode([
  		"status" => "error",
  		"message" => "No image was uploaded"
  	]));
  // no error was encountered, try to upload file
  } else {
  	if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $targetFile)) {
      $bidnoData = getBidnoData($bidno);
      // var_dump($bidnoData);

      $check_result = json_decode(exec("python3 face/check_face.py '".$bidnoData["face_geometry"]."'"), true);
      // var_dump($check_result);
      if ($check_result["data"]["matched"] == true) {
        unset($bidnoData["face_geometry"]);
        echo json_encode([
          "status" => "success",
          "message" => "Face check complete",
          "data" => [
            "checkResult" => true,
            "idData" => $bidnoData
          ]
        ]);
      }
      else {
        echo json_encode([
          "status" => "success",
          "message" => "Face check complete",
          "data" => [
            "checkResult" => false
          ]
        ]);
      }
    }
  }
}
else {
  die(json_encode([
    "status" => "error",
    "message" => "No bidno was provided to check the face against"
  ]));
}

function getBidnoData($bidno) {
	global $conn;
	$existingBidno = null;
	try {
		$sql = "SELECT * from face_api where bidno='$bidno'";
		$result = $conn->query($sql);

		if ($result and $result->num_rows > 0) {
		    // output data of each row
		    while($row = $result->fetch_assoc()) {
		      if ($row["bidno"]) {
						// this id already exists
						$existingBidno = $row["bidno"];
            $namef = $row["namef"];
            $namel = $row["namel"];
            $face_geometry = $row["face_geometry"];
					}
		    }
		} else {
		    // echo "0 results";
		}
	}
	catch (Exception $ex) {
		echo "Error: No faces in input image <br>";
		return null;
	}
	if ($existingBidno) {
    $ext_ids = [];
    try {
  		$sql = "SELECT * from ext_ids where bidno='$bidno'";
  		$result = $conn->query($sql);

  		if ($result and $result->num_rows > 0) {
  		    // output data of each row
  		    while($row = $result->fetch_assoc()) {
  		      if ($row["bidno"]) {
  						array_push($ext_ids, [
                "extIdNo" => $row["extIdNo"],
                "namef" => $row["namef"],
                "namel" => $row["namel"],
                "jd" => $row["jd"],
              ]);

  					}
  		    }
  		} else {
  		    // echo "0 results";
  		}
  	}
  	catch (Exception $ex) {
  		echo "Error: No faces in input image <br>";
  		return null;
  	}

		return [
      "bidno" => $existingBidno,
      "namef" => $namef,
      "namel" => $namel,
      "face_geometry" => $face_geometry,
      "extIds" => $ext_ids
    ];
	}
	else {
		return null;
	}
}
?>
