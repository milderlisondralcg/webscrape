<?php

/**
* update-delta.php
* Update products delta from monthly table ( ie productsinventoryseptember )
* Takes approximately 1 minute to run through 1500 records from monthly table to productsdelta
*
**/

date_default_timezone_set('America/Los_Angeles');

require 'vendor/autoload.php';
require("RedBean/rb.php");
require_once("spotlight.inc.php");
//require_once("classes/db.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet; 
use PhpOffice\PhpSpreadsheet\Writer\Xlsx; 

//$dbconn = new DB();

$error_log_file = create_log_file('update-delta');
record_log_message(date("Y-m-d h:i:s A") . " Store Deltas");

$limit = 1;
if(isset($argv[1])){
	$limit = $argv[1];
}

if(isset($argv[2])){
	$date_acquired = $argv[2];
}else{ 
	print 'Date Required is missing' . PHP_EOL;
	print 'Processing stopped' . PHP_EOL;
	exit(); 
}

if(isset($argv[3])){
	$table_name = $argv[3];
}else{ 
	print 'Table Name is missing' . PHP_EOL;
	print 'Processing stopped' . PHP_EOL;
	exit(); 
}

print $date_acquired . PHP_EOL;
print $table_name . PHP_EOL;

$dbconn = new DB($date_acquired, $table_name);
$dbconn->get_products($limit);
record_log_message(date("Y-m-d h:i:s A") . " Store Deltas Completed");

class DB{

	public $inventory_table = "";
	public $inventory_delta = 'productsdelta';
	public $query_limit = 10;
	public $start_date = "2022-11-10";
	public $end_date = "2022-12-01";
	public $all_results = array();
	public $date_acquired;

	function __construct($date_acquired, $table_name){
		
		$this->date_acquired = $date_acquired;
		$this->inventory_table = $table_name;

		// Setup Redbean ORM
		R::setup( 'mysql:host=localhost;dbname=cytek', 'root', '' );
	}

	public function get_products($query_limit){

		$results = R::getAll("SELECT catalog_id from " . $this->inventory_table . " WHERE date_acquired IN  ('".$this->date_acquired."') AND delta_status IS NULL LIMIT " . $query_limit);
		foreach($results as $result){
			// $this->all_results[] = $this->get_product($result['catalog_id']);
			//$data = $this->get_product($result['catalog_id']);
			if( $this->get_product($result['catalog_id']) ){
				$data = $this->get_product($result['catalog_id']);
				$this->save($data);
			}
			//$this->save($data);
		}
	}

	public function get_product($catalog_id){

		$sql_str =  "SELECT inventory, date_acquired from " .$this->inventory_table. " WHERE date_acquired IN  ('".$this->date_acquired."') AND catalog_id = '" . $catalog_id . "' ORDER BY date_acquired ASC";

		$result = R::getRow($sql_str);
		if( count($result) > 0 ){
			$result['catalog_id'] = $catalog_id;
			return $result;
		}else{ return false; }
		//$result['catalog_id'] = $catalog_id;
		//return $result;

	}

	/**
	 * save
	 * Update Catalog ID for specificed column with specified inventory
	 * */
	public function save($data){



			// get the catalog id
			$catalog_id = $data['catalog_id'];

			$get_result = R::getRow( 'SELECT * FROM ' . $this->inventory_delta . ' WHERE catalog_id = ? LIMIT 1', [ $catalog_id ] );
			$delta_table_id = $get_result['id'];
			$inventory =  $data['inventory'];

			print 'Processing Catalog ID: '. $catalog_id . PHP_EOL;
			print "Inventory: " . $inventory . PHP_EOL;

			// Create column name based on date acquired
			$col_name = "date_" . str_replace("-","",$this->date_acquired);

			// Update delta table based on table id
			$update_str = "UPDATE " . $this->inventory_delta . " SET ".$col_name." = '".$inventory."' WHERE id = '".$delta_table_id."';";

			R::exec( $update_str ); // Doesn't stop on failure

			//Update Products Inventory set delta status to DONE
			$update_delta_str = "UPDATE " . $this->inventory_table . " SET `delta_status` = 'DONE' WHERE catalog_id = '".$catalog_id."' AND date_acquired in ('".$this->date_acquired."')";

			R::exec( $update_delta_str );

	}


}