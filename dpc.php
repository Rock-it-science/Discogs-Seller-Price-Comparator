<?php
//Get installed dependancies from autoload
require '../php/vendor/autoload.php';

//Getting username passed from index page
$username = $_REQUEST["username"];

//Setting up client with my user agent
$client = Discogs\ClientFactory::factory([
  'defaults' => [
      'headers' => ['User-Agent' => 'Discogs-Seller-Price-Comparator/0.0 +https://github.com/Rock-it-science/Discogs-Seller-Price-Comparator'],
  ]
]);

$response = $client->getWantlist([
    'username' => $username
]);
// Pagination data
var_dump($response);

 ?>
