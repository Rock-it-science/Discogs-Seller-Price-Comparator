<html><body>
<?php
//Get installed dependancies from autoload
require '../vendor/autoload.php';

//Getting username passed from index page
$username = $_REQUEST['username'];

//Setting wantlist URL
$wantURL = 'https://api.discogs.com/users/Rock_it_science/wants';
//Setting seller inventory URL
$sellerURL = 'https://api.discogs.com/users/bigschoolrecords/inventory?sort=item&sort_order=asc';

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
?>
<table>
 <tr>
   <th>Artist</th>
   <th>Title</th>
   <th>Release ID</th>
 </tr>
 <?php

for($p=0; $p<sizeof($wantClients); $p++){//Iterate through every client (page)
  foreach($wantClients[$p]['wants'] as &$item){//Iterating through items on page
    //Add to wantArray
    array_push($wantArray, $item['id']);
    //Display artist and name in table
    //echo '<tr><td>' . $item['basic_information']['artists'][0]['name'] .'</td><td>' . $item['basic_information']['title'] . '</td><td>' . $item['id']. '</td></tr>';
  }
}
//Seller inventory client
$sellClient = $client->getInventory([
  'username' => 'discoclubmate', //Example seller name
  'sort' => 'item',
  'sort_order' => 'asc',
  'per_page' => 250, //For now, only first 50 for example
  'page' => 1 //Example releases are on this page
]);

//Array of items for sale by seller (for now only items from first page)
$sellWant = array();
foreach($sellClient['listings'] as &$forSale){//Iterate through items for sale on this page
  if(in_array($forSale['release']['id'], $wantArray)){//Check if for-sale item is in want-array
    //Add to sellwant array
    array_push($sellWant, $forSale['release']['id']);
    //Show in table
    echo '<tr><td>' . $forSale['release']['artist'] .'</td><td>' . $forSale['release']['title'] . '</td><td>' . $forSale['id']. '</td></tr>';
  }
}


?>
</table>
</body></html>
