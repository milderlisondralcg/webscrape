<?php

/**
* delta.php
* Calculate the delta for inventory for given month
*
**/

date_default_timezone_set('America/Los_Angeles');

require 'vendor/autoload.php';
require("RedBean/rb.php");
require_once("spotlight.inc.php");
require_once("classes/db.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet; 
use PhpOffice\PhpSpreadsheet\Writer\Xlsx; 

$dbconn = new DB();

$error_log_file = create_log_file('update-delta');
record_log_message(date("Y-m-d h:i:s A") . " Begin Update Products Delta");

// Connect to db
//R::setup( 'mysql:host=localhost;dbname=cytek', 'root', '' );

$inventory_table = 'productsinventory'; // Production

//$date_to_fetch = 1;
if(isset($argv[1])){
	$date_to_fetch = $argv[1];
}else{ 
	print 'Enter Date to Fetch' . PHP_EOL;
	exit(); 
}

//$dbconn->get_products($query_limit);
$dbconn->get_product_by_date($date_to_fetch);
record_log_message(date("Y-m-d h:i:s A") . " Complete Update Products Delta");


class DBA{

	public $inventory_table = 'productsinventory';
	public $inventory_delta = 'productsdelta';
	public $query_limit = 10;
	public $start_date = "2022-11-10";
	public $end_date = "2022-12-01";
	public $all_results = array();

	function __construct(){
		// Setup Redbean ORM
		R::setup( 'mysql:host=localhost;dbname=cytek', 'root', '' );
	}

	public function get_product_by_date($date_acquired){

		$date_acquired = str_replace("-","",$date_acquired);
		$date_col = "date_".$date_acquired;

		R::exec( 'ALTER TABLE ' . $this->inventory_delta . ' ADD COLUMN ' . $date_col . ' INT' );

		// select catalog_id,inventory, date_acquired from productsinventory where date_acquired IN '2022-11-21')
		$results = R::getAll("SELECT catalog_id, inventory FROM ".$this->inventory_table." WHERE date_acquired IN ('".$date_acquired."')");

		print "Date Acquired: " . $date_acquired . PHP_EOL;
		print "Number of records: " . count($results);
		//$date_acquired = str_replace("-","",$date_acquired);
		print_r($results);
/*		foreach($results as $result){

			extract($result);

			$link_query_result = R::getRow( 'SELECT * FROM ' . $this->inventory_delta . ' WHERE catalog_id = ? LIMIT 1', [ $catalog_id ] );
			print_r($link_query_result);
			if( empty($link_query_result) ){
			  $delta_table = R::dispense( $this->inventory_delta );
			  $delta_table->catalog_id = $catalog_id;
			  // $date_col = "date_".$date_acquired;
			  $delta_table->{$date_col} = $inventory;
			  R::store($delta_table);
       		print 'Add' . PHP_EOL;
			}else{

				//$date_col = "date_".$date_acquired;
			R::exec( "UPDATE " . $this->inventory_delta . " SET `".$date_col."` = '".$inventory."' WHERE catalog_id = '".$catalog_id."'" );
				print 'Update catalog id: ' . $catalog_id . PHP_EOL;
			}

		}*/

	}

	function get_products($query_limit){ 

		if($query_limit){

		}

		$results = R::getAll("SELECT catalog_id from ".$this->inventory_table." WHERE last_modified >= '".$this->start_date."' AND last_modified < '".$this->end_date."' and delta_status IS NULL group by catalog_id LIMIT " . $query_limit);
		
		foreach($results as $result){

			foreach($result as $catalog_id){
				//print "Catalog ID: " . $catalog_id . PHP_EOL;
				//$this->get_product($catalog_id);
				$this->all_results[] = $this->get_product($catalog_id);
				// Retrieve inventory for all records with given catalog id

			}
			
		}
		//print_r($this->all_results);
		$this->save($this->all_results);	
	}

	public function get_product($catalog_id){
		$result = R::getAll("SELECT inventory, last_modified from " .$this->inventory_table. " WHERE last_modified >= '".$this->start_date."' AND last_modified < '".$this->end_date."' AND catalog_id = '" . $catalog_id . "'");

		// Go through the recordset and get the delta
		$cur_inventory = 0;
		$total_records = count($result);
		$num_processed = 0;

		$results_arr = array();
		$temp_arr = array();
		$results_arr['catalog_id'] = $catalog_id;

		foreach($result as $val){
			
			extract($val);
			//print "Inventory " . $inventory . " Date Inventory Taken: " . $last_modified . PHP_EOL;

			$temp_arr[$last_modified] = $inventory;
			$results_arr['inventory'] =  $temp_arr;
			if($inventory > $cur_inventory){
				$cur_inventory = $cur_inventory + $inventory;
			}
			
			$num_processed++;
			
			if($num_processed == $total_records){
				$delta = 0;
				if($cur_inventory > $inventory ){
					$delta = $cur_inventory - $inventory;
				}

				// Assign delta to array
				$results_arr['delta'] =  $delta;
			}

		}

		//print_r($results_arr);
		return $results_arr;
	}

	/**
	 * possibly save to DB
	 * */
	public function save($data){
		//print_r($data[0]['inventory']);

		$dates_keys = array_keys($data[0]['inventory']);
		foreach($dates_keys as $val){
			//print substr($val,0,10) . PHP_EOL;
		}

		$date_str = "";
		$delta_str = "";
		$inventory_str = "";

		foreach($data as $val){

			// get the catalog id
			$catalog_id = $val['catalog_id'];
			
			//print_r(array_keys($val['inventory']));

			print 'Catalog id: '.$catalog_id . PHP_EOL;
			$inventory_str .= implode(",",$val['inventory']);			
			$delta = $val['delta'];

			echo "Inventory string: " . $inventory_str . PHP_EOL;
			echo "Delta: " . $delta . PHP_EOL;

		}
		// echo "Inventory string: " . $inventory_str . PHP_EOL;
		// echo "Delta: " . $delta . PHP_EOL;
	}


}