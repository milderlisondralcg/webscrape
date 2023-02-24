 <?php

/**
 * get-inventory-only.php
 * Retrieve inventory for each product
 * @author Milder Lisondra
 * */

require_once "../inc/config.php";

// Library
use Goutte\Client;
$client = new Client();

R::setup( 'mysql:host=localhost;dbname=cytek', 'root', '' ); 
$query_limit = 1;
if(isset($argv[1])){
       $query_limit = $argv[1];
}

$links_table = LINKS;

print "Limit: " . $query_limit . PHP_EOL;
// Get products
$links = R::findAll($links_table, " WHERE status IS NULL LIMIT $query_limit");

$error_log_file = create_log_file('get-inventory-only');
record_log_message("Number of links to process: " . $query_limit);
record_log_message(date("Y-m-d h:i A") . " Get Inventory Only Started");

// Iterate through result set of products
// Retrieve product detail page
// Update DB with retrieved detail information

$temp_arr = array();
$data = array();

// Set retrieved links records to Pending
// Allows for another job to run a parallel query
foreach($links as $link){
       R::exec("UPDATE " . $links_table . " SET `status` = 'Pending'  WHERE catalog_id = '" . $link->catalog_id . "'" ); 
}


foreach($links as $link){
       
       $link_to_fetch = $link->url;
       $link_id = $link->id;
       print 'Fetching link ' . $link_to_fetch . PHP_EOL;

       // Take above URL and retrieve using DOM Crawler
       $crawler = $client->request('GET', $link_to_fetch);

       $labels_arr = array();
       $vals_arr = array();
       $key_names = array(1=>"catalog_id",2=>"size",3=>"price",4=>"inventory");
       $product_name = "";
       $data_pair = [];
       $labels = [];
       $labels_values = [];

       // Get Product Name
       $title = $crawler->filter('.container')->each(function ($nodex){
              foreach($nodex->children() as $child){
                     if($child->tagName == "h1"){
                            global $product_name;
                            $product_name = trim($child->nodeValue);
                     }
              }
       });

       // Get the different catalog ids and related information
       $all = $crawler->filter('#variantsContainer > tbody > tr')->each(function ($nodex){

              $counter = 1;

              // Get Product ID, Product Size, Price
              foreach($nodex->children() as $child){
                                          
                     if($counter < 4){

                            global $labels_arr;
                            global $vals_arr;
                            global $temp_arr;
                            global $key_names;
                            
                            $key_name = $key_names[$counter];
                            $temp_arr[$key_name] = trim($child->nodeValue);

                            $counter++;

                     }                 

              }
                     // Get current inventory
                     $nodex->filter('td > label > input')->each(function ($node2) {
                            global $temp_arr;
                            //print 'INVENTORY: '.$node2->attr('data-stock')  . PHP_EOL;
                            $temp_arr['current_stock'] = $node2->attr('data-stock');

                     }); 


              global $catalog_id;
              global $data;
              global $temp_arr;
              global $product_name;
              global $labels;
              global $labels_values;
              global $link_to_fetch;
              global $link_id;

              $temp_arr['name'] = $product_name;
              $temp_arr['link'] = $link_to_fetch;
              $temp_arr['link_id'] = $link_id; 

              foreach($labels as $key=>$label){
                     $temp_arr[$label] = $labels_values[$key];
              }
              if(!array_key_exists($temp_arr['catalog_id'],$data)){
                     $data[$temp_arr['catalog_id']] = $temp_arr;
                     unset($temp_arr);
              }

       });



}

global $data;

$products_table = "productsinventory";

foreach($data as $key=>$product){

       $inventory = 0;
       if( isset($product['current_stock']) ){
              $inventory = trim($product['current_stock']);
       }
       print "Inserting catalog id: " . $product['catalog_id'] . PHP_EOL;
       record_log_message(date("Y-m-d h:i:s A") . " " .$product['catalog_id']);              
       $new_product = R::dispense( $products_table );
       $new_product->catalog_id = $product['catalog_id'];
       $new_product->price = str_replace('$','', $product['price']);
       $new_product->inventory = $inventory;
       $new_product->last_modified = date('Y-m-d h:i:s');
       $new_product->date_acquired = date('Y-m-d');
       R::store($new_product);        

}


foreach($data as $key=>$product){

       $catalog_id = $product['catalog_id'];
       try{
              R::exec("UPDATE " . $links_table . " SET status = 'Done'  WHERE `catalog_id` = '" . $catalog_id . "'" );
       }catch (\Exception $e) {
              R::exec("UPDATE " . $links_table . " SET status = 'Error'  WHERE `catalog_id` = '" . $catalog_id . "'" );
              print $e->getMessage();
       }

}

record_log_message(date("Y-m-d h:i A") . " Get Inventory Only Completed");