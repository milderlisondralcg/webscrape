<?php

define("LOG_PATH","log/");
define("BASE_URL","https://www.biolegend.com");

$current_datetime = date("Y-m-d-h-i-s-A");

/**
 * create_log_file
 * Create log file to be used during sync job run
 * @param string $partner_folder Name of partner
 * @return string $path
 * */
function create_log_file($job_type){
    global $current_datetime;

    $path = $job_type . '-' .$current_datetime . '.log';
    return $path;
}

/**
* record_log_message
* @param $log_message string
* Record given $log_message to predefined error log file ( $error_log_file )
* */
function record_log_message($log_message){

  global $error_log_file;

  $cdt = date("Y-m-d h:i:s a");
  $error_message = $log_message . "\r\n";
  error_log($error_message, 3, LOG_PATH.$error_log_file);

}

function search_for_link($product_name, $links_arr) {
   foreach ($links_arr as $key => $val) {
       if ($val['name'] === $product_name) {
            return $val['link'];
           //return $key;
       }
   }
   return null;
}

/**
 * upload_listing_file
 * Upload a file into S3
 * @param string $local_file_path Local path to file
 * @param string $json_file Filename to be uploaded
 * */
function upload_listing_file($local_file_path,$json_file){

    try{
       $result = $this->s3_object->putObject([
              'Bucket' => $this->s3_bucket,
              'Key' => $this->json_files_path . $json_file,
              'SourceFile' => $local_file_path,
          ]); return true;

      } catch (AwsException $e) {
          // output error message if fails
          echo $e->getMessage().PHP_EOL;
            return false;
      }
} 

/**
 * @param array $params
 * */
function create_json_file($params){

        $filename = $params["filename"];
        $products = $params["data"];
        $myfile = fopen($filename, "w") or die("Unable to open file!");        
        fwrite($myfile, json_encode($products));
        fclose($myfile);
}