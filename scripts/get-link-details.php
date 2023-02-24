 <?php
 /**
  * Get Link Details
  * get-link-details.php
  * Retrieve product details from given URL
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

$links_table = 'biolegendlinks';

print "Limit: " . $query_limit . PHP_EOL;
// Get products
$links = R::findAll($links_table, " WHERE link != 'https://www.biolegend.com' AND status IS NULL LIMIT $query_limit");

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
foreach($links as $link){
       R::exec("UPDATE " . $links_table . " SET `status` = 'Pending'  WHERE id = '" . $link->id . "'" ); 
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

                    // print 'tag: '.$child->tagName . PHP_EOL;
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

                    // print_r($labels);
                    // print_r($labels_values); 
                                                             
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
                            $nodex->filter('td > label > input')->each(function ($node2) {
                                   //print_r($node2);
                                   //print $node2->attr('data-stock') . PHP_EOL;
                            });    

                     }                 

              }
                     // Get current inventory
                     $nodex->filter('td > label > input')->each(function ($node2) {
                            global $temp_arr;
                            print 'INVENTORY: '.$node2->attr('data-stock')  . PHP_EOL;
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
              print $link_id . PHP_EOL;
              foreach($labels as $key=>$label){
                     $temp_arr[$label] = $labels_values[$key];
              }
              $data[] = $temp_arr;
              //print_r($temp_arr);
              unset($temp_arr);

       });



}

global $data;


$products_table = "biolegendproducts";
// Update products
foreach($data as $key=>$product){
       // Get current product as object
       $id = $key;
       $catalog_id = $product['catalog_id']; 
       print $catalog_id . PHP_EOL;
       print $product['current_stock'] . PHP_EOL;
       unset($product['Ave. Rating']);
       unset($product['Product Citations']);
       try{
              $current_product = R::getRow( "SELECT * FROM " . $products_table . " WHERE catalog_id = " . $catalog_id );

              if( empty($current_product) ){
                     
                     $new_product = R::dispense( $products_table );
                     $new_product->name = $product['name'];
                     if(isset($product['clone'])){
                            $new_product->clone = $product['clone'];
                     }else{
                            $new_product->clone = NULL;
                     }
                     //$new_product->application = $product_item->application;
                     $new_product->regulation = $product['regulation'];
                     $new_product->catalog_id = $product['catalog_id'];
                     $new_product->link = $product['link'];
                     $new_product->size = $product['size'];
                     $new_product->price = str_replace('$','', $product['price']);
                     $new_product->inventory = $product['current_stock'];
                     //$hash = md5($product->clone . ' ' . $product->application . ' ' . $product->regulation . ' ' . $product->size . ' ' . $product->price);
                     $new_product->last_modified = date('Y-m-d H:i:s');
                     //$product->hash = $hash;
                     R::store($new_product); 
                     print 'Catalog ID Created: ' . $catalog_id . PHP_EOL;
                     //record_log_message('Catalog ID Created: ' . $catalog_id);
                     global $error_log_file;
                     //error_log("New catalog ID added " . $catalog_id . "\r\n", 3, $error_log_file);
              }else{ 

                     $isotype = 'N/A';
                     $inventory = 'N/A';
                     if(isset($product['isotype'])){
                            $isotype = $product['isotype'];
                     }
                     if(isset($product['current_stock'])){
                            $inventory = $product['current_stock'];
                     }                     
                     
                     //$isotype = $product['isotype'];
                     $last_modified = date('Y-m-d H:i:s');

                     print 'Catalog ID Updated: ' . $catalog_id . PHP_EOL;
                     //record_log_message('Catalog ID Updated: ' . $catalog_id);
                     R::exec("UPDATE " . $products_table . " SET isotype = '".$isotype."', inventory = '" . $inventory . "', last_modified = '".$last_modified."' WHERE catalog_id = '" . $catalog_id . "'" ); 
              }
        }catch (\Exception $e) {
              print $e->getMessage();
              
              global $error_log_file;

              $cdt = date("Y-m-d h:i:sa");
              $error_message = $e->getMessage() . $cdt .  "\r\n";
              //error_log($error_message, 3, $error_log_file);
              continue;
       }

}

foreach($data as $key=>$product){

       $link = $product['link'];
       $link_id = $product['link_id'];
       try{
              R::exec("UPDATE " . $links_table . " SET status = 'Done'  WHERE `id` = '" . $link_id . "'" );
              //record_log_message("Link ID status set to DONE: " . $link_id);
       }catch (\Exception $e) {
              print $e->getMessage();
       }

}


//record_log_message(date("Y-m-d h:i:s A") . " Get Links Details Completed");