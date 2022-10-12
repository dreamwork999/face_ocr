<?php
require __DIR__ . '/vendor/autoload.php';
use thiagoalessio\TesseractOCR\TesseractOCR;
use DiDom\Document;

include 'config.php';

$input_path = __DIR__ ."/";
if (file_exists($input_path.$input)) {
  try {
    // var_dump(shell_exec("pip install kraken"));
    // first resize the image to 1600px wide and maintain the aspect ratio
    shell_exec("convert -resize 1600x $input_path$input $input_path$input");

    // next, find the darkest color in the image using image magick
    $darkest_colors = shell_exec($input_path."get_darkest_pixels.sh $input_path$input");
    // split darkest color into an array by line
    $darkest_color_array = explode("\n", $darkest_colors);
    // get the darkest color
    $darkest_color = $darkest_color_array[0];
    // var_dump($darkest_colors);
    // use imageMagick to remove all pixels that are not black or near black
    // shell_exec('convert '.$input_path.$input.' -fill white -fuzz 22% +opaque "'.$darkest_color.'" '.$input_path."bin_".$input);
    shell_exec("kraken -i ".$input_path."".$input." ".$input_path."bin2_".$input."  binarize --threshold .40 --perc 70 --range 20 --low 4 --high 60");
    shell_exec("convert ".$input_path."bin2_".$input." -define connected-components:area-threshold=50 -define connected-components:mean-color=true -connected-components 15 ".$input_path."bin_clean_".$input.".png");

    $ocr = (new TesseractOCR($input_path.'bin_clean_'.$input.'.png'))
        ->oem(1)
        ->psm(12)
        // ->allowlist(range('A', 'Z'), range('a', 'z'), range('0', '9'))
        // ->userPatterns($input_path.'text-patterns.txt')
        ->lang('eng')
        ->hocr()
        ->run();

    $document = new Document($ocr);

    // now cleanup all the files we created above
    shell_exec("rm $input_path"."bin_".$input);
    shell_exec("rm $input_path"."bin2_".$input);
    shell_exec("rm $input_path"."bin_clean_".$input.".png");

    $words = $document->find('.ocrx_word');
    // $debug = "";
    foreach ($words as $word) {
        $word->setInnerHtml(preg_replace("/[^[:alnum:][:space:]\/]/u", '', $word->text()));
        // $debug .= $word->text()." - ".intval(trim(explode("x_wconf", $word->getAttribute('title'))[1]))."\n";
        if (
          // only contains letters
          (strlen($word->text()) <= 2 and intval(trim(explode("x_wconf", $word->getAttribute('title'))[1])) < 80) or
          (strlen($word->text()) <= 4 and intval(trim(explode("x_wconf", $word->getAttribute('title'))[1])) < 50) or
          (preg_match("/^[a-zA-Z]+$/", $word->text()) and intval(trim(explode("x_wconf", $word->getAttribute('title'))[1])) < 40)
        ) {
          // echo $word->text()."\n";
          $word->remove();
        }
    }
    // file_put_contents($input_path."debug.txt", $debug);

    $lines_array = [];
    $lines = $document->find('.ocr_line');
    foreach ($lines as $line) {
      if (strlen(trim($line->text())) > 0) {
        array_push($lines_array, preg_replace('/\s+/', ' ', str_replace("\n", "", trim($line->text()))));
      }
    }
    http_response_code(200);
    echo json_encode([
      "status" => "success",
      "message" => "OCR completed",
      "data" => $lines_array
    ]);
  } catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
      "status" => "error",
      "message" => "OCR failed. Error info has been logged"
    ]);
    $log_line = date("D M d, Y G:i")." Error: ".$e->getMessage()."\n";
    file_put_contents($input_path."read_ocr_log.txt", $log_line,  FILE_APPEND);
  }
}
else {
  http_response_code(500);
  echo json_encode([
    "status" => "error",
    "message" => "No input image provided. Error info has been logged"
  ]);
  $log_line = date("D M d, Y G:i")." Error: "."Input image file not found"."\n";
  file_put_contents($input_path."read_ocr_log.txt", $log_line,  FILE_APPEND);
}
?>
