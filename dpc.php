<?php
//Get installed dependancies from autoload
require '../vendor/autoload.php';

//Setting infinite execution time
ini_set('max_execution_time', 0);

//Get sellers from SQL tables
$sellers = array();
$servername = "localhost";
$serverUsername = "root";
$password = "";
$dbname = "discogs";
$conn = new mysqli($servername, $serverUsername, $password, $dbname);
if($conn->connect_error){
  die("Connection failed: ". $conn->connect_error);
}
$sellersQuery = $conn->query('SHOW TABLES;');
if($row = $sellersQuery->fetch_assoc()){
  array_push($sellers, $row);
}

//Setting up client with my user agent
$client = Discogs\ClientFactory::factory([
  'defaults' => [
      'headers' => ['User-Agent' => 'Discogs-Seller-Price-Comparator/0.0 +https://github.com/Rock-it-science/Discogs-Seller-Price-Comparator'],
  ]
]);
//Throttle client
$client->getHttpClient()->getEmitter()->attach(new Discogs\Subscriber\ThrottleSubscriber());

//Find name of user table
$userQuery = $conn->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.tables WHERE TABLE_NAME LIKE 'username_%';");
while($row = $userQuery->fetch_assoc()){
  $username = $row['TABLE_NAME'];
}

//Find names of seller tables
$sellerNamesQuery = $conn->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.tables WHERE TABLE_NAME LIKE 'seller_%'");
$sellerNames = array();
while($row = $sellerNamesQuery->fetch_assoc()){
  array_push($sellerNames, $row['TABLE_NAME']);
}

//Load all wantlist items into array
$wantlist = array();
$wantQuery = $conn->query('SELECT * FROM '.$username);
while($row = $wantQuery->fetch_assoc()){
  array_push($wantlist, $row['recordID']);
}

foreach($wantlist as &$item){//Iterate through all items in wantlist
  foreach($sellerNames as &$seller){//Iterate through all sellers in sellerNames
    //Search in sellers tables for each item
    $itemQuery = $conn->query('SELECT * FROM '.$seller.' WHERE recordID='.$item.';');//TODO iterate through all sellers
    if(mysqli_num_rows($itemQuery) > 0){//Item match is found
      //Lookup item from releaseId to get title and artist
      $release = $client->getRelease([
        'id' => $item
      ]);
      echo $release['artists_sort'] . ' - ' . $release['title'] . ', ';
    }
  }
}

?>
