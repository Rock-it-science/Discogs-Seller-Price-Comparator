<html><body>
<table>
  <tr>
    <th>Artist</th>
    <th>Release Title</th>
    <th>Record info</th>
    <th>Seller</th>
    <th>Price</th>
  </tr>
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
// Loop through results
foreach ($response['wants'] as $result) {
    //New table row
    echo "<tr>";
    //Echo artist name
    echo "<td>" . $result['basic_information']['artists'][0]['name'] . "</td>";
    //Echo release title
    echo "<td>" . $result['basic_information']['title'] . "</td>";
    //Record info
    echo "<td>";
    foreach($result['basic_information']['formats'][0]['descriptions'] as $info){
      echo $info . " ";
    }
    //Print more info if it exists
    if(isset($result['basic_information']['formats'][0]['text'])){
      echo $result['basic_information']['formats'][0]['text'];
    }
    echo "</td>";
    //Get release info
    $rId = $result['id'];
    $release = $client->getRelease([
    'id' => $rId;
    ]);
    //Median release price
    echo "<td>" . $release['lowest_price'] . "</td>";
    //Seller and link
    echo "<td>" . "</td>";
    //End table row
    echo "</tr>";
}

//Make a list of all items on want list
//Get names of certain sellers that the buyer is interested in
//Compare items to find all items that seller is selling that are on the buyer's wantlist
//Output prices compared to median prices in table

 ?>
</table>
</body></html>
