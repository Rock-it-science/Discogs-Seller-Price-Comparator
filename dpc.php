<?php
//Get installed dependancies from autoload
require '../vendor/autoload.php';
/*
What I want this program to do: Given a username and some sellers, find who is selling the most items
  on your wantlist for the lowest prices.
How to find items from a seller's inventory that are also in your wantlist:
  Brute force ID-matching: Make an array of release IDs from user's wantlist, and compare it to all release ID's
  from the seller's inventory.
   - Too slow, and too many requests

   Smart Title matching: Attach title of each release to the wantArray, and when searching for items in seller's
   inventory, look at the first character from the first and last items on each page. If there is a release in the
   wantArray with a title that's first character is between those characters, then binary search the page, otherwise
   skip it.
   - Faster, but might not cut down number of requests

   Local searching: Put everything in local arrays, then binary search
   - Far fewer requests, can pair with smart title matching

   *New Idea
   - Given list of things I want, find seller who has the most of them
   - Button to analyze sellers, permanently store data of what they are selling
*/

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

//Load all wantlist items into array
$wantlist = array();
$wantQuery = $conn->query('SELECT * FROM Rock_it_science');//TODO make this not hard-coded
while($row = $wantQuery->fetch_assoc()){
  array_push($wantlist, $row['recordID']);
}

foreach($wantlist as &$item){//Iterate through all items in wantlist
  //Search in sellers tables for each item
  $itemQuery = $conn->query('SELECT * FROM sweet_baby_angel WHERE recordID='.$item.';');//TODO make this also not hard-coded
  if(mysqli_num_rows($itemQuery) > 0){
    echo $item .', ';
  }
}

?>
