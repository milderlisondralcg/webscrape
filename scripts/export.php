<?php

/**
 * export.php
 * Export products data and create an Excel file
 * */
date_default_timezone_set('America/Los_Angeles');

require 'vendor/autoload.php';
require("RedBean/rb.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet; 
use PhpOffice\PhpSpreadsheet\Writer\Xlsx; 

// Connect to db
R::setup( 'mysql:host=localhost;dbname=cytek', 'root', '' );

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1','Link');
$sheet->setCellValue('B1','Product Name');
$sheet->setCellValue('C1','Catalog Number');
$sheet->setCellValue('D1','Stock');
$sheet->setCellValue('E1','Price');
$sheet->setCellValue('F1','Size');
$sheet->setCellValue('G1','Regulatory Status');
$sheet->setCellValue('H1','Isotype');
$sheet->setCellValue('I1','Clone');
$sheet->setCellValue('J1','Application Status');

$writer = new Xlsx($spreadsheet);
$filename = 'Spotlight-Export-' . date('mdY') . '.xlsx';
$filename_path = 'exports/' . $filename;

$products = R::find( 'products' , ' ORDER BY catalog_id ASC');
print 'Number of records found: ' . count($products) . PHP_EOL;
$col = 2;
foreach($products as $product){
	$link = 'A' . $col;
	$name_cell = 'B' . $col;
	$catalog_id_cell = 'C' . $col;
	$stock_cell = 'D' . $col;
	$price_cell = 'E' . $col;
	$size_cell = 'F' . $col;
	$regulation_cell = 'G' . $col;
	$isotype_cell = 'H' . $col;
	$clone_cell = 'I' . $col;
	$application_cell = 'J' . $col;
		
	$sheet->setCellValue($link, $product['link']);
	$sheet->setCellValue($name_cell, $product['name']);
	$sheet->setCellValue($catalog_id_cell, $product['catalog_id']);
	$sheet->setCellValue($stock_cell, $product['inventory']);
	$sheet->setCellValue($price_cell, $product['price']);
	$sheet->setCellValue($size_cell, $product['size']);
	$sheet->setCellValue($regulation_cell, $product['regulation']);
	$sheet->setCellValue($isotype_cell, $product['isotype']);
	$sheet->setCellValue($clone_cell, $product['clone']);
	$sheet->setCellValue($application_cell, $product['application']);
	
	$col++;
}


$writer->save($filename_path);