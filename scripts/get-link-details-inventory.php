 <?php

/**
 * get-link-details-inventory.php
 * */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set("America/Los_Angeles");

require 'vendor/autoload.php';
require("RedBean/rb.php");
require_once("spotlight.inc.php");

// Library
use Goutte\Client;
$client = new Client();

R::setup( 'mysql:host=localhost;dbname=cytek', 'root', '' ); 
$query_limit = 1;
if(isset($argv[1])){
       $query_limit = $argv[1];
}

$products_urls = 'biolegendlinks';
$products_inventory = "productsinventory";

print "Limit: " . $query_limit . PHP_EOL;
// Get products
$urls = R::findAll($links_table, " WHERE link != 'https://www.biolegend.com' AND status IS NULL LIMIT " . $query_limit);

/*$error_log_file = create_log_file('get-link-details');
record_log_message("Number of links to process: " . $query_limit);
record_log_message(date("Y-m-d h:i:s A") . " Get Links Details Started");*/

// Iterate through result set of products
// Retrieve product detail page
// Update DB with retrieved detail information

$temp_arr = array();
$data = array();

// Set retrieved links records to Pending
// Allows for another job to run a parallel query
foreach($urls as $url){
       R::exec("UPDATE " . $products_urls . " SET `status` = 'Pending'  WHERE id = '" . $link->id . "'" ); 
}


foreach($links as $link){
       
       $link_to_fetch = $link->link;
       $link_id = $link->id;
       print 'Fetching link ' . $link->link . PHP_EOL;

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

       // Get Other Information
       $crawler->filter('#productInfo > dl')->each(function ($nodex){
              foreach($nodex->children() as $child){
                     global $labels;
                     global $labels_values;

                     if($child->tagName == "dt"){            
                            switch(trim($child->nodeValue)){
                                   case "Clone":
                                          $key_name = "clone";
                                          break;
                                   case "Regulatory Status":
                                          $key_name = "regulation";
                                          break; 
                                   case "Workshop":
                                          $key_name = "workshop";
                                          break;
                                   case "Other Names":
                                          $key_name = "othernames";
                                          break; 
                                   case "Isotype":
                                          $key_name = "isotype";
                                          break;
                                   default:
                                          $key_name = trim($child->nodeValue);
                            }            
                            $labels[] = $key_name; 
                     }
                     if($child->tagName == "dd"){                            
                            $labels_values[] = trim($child->nodeValue); 
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
              $data[] = $temp_arr;
              unset($temp_arr);

       });



}

global $data;

foreach($data as $key=>$product){

       $new_product = R::dispense( $products_inventory );
       $new_product->catalog_id = $product['catalog_id'];
       $new_product->price = str_replace('$','', $product['price']);
       $new_product->inventory = $product['current_stock'];
       $new_product->last_modified = date('Y-m-d H:i:s');
       R::store($new_product);        

}


foreach($data as $key=>$product){

       $link = $product['link'];
       $link_id = $product['link_id'];
       try{
              R::exec("UPDATE " . $links_table . " SET status = 'Done'  WHERE `id` = '" . $link_id . "'" );
       }catch (\Exception $e) {
              print $e->getMessage();
       }

}


//record_log_message(date("Y-m-d h:i:s A") . " Get Links Details Completed");