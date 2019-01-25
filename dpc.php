<html><body>
<?php
//Get installed dependancies from autoload
require '../vendor/autoload.php';

//Getting username passed from index page
$username = $_REQUEST["username"];

//Setting up client with my user agent
$client = Discogs\ClientFactory::factory([
  'defaults' => [
      'headers' => ['User-Agent' => 'Discogs-Seller-Price-Comparator/0.0 +https://github.com/Rock-it-science/Discogs-Seller-Price-Comparator'],
  ]
]);
//Throttle client
$client->getHttpClient()->getEmitter()->attach(new Discogs\Subscriber\ThrottleSubscriber());

$wantResponse = $client->getWantlist([
    'username' => $username,
    'page' => 3, //For example releases, use this page
    'per_page' => 250
]);
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
//First get all items from seller's inventory
$invResponse = $client->getInventory([
  'username' => $sellerArr[0],
  'sort' => 'item',
  'sort_order' => 'asc',
  'per_page' => 250, //For now, only first 50 for example
  'page' => 1 //Example releases are on this page
]);
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

 ?>
<!--<table>
  <tr>
    <th>Artist</th>
    <th>Release Title</th>
    <th>Seller</th>
    <th>Price</th>
  </tr>-->
  <?php
  //Output prices compared to median prices in table
   ?>
</table>
</body></html>
