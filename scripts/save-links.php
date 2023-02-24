 <?php

/**
 * save-links.php
 * Read JSON files from given directory and store data into given database table
 * @author Milder Lisondra
 * */

require_once "../inc/config.php";

// Connect to db
R::setup( 'mysql:host=localhost;dbname=cytek', 'root', '' );

$error_log_file = create_log_file('save-links');
record_log_message(date("Y-m-d h:i A") . " Save Links to database Started");

// Files location
$json_files_location = "../json-files/";
$json_files_processed = "../json-files-processed/";

// Products urls table
$products_links = LINKS;
// Number of files to process
$num_files_to_process = 290;
if(isset($argv[1])){
       $num_files_to_process = $argv[1];
}

record_log_message("Number of files to process: " . $num_files_to_process);

$files_list =  array_slice(array_diff(scandir($json_files_location), array('..', '.')),0,$num_files_to_process);

foreach($files_list as $file){

    print 'Processing file: ' . $json_files_location . $file . PHP_EOL;
    record_log_message('Processing file ' . $file);
    $file_data = file_get_contents('../json-files/' . $file);

    $json_data = json_decode($file_data);

    foreach($json_data as $product_item){

        $url = $product_item->url;
        $catalog_id = $product_item->catalog_id;

            record_log_message($url);
            $link_query_result = R::getRow( 'SELECT * FROM ' . $products_links . ' WHERE catalog_id = ? LIMIT 1', [ $catalog_id ] );
           
            if( empty($link_query_result) ){
                $product = R::dispense( $products_links );
                $product->catalog_id = $product_item->catalog_id;
                $product->url = $product_item->url;
                R::store($product);            
            }
    }

    // Delete processed JSON file
    $original_file = $json_files_location . $file;
    unlink($original_file);

}

// Set all records to status of NULL
R::exec( 'UPDATE ' . $products_links . ' SET `status` = NULL;' );
record_log_message(date("Y-m-d h:i A") . " Save Links to database Completed");