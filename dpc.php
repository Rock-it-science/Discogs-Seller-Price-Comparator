<html><body>
<?php
//Get installed dependancies from autoload
require '../vendor/autoload.php';

//Getting username passed from index page
$username = $_REQUEST['username'];

/*
New method: getting all JSON data at once to avoid trying to do thousands of requests that either get throttled or blocked
Or: Do all the requests once and save the data
*/

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

//Wantlist client
$wantClients = array();
array_push($wantClients, $client->getWantlist([
    'username' => $username,
    'page' => 1, //For example releases, use this page
    'per_page' => 250
]));
//Create a client for every page
//Find number of pages in wantList
$pages = $wantClients[0]['pagination']['pages'];
if($pages>2){
  for($p=2; $p<=$pages; $p++){//Iterate through every page
    array_push($wantClients, $client->getWantlist([
        'username' => $username,
        'page' => $p,
        'per_page' => 250
    ]));
  }
}

//Seller inventory client
/*$invResponse = $client->getInventory([
  'username' => $sellerArr[0],
  'sort' => 'item',
  'sort_order' => 'asc',
  'per_page' => 250, //For now, only first 50 for example
  'page' => 1 //Example releases are on this page
]);*/

$wantArray = array();
?>
<table>
 <tr>
   <th>Artist</th>
   <th>Title</th>
 </tr>
 <?php

for($p=0; $p<sizeof($wantClients); $p++){//Iterate through every client (page)
  foreach($wantClients[$p]['wants'] as &$item){//Iterating through items on page
    //Create array of title and artist
    $current = array();
    array_push($current, $item['basic_information']['artists'][0]['name']);
    array_push($current, $item['basic_information']['title']);
    //Check if it is already in array
    if(!in_array($current, $wantArray)){//If not already in array:
      //Add to wantArray
      array_push($wantArray, $current);
      //Display artist and name in table
      echo '<tr><td>' . $item['basic_information']['artists'][0]['name'] .'</td><td>' . $item['basic_information']['title'] . '</td></tr>';
    }
  }
}

//ricbra API only method (429 error), have to iterate through every page
/*
// Loop through results adding the ID of each one to array wantsArr
$wantsArr = array();

foreach ($wantResponse['wants'] as $result) {
    $wantsArr[] = $result['id'];
}

//Get median price of each release

//Get names of certain sellers that the buyer is interested in
//For now hard code 1 seller that doesn't have too many items
$sellerArr = array('bigschoolrecords');

//Compare items to find all items that seller is selling that are on the buyer's wantlist

$sellItemArr = array();
foreach($invResponse['listings'] as $item){
  $sellItemArr[] = $item['release']['id'];
}

//Check if each item in wantsArr is being sold by the seller
foreach($wantsArr as $wantItemID){
  if(in_array($wantItemID, $sellItemArr)){
    //Echo information about release from ID
    $wantRelease = $client->getRelease([
      'id' => $wantItemID
    ]);
    echo $wantRelease['artists'][0]['name'] . " - " . $wantRelease['title'] . ", ";
  }
}

//Currently echos seller's item page and release page, which have seperate IDs
//Need to figure out how to get release ID from seller's item page
*/
?>
</table>
</body></html>
