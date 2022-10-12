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

if (isset($_POST["face_data"]) and !empty($_POST["face_data"])) {
  $face_data = $_POST["face_data"];

  // see if the $uploadOk variable has been set to 0 by an error
  if ($uploadOk == 0) {
  	die(json_encode([
  		"status" => "error",
  		"message" => "No image was uploaded"
  	]));
  // no error was encountered, try to upload file
  } else {
  	if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $targetFile)) {
      // var_dump($bidnoData);

      $check_result = json_decode(exec("python3 face/check_face.py '".$face_data."'"), true);
      // var_dump($check_result);
      if ($check_result["data"]["matched"] == true) {
        echo json_encode([
          "status" => "success",
          "message" => "Face check complete",
          "data" => [
            "checkResult" => true
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
    "message" => "No face data was provided to check the face against"
  ]));
}
?>
