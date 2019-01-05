<?php
//Get installed dependancies from autoload
require '../bin/php/php7.2.10/vendor/autoload.php';

//Getting username passed from index page
$username = $_REQUEST["username"];

//Setting up client with my user agent
$client = Discogs\ClientFactory::factory([
  'defaults' => [
      'headers' => ['User-Agent' => 'Discogs-Seller-Price-Comparator/0.0 +https://github.com/Rock-it-science/Discogs-Seller-Price-Comparator'],
  ]
]);

$response = $client->search([
    'q' => 'Meagashira'
]);
// Loop through results
foreach ($response['results'] as $result) {
    var_dump($result['title']);
}
// Pagination data
var_dump($response['pagination']);

// Dump all data
var_dump($response->toArray());

 ?>
