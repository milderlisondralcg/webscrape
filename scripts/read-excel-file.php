<?php

/**
 * read-excel-file.php
 * */
date_default_timezone_set('America/Los_Angeles');

error_reporting(E_ALL);

require 'vendor/autoload.php';
require("RedBean/rb.php");
require_once("spotlight.inc.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet; 
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

R::setup( 'mysql:host=localhost;dbname=cytek', 'root', '' ); 

$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();

// Set file to be read
if(isset($argv[1])){
       $excel_filename = $argv[1];
       print $excel_filename . PHP_EOL;
}

// Set table to enter data into
if(isset($argv[2])){
       $inventory_table = $argv[2];
       print $inventory_table . PHP_EOL;
}

// Set table to enter data into
if(isset($argv[3])){
       $date_acquired = $argv[3];
       print $date_acquired . PHP_EOL;
}

// Set table to enter data into
if(isset($argv[4])){
       $files_dir = $argv[4];
       print $files_dir . PHP_EOL;
}


$excel_file_dir = "excel/".$files_dir."/";
$spreadsheet = $reader->load( $excel_file_dir . $excel_filename );
$d=$spreadsheet->getSheet(0)->toArray();
$sheetData = $spreadsheet->getActiveSheet()->toArray();

$i=1;

unset($sheetData[0]);

foreach ($sheetData as $item) {
 // process element here;
// access column by index

	$catalog_id = trim($item[2]);
	$inventory = trim($item[3]);
    $price = trim($item[4]);

print $inventory_table . PHP_EOL;

	print 'Processing Catalog ID: '. $catalog_id . PHP_EOL;
   $new_product = R::dispense( $inventory_table );
   $new_product->catalog_id = $catalog_id;
   $new_product->inventory = $inventory;
   $new_product->price = $price;
   $new_product->date_acquired = $date_acquired;
   R::store($new_product);        

	//echo $i."---".$t[0].",".$t[1]." <br>";
	
	$i++;
}