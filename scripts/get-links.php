<?php

/**
 * get-links.php 
 * Retrieve all the links from the main search results
 * and create a JSON file with all the links found on the current page
 * */

require_once "../inc/config.php";

// Setup DOM Crawler
use Goutte\Client;

$client = new Client();

// Products displayed per page
$products_per_page = 100;
// Total number of pages with results based on the above number
$total_pages = 291;

$links_array = array();
$product_count = 0;
$base_url = BASE_URL;
$auxilary_info = array();
$products = [];
$cur_arr = [];

$error_log_file = create_log_file('get-urls');
record_log_message(date("Y-m-d h:i A") . " Get URLs");

for($i = 1; $i <= $total_pages; $i++){

    $url_to_fetch = $base_url . '/en-us/search-results?GroupID=&PageNum=' . $i .'&PageSize='.$products_per_page.'&altView=list';
    print $url_to_fetch . PHP_EOL;

    $crawler = $client->request('GET', $url_to_fetch);
    
    $catalog_id = "";
    $product_url = "";

    // Get Catalog ID
    $crawler->filter('ul#productsHolder ul')->each(function ($node) {
        $catalog_id_arr = array();
        $url_arr = array();
        $cur_arr = array();

        $num_rows = $node->filter('li.row')->count();
        $node->filter('li.row')->each(function ($nodex) {

            global $url_arr;
            global $base_url;
            global $cur_arr;
            global $cur_url;

            $url = trim($nodex->attr("href"));
            $url_arr[] = $base_url . $url;           

            if ( $nodex->filter('h2 a')->count() > 0 ) {
                $url_txt = $nodex->filter('h2 a');
                $url = $url_txt->attr("href");
                 global $cur_url;
                 $cur_url = $url;
            }else{
                global $cur_url;
                $url = $cur_url;
            }

           
            if ( $nodex->filter('form div.col-xs-3')->eq(0)->count() > 0 ) {
                $catalog_id_txt = $nodex->filter('form div.col-xs-3')->eq(0);
                $catalog_id = $catalog_id_txt->text();

                global $base_url;
                $cur_arr[] = array("catalog_id"=>$catalog_id,"url"=>$base_url.$url);
            }
        });

    });

    global $cur_arr;

    $products = $cur_arr;

        $filelocation = '../json-files/';
        $filename = $filelocation . 'page' . $i . '.json';

        $params_arr = array(
                "filename"=>$filename,
                "data"=>$products
            );
        create_json_file($params_arr);

        record_log_message('JSON file created : ' . $filename);

    unset($cur_arr);

}
record_log_message(date("Y-m-d h:i A") . " Biolegend Get URLs Completed");



