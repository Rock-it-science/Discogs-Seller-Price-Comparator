<?php
require '../vendor/autoload.php';

ini_set('max_execution_time', 0);

//Passing seller name
$user = $_REQUEST["username"];

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
$checkTableQuery = $conn->query('SELECT 1 FROM '.$user.';');
if(!$checkTableQuery){//Table does not exist
  //Creating table for seller
  $conn->query('CREATE TABLE '.'username_'.$user.'(recordID INTEGER PRIMARY KEY)');

  //Setting up Discogs Client
  $client = Discogs\ClientFactory::factory([
    'defaults' => [
        'headers' => ['User-Agent' => 'Discogs-Seller-Price-Comparator/0.0 +https://github.com/Rock-it-science/Discogs-Seller-Price-Comparator'],
    ]
  ]);
  //Throttle client
  $client->getHttpClient()->getEmitter()->attach(new Discogs\Subscriber\ThrottleSubscriber());

  //Wantlist clients
  //Client for first page
  $wantClients = array();
  array_push($wantClients, $client->getWantlist([
      'username' => $user,
      'page' => 1, //For example releases, use this page
      'per_page' => 100
  ]));
  //Create a client for every page
  //Find number of pages in wantList
  $pages = $wantClients[0]['pagination']['pages'];
  if($pages>1){
    for($p=2; $p<=$pages; $p++){//Iterate through every page
      array_push($wantClients, $client->getWantlist([
          'username' => $user,
          'page' => $p,
          'per_page' => 100
      ]));
    }
  }
  foreach($wantClients as &$wantPage){
    foreach($wantPage['wants'] as &$item){//Iterate through items for sale on this page
      //Add to seller table
      $conn->query('INSERT INTO '.'username_'.$user.' VALUES ('.$item['id'].');');
    }
  }
  echo 'done';
  header('location: index.html');
}else{
  echo 'Table already exists';
  header('location: index.html');
}

 ?>
