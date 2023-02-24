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
//require_once("classes/db.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet; 
use PhpOffice\PhpSpreadsheet\Writer\Xlsx; 

$dbconn = new DB();

$error_log_file = create_log_file('store-delta');
record_log_message(date("Y-m-d h:i:s A") . " Store Deltas");

$limit = 1;
if(isset($argv[1])){
	$limit = $argv[1];
}

$dbconn->get_products($limit);
record_log_message(date("Y-m-d h:i:s A") . " Store Deltas Completed");
class DB{

	public $inventory_table = 'productsinventory_october';
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
		$date_acquired = str_replace("-","",$date_acquired);

		foreach($results as $result){
			extract($result);

			$link_query_result = R::getRow( 'SELECT * FROM ' . $this->inventory_delta . ' WHERE catalog_id = ? LIMIT 1', [ $catalog_id ] );

			if( empty($link_query_result) ){
			  $delta_table = R::dispense( $this->inventory_delta );
			  $delta_table->catalog_id = $catalog_id;
			  // $date_col = "date_".$date_acquired;
			  $delta_table->{$date_col} = $inventory;
			  R::store($delta_table);
       
			}else{

				//$date_col = "date_".$date_acquired;
			R::exec( "UPDATE " . $this->inventory_delta . " SET `".$date_col."` = '".$inventory."' WHERE catalog_id = '".$catalog_id."'" );
				//print 'Update catalog id: ' . $catalog_id . PHP_EOL;
			}

		}

	}

	public function get_delta(){

		$results = R::getAll("SELECT catalog_id, date_20221117, date_20221119, date_20221121, date_20221122 from " . $this->inventory_delta );
		print count($results);

	}


	public function get_products($query_limit){ 

		//$results = R::getAll("SELECT catalog_id from ".$this->inventory_table." WHERE last_modified >= '".$this->start_date."' AND last_modified < '".$this->end_date."' and delta_status IS NULL group by catalog_id LIMIT " . $query_limit);
		
		$results = R::getAll("SELECT catalog_id from " . $this->inventory_table . " WHERE date_acquired IN  ('2022-10-30') AND delta_status IS NULL LIMIT " . $query_limit);

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
		//$result = R::getAll("SELECT inventory, last_modified from " .$this->inventory_table. " WHERE last_modified >= '".$this->start_date."' AND last_modified < '".$this->end_date."' AND catalog_id = '" . $catalog_id . "'");

		$result = R::getAll("SELECT inventory, last_modified, date_acquired from " .$this->inventory_table. " WHERE date_acquired IN  ('2022-10-30') AND catalog_id = '" . $catalog_id . "' ORDER BY date_acquired ASC");


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

			$temp_arr[$date_acquired] = $inventory;
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
	 * save to DB
	 * */
	public function save($data){

		$dates_keys = array_keys($data[0]['inventory']);

		$date_str = "";
		$delta_str = "";
		$inventory_str = "";

		foreach($data as $val){

			// get the catalog id
			$catalog_id = $val['catalog_id'];
			print 'Processing Catalog ID: '. $catalog_id . PHP_EOL;
			  $delta_table = R::dispense( $this->inventory_delta );
			  $delta_table->catalog_id = $catalog_id;
			  R::store($delta_table);

			//print 'Catalog id: '.$catalog_id . PHP_EOL;
			$inventory_str = implode(",",$val['inventory']);

			$cols = "";
			$inventories = "";

/*			foreach($val['inventory'] as $key=>$inventory){

				$col = "date_".str_replace("-","",$key);
				R::exec( "UPDATE " . $this->inventory_delta . " SET `".$col."` = '".$inventory."' WHERE catalog_id = '".$catalog_id."'" );
			}	*/
			$date_20221030 =  $val['inventory']['2022-10-30'];
			//$date_20221121 =  $val['inventory']['2022-11-21'];
			//$date_20221128 =  $val['inventory']['2022-11-28'];
			$delta = $val['delta'];

			$update_str = "UPDATE " . $this->inventory_delta . " SET date_20221030 = '".$date_20221030."' WHERE catalog_id = '".$catalog_id."';";

			//$update_str = "UPDATE " . $this->inventory_delta . " SET date_20221114 = '".$date_20221030."', date_20221121 = '".$date_20221121."', date_20221128 = '".$date_20221128."', delta = '".$delta."' WHERE catalog_id = '".$catalog_id."';";
			//$update_str .= "UPDATE " . $this->inventory_delta . " SET date_20221121 = '".$date_20221121."';";
			//$update_str .= "UPDATE " . $this->inventory_delta . " SET date_20221128 = '".$date_20221128."';";

			R::exec( $update_str );

			//$delta = $val['delta'];
			// Update delta for given catalog id
			//R::exec( "UPDATE " . $this->inventory_delta . " SET `delta` = '".$delta."' WHERE catalog_id = '".$catalog_id."'" );
			//Update Products Inventory set delta status to DONE
			R::exec( "UPDATE " . $this->inventory_table . " SET `delta_status` = 'DONE' WHERE catalog_id = '".$catalog_id."'" );
			//print 'Catalog ID: Completed '. $catalog_id . PHP_EOL;

		}

	}


}