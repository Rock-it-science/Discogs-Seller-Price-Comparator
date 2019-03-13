<html><body>
<?php

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

//Get installed dependancies from autoload
require '../vendor/autoload.php';

//Setting infinite execution time
ini_set('max_execution_time', 0);

//Getting username passed from index page
$username = $_REQUEST['username'];

//Get sellers from SQL tables
$sellers = array();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "discogs";
$conn = new mysqli($servername, $username, $password, $dbname);
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

//Wantlist clients
//Client for first page
$wantClients = array();
array_push($wantClients, $client->getWantlist([
    'username' => $username,
    'page' => 1, //For example releases, use this page
    'per_page' => 250
]));
//Create a client for every page
//Find number of pages in wantList
$pages = $wantClients[0]['pagination']['pages'];
if($pages>1){
  for($p=2; $p<=$pages; $p++){//Iterate through every page
    array_push($wantClients, $client->getWantlist([
        'username' => $username,
        'page' => $p,
        'per_page' => 250
    ]));
  }
}

// Array of release IDs from every item in wantlist
$wantArray = array();
/*?>
<table>
 <tr>
   <th>Artist</th>
   <th>Title</th>
   <th>Release ID</th>
 </tr>
 <?php*/

foreach($wantClients as &$wantPage){//Iterate through every client (page)
  foreach($wantPage['wants'] as &$item){//Iterating through items on page
    //Search in sellers tables for each item
    $itemQuery = $conn->query('SELECT 1 FROM '.$sellers[0]['Tables_in_discogs'].' WHERE recordID='.$item['id'].';');
    if($itemQuery != null){
      echo $item['basic_information']['title'] . ' ' . $item['basic_information']['artists'][0]['name'] .', ';
    }
  }
}

?>
</table>
</body></html>
