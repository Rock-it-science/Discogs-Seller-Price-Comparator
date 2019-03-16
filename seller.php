<?php
require '../vendor/autoload.php';

ini_set('max_execution_time', 0);

//Passing seller name
$seller = $_REQUEST["seller"];

//SQL stuff
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "discogs";
$conn = new mysqli($servername, $username, $password, $dbname);
if($conn->connect_error){
  die("Connection failed: ". $conn->connect_error);
}

//Check if table with seller's name already exists
$checkTableQuery = $conn->query('SELECT 1 FROM '.$seller.';');
if(!$checkTableQuery){//Table does not exist
  //Creating table for seller
  $conn->query('CREATE TABLE '.'seller_'.$seller.'(recordID INTEGER PRIMARY KEY)');

  //Setting up Discogs Client
  $client = Discogs\ClientFactory::factory([
    'defaults' => [
        'headers' => ['User-Agent' => 'Discogs-Seller-Price-Comparator/0.0 +https://github.com/Rock-it-science/Discogs-Seller-Price-Comparator'],
    ]
  ]);
  //Throttle client
  $client->getHttpClient()->getEmitter()->attach(new Discogs\Subscriber\ThrottleSubscriber());

  //Iterating through seller's items and adding them to table
  $sellClients = array();
  //Seller inventory client (first client is just used to get number of pages)
  $sellClients[0] = $client->getInventory([
    'username' => $seller,
    'sort' => 'item',
    'sort_order' => 'asc',
    'per_page' => 100,
    'page' => 1
  ]);
  //Create a client for every page
  //First find number of pages in inventory
  $sellPages = $sellClients[0]['pagination']['pages'];
  if($sellPages>1){
    for($p=2; $p<=$sellPages; $p++){//Iterate through every page **Set max to 5 for testing**
      array_push($sellClients, $client->getInventory([
          'username' => $seller,
          'sort' => 'item',
          'sort_order' => 'asc',
          'per_page' => 100,
          'page' => $p,
      ]));
    }
  }
  foreach($sellClients as &$sellPage){
    foreach($sellPage['listings'] as &$forSale){//Iterate through items for sale on this page
      //Add to seller table
      $conn->query('INSERT INTO '.'seller_'.$seller.' VALUES ('.$forSale['release']['id'].');');
    }
  }
  echo 'done';
  header('location: index.html');
}else{
  echo 'Table already exists';
  header('location: index.html');
}

 ?>
