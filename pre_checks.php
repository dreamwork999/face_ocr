<?php
// var_dump($_FILES);
// make sure the uploaded file is within the size limit
if (isset($_FILES["fileToUpload"]) and $_FILES["fileToUpload"]["size"] > $uploadSizeLimit) {
	echo "Sorry, the file you uploaded exceeds the maximum allowed size of $uploadSizeLimit bytes <br>";
	$uploadOk = 0;
}

// see if the image file actualy an image
if(isset($_FILES["fileToUpload"])) {
	$check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
	if($check !== false) {
		$uploadOk = 1;
	} else {
		echo "The file is not an image <br>";
		$uploadOk = 0;
	}
}
else {
	echo "No file was uploaded <br>";
}

// only allow specific file formats
if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
	echo "We only allow JPG, JPEG, PNG files to be uploaded <br>";
	$uploadOk = 0;
}
?>
