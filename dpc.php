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
    //Seller
    echo "<td>" . "</td>";
    //Price
    echo "<td>" . "</td>";
    //End table row
    echo "</tr>";
}

 ?>
</table>
</body></html>
