<?php
if (!isset($_GET["apikey"]) or $_GET["apikey"] !== "dcee6b8c-0a56-4ec8-91be-ef9fee34e209") {
  die(json_encode([
		"status" => "error",
		"message" => "No API key provided or incorrect key. Error has been logged"
	]));
}
?>
