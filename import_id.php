<?php
include 'config.php';
include 'api_key.php';

$OcrlocalUploadDir = "ocr/";
// create a random name for the file
$fileName = uniqid() . '.jpg';
// $fileName = "input.jpg";
$targetFile = $OcrlocalUploadDir . $fileName;

$uploadOk = 1;
$imageFileType = strtolower(pathinfo($targetFile,PATHINFO_EXTENSION));

include 'pre_checks.php';
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

// see if the $uploadOk variable has been set to 0 by an error
if ($uploadOk == 0) {
	die(json_encode([
		"status" => "error",
		"message" => "No image was uploaded"
	]));
// no error was encountered, try to upload file
} else {
	if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $targetFile)) {

		$OcrResult = json_decode(includeWithVariables('ocr/read_ocr.php', array('input' => $fileName), false), true);
		// $OcrResult = ob_get_clean();
		// var_dump($OcrResult);

		// now process the ID card OCR data
		$jd = getJurisdiction($OcrResult["data"]);
		// var_dump($jd);

		$id_line_no = 0;
		$idno = getIdno($jd, $OcrResult["data"]);
		// var_dump($idno);

		$name = getName($jd, $OcrResult["data"], $id_line_no);
		// var_dump($name);

		$bidno = null;
		if (isset($_POST["bidno"]) and !empty($_POST["bidno"])) {
			$bidno = $_POST["bidno"];
		}

		try {
			// Check for faces
			$face_result = json_decode(shell_exec("python3 face/import_face.py /var/www/html/".$targetFile), true);
			// var_dump(shell_exec("python3 face/import_face.py /var/www/html/".$targetFile));

			if ($face_result["status"] == "error") {
				// now cleanup the file we created
				unlink("/var/www/html/".$targetFile);

				die(json_encode([
					"status" => "error",
					"message" => $face_result["message"]
				]));
			}
			else {
				// var_dump($face_result);
				$save_result = saveBid($name["namef"], $name["namel"], $idno, $jd, $face_result["data"]);
				if ($save_result !== false) {

					$s3_bucket = "https://id-cards-cold-storage.s3.us-west-2.amazonaws.com/";

					// upload $targetFile to the s3 bucket
					$cmd = "aws s3 cp /var/www/html/".$targetFile." s3://id-cards-cold-storage/".$idno.".jpg";
					exec($cmd);

					// now cleanup the file we created
					unlink("/var/www/html/".$targetFile);


					echo json_encode([
						"status" => "success",
						"message" => "Ext ID imported OK",
						"data" => $save_result
					]);
				}
				else {
					// now cleanup the file we created
					unlink("/var/www/html/".$targetFile);

					echo json_encode([
						"status" => "error",
						"message" => "An error occured when importing the ID and has been logged"
					]);
				}
			}
		}
		catch (Exception $ex) {
			// TODO: add logging here
			// echo "Error: face recognition failed. Error has been logged. <br>";
			// var_dump($ex);
		}
		
	}
}


function saveBid($namef, $namel, $idno, $jd, $face_geometry) {
	global $bidno, $conn;
	$id_file = [];

	if (isset($bidno)) {
		// we already have a B-ID

		$existing_ext_id = checkIdInDb($idno);
		// lets check if this B-ID has this 3rd party ID we are trying to add already
		if (isset($existing_ext_id)) {
			// yes, we already have it - do nothing
		}
		else {
			// we have B-ID but not the 3rd party ID
			$bidno = $existing_bid;
			$id_file[$existing_bid][$idno] = [
				"namef" => $namef,
				"namel" => $namel,
				"jd" => $jd
			];
			$output_json = json_encode($id_file);
			try {
				// $sql = "insert into ext_ids (bidno, extIdNo, namef, namel, jd) VALUES ('$bidno', '$idno', '$namef', '$namel', '$jd');";
				// $result = $conn->query($sql);

				// if ($conn->query($sql) === TRUE) {
				//   // echo "New record created successfully (we have B-ID but not the 3rd party ID)";
				// } else {
				// 	// TODO: add logging here
				//   // echo "Error: " . $sql . "<br>" . $conn->error;
				// 	return false;
				// }
			}
			catch (Exception $ex) {
				// TODO: add logging here
				// echo "Error";
				return false;
			}
		}
	}
	else {
		// we dont have a B-ID yet, lets add both
		$bidno = substr($namef, 0, 1).substr($namel, 0, 1)."-".rand(100000,999999);

		$new_bid = [
			$idno => [
				"namef" => $namef,
				"namel" => $namel,
				"jd" => $jd
			]
		];
		$id_file[$bidno] = $new_bid;
		$output_json = json_encode($id_file);
		// var_dump($output_json);

		// save new id data to DB
		try {
			// $sql = "insert into face_api (namef, namel, bidno, face_geometry) VALUES ('$namef', '$namel', '$bidno', '".json_encode($face_geometry)."');";
			// if ($conn->query($sql) === TRUE) {
			//   // echo "New record created successfully (brand new BID)";
			// } else {
			// 	// TODO: add logging here
			//   // echo "Error: " . $sql . "<br>" . $conn->error;
			// 	return false;
			// }
		}
		catch (Exception $ex) {
			// TODO: add logging here
			// echo "Error";
			return false;
		}
		try {
			// $sql2 = "insert into ext_ids (bidno, extIdNo, namef, namel, jd) VALUES ('$bidno', '$idno', '$namef', '$namel', '$jd');";
			// if ($conn->query($sql2) === TRUE) {
			// 	// echo "New record created successfully (brand new BID and ext ID)";
			// } else {
			// 	// TODO: add logging here
			// 	// echo "Error: " . $sql2 . "<br>" . $conn->error;
			// 	return false;
			// }
		}
		catch (Exception $ex) {
			// TODO: add logging here
			// echo "Error";
			return false;
		}
	}
	return [
		// "bidno" => $bidno,
		"namef" => $namef,
		"namel" => $namel,
		"face_data" => $face_geometry,
		"extIdAdded" => [
			"extIdNo" => $idno,
			"namef" => $namef,
			"namel" => $namel,
			"jd" => $jd
		]
	];
}

function checkIdInDb($idno) {
	global $conn;
	$existingBidno = null;
	try {
		// $sql = "SELECT * from ext_ids left join face_api on ext_ids.bidno=face_api.bidno where extIdNo='$idno'";
		// $result = $conn->query($sql);

		// if ($result and $result->num_rows > 0) {
		//     // output data of each row
		//     while($row = $result->fetch_assoc()) {
		//       if ($row["bidno"]) {
		// 				// this id already exists
		// 				$existingBidno = $row["bidno"];
		// 			}
		//     }
		// } else {
		//     // echo "0 results";
		// }
	}
	catch (Exception $ex) {
		// echo "Error: No faces in input image <br>";
		return null;
	}
	if ($existingBidno) {
		// this face is already in the DB, lets grab the existing B-ID and use that
		// return $existingBidno;
		return null;
	}
	else {
		return null;
	}
}

function getJurisdiction($ocr_line_array) {
	$merged_string = "";
	foreach ($ocr_line_array as $ocr_line) {
		$merged_string .= strtolower($ocr_line)." ";
	}
	$not_passport = stripos($merged_string, 'passport') == false;
	$visa = stripos($merged_string, 'visa') !== false;
	foreach($ocr_line_array as $text_soup) {
		$text_soup = strtolower($text_soup);
		if ($not_passport) {
			// university / college
			if (stripos($text_soup, 'university') !== false || stripos($text_soup, 'college') !== false) {
				// university of the bahamas
				if (stripos($text_soup, 'bahamas') !== false) {
					return 'univ_bahamas_id';
				}
			}

			// IDs
			if (
				stripos($text_soup, 'Baha') !== false or 
				stripos($text_soup, 'hama') !== false or 
				stripos($text_soup, 'amas') !== false or 
				stripos($text_soup, 'Bahamas') !== false or 
				stripos($text_soup, 'national insurance') !== false or
				stripos($text_soup, 'commonwealth') !== false
			) {
				return 'bahamas_id';
			}
			else if (stripos($text_soup, 'kansas') !== false or stripos($text_soup, ' KS ') !== false) {
				return 'ks_id';
			}
			else if (stripos($text_soup, 'california') !== false) {
				return 'ca_id';
			}
		}
		// visas (must come before passports, due to also including the word passport)
		else if ($visa) {
			if (stripos($text_soup, 'issuing post name control number') !== false) {
				return 'us_visa';
			}
		}
		// passports
		else if (!$not_passport) {
			if (stripos($text_soup, 'united kingdom') !== false) {
				return 'uk_passport';
			}
			else if (
				stripos($text_soup, 'Baha') !== false or 
				stripos($text_soup, 'hama') !== false or 
				stripos($text_soup, 'amas') !== false or 
				stripos($text_soup, 'Bahamas') !== false
			) {
				return 'bahamas_passport';
			}
		}
	}
	return "unknown_jurisdiction";
}

function getIdno($jd, $input_array) {
global $id_line_no;
  $output = null;
	foreach($input_array as $input) {
		if ($jd == "bahamas_id") {
	    preg_match('/[\d]{8}/', $input, $matches, PREG_OFFSET_CAPTURE);
			if ($matches) {
				$output = $matches[0][0];
			}
	  }
	  else if ($jd == "ks_id") {
	    preg_match('/[kK][\d]{2}[ -]?[\d]{2}[ -]?[\d]{4}/', $input, $matches, PREG_OFFSET_CAPTURE);
			if ($matches) {
		    $output = $matches[0][0];
		    $output = strtoupper(str_replace(" ", "-", $output));
			}
	  }
	  else if ($jd == "ca_id") {
	    preg_match('/[DLdl]{2}[ ][Dd][\d]{7}/', $input, $matches, PREG_OFFSET_CAPTURE);
			if ($matches) {
		    $output = $matches[0][0];
		    $output = strtoupper(str_replace("DL ", "", $output));
			}
	  }
		else if ($jd == "uk_passport") {
	    preg_match('/[\d]{9}/', $input, $matches, PREG_OFFSET_CAPTURE);
			if ($matches) {
		    $output = $matches[0][0];
			}
	  }
		else if ($jd == "bahamas_passport") {
	    preg_match('/[a-zA-Z]{2}[\d]{7}/', $input, $matches, PREG_OFFSET_CAPTURE);
			if ($matches) {
		    $output = $matches[0][0];
			}
	  }
		else if ($jd == "us_visa") {
	    preg_match('/[\d]{14}/', $input, $matches, PREG_OFFSET_CAPTURE);
			if ($matches) {
		    $output = $matches[0][0];
			}
	  }
		else if ($jd == "univ_bahamas_id") {
	    preg_match('/([\d]{3}[- ][\d]{3}[- ][\d]{3})|([\d]{3}[- ][\d]{2}[- ][\d]{4})/', $input, $matches, PREG_OFFSET_CAPTURE);
			if ($matches) {
		    $output = $matches[0][0];
			}
	  }
		if ($output) {
			return $output;
		}
		$id_line_no++;
	}
  return $output;
}

function getName($jd, $ocr_lines, $id_line_no) {
	// var_dump($ocr_lines);
	// var_dump($jd_line_no);
	$output = null;
  if ($jd == "bahamas_id") {
		$namef = $ocr_lines[$id_line_no + 2];
		// but if namef contains a space, use line below instead
		if (stripos($namef, ' ') !== false) {
			$namef = $ocr_lines[$id_line_no + 3];
		}
		$namel = $ocr_lines[$id_line_no + 1];
  }
  else if ($jd == "ks_id") {
		$namef = explode(" ", $ocr_lines[3])[0];
		$namel = $ocr_lines[2];
  }
  else if ($jd == "ca_id") {
		$namef = explode(" ", $ocr_lines[6])[1];
		$namel = explode(" ", $ocr_lines[5])[1];
  }
	else if ($jd == "uk_passport") {
		$namef = "?";
		$namel = "?";
  }
	else if ($jd == "bahamas_passport") {
		// first find which line contains the text "names"
		$names_line_no = 0;
		foreach ($ocr_lines as $line) {
			if (stripos($line, 'names') !== false) {
				break;
			}
			$names_line_no++;
		}
		$namef = explode(" ", $ocr_lines[$names_line_no + 1])[0];
		$namel = explode(" ", $ocr_lines[$names_line_no - 1])[0];
  }
	else if ($jd == "us_visa") {
		$namef = "?";
		$namel = "?";
  }
	else if ($jd == "univ_bahamas_id") {
		$namef = "?";
		$namel = "?";
  }
  else {
		// fallback
		$namef = "?";
		$namel = "?";
  }
	$output = ["namef" => $namef, "namel" => $namel];
  return $output;
}

function includeWithVariables($filePath, $variables = array(), $print = true) {
    $output = NULL;
    if(file_exists($filePath)){
        // Extract the variables to a local namespace
        extract($variables);

        // Start output buffering
        ob_start();

        // Include the template file
        include $filePath;

        // End buffering and return its contents
        $output = ob_get_clean();
    }
    if ($print) {
        print $output;
    }
    return $output;

}
?>
