<?php

/**
 * This is not an exhaustive test.
 *
 * The purpose of the smoketest is to quickly identify if something has broken
 * integration with the TaxCloud API. The smoketest goes through the process
 * required by TaxCloud and actually makes connections to TaxCloud.
 */

// API credentials loaded from environment variables.
$apiLoginID = $_ENV["TaxCloud_apiLoginID"];
$apiKey = $_ENV["TaxCloud_apiKey"];
$uspsUserID = $_ENV["TaxCloud_uspsUserID"];

// Some variable that need to be unique, but can't change.
$orderID = rand();
$cartID = rand(1, 999);

/**
 * Show us what step we are on.
 */
function step($message) {
  global $STEPCOUNTER;
  printf("\nStep %d. %s\n", $STEPCOUNTER++, sprintf($message));
}

require_once 'lib/php-taxcloud.php';

$client = new \TaxCloud\Client();

step('Ping');
$pingParams = new \TaxCloud\Request\Ping($apiLoginID, $apiKey);

try {
  $client->Ping($pingParams);
} catch (Exception $e) {
  echo 'Caught exception: ', $e->getMessage(), "\n";
}

step('Get TICs');

$params = new \TaxCloud\Request\GetTICs($apiLoginID, $apiKey);
try {
  $client->GetTICs($params);
} catch (Exception $e) {
  echo 'Caught exception: ', $e->getMessage(), "\n";
}

step('GetTICGroups');

$params = new \TaxCloud\Request\GetTICGroups($apiLoginID, $apiKey);
try {
  $client->getTICGroups($params);
} catch (Exception $e) {
  echo 'Caught exception: ', $e->getMessage(), "\n";
}

step('Get TICs By Group');

$params = new \TaxCloud\Request\GetTICsByGroup($apiLoginID, $apiKey, 10000);
print_r($client->GetTICsByGroup($params));

step('Cart Item');
$cartItems = array();
$cartItem = new \TaxCloud\CartItem($cartID + 1, 'ABC123', '00000', 12.00, 1);
$cartItems[] = $cartItem;
print_r($cartItem);

step('Cart Item - Shipping');

$cartItemShipping = new \TaxCloud\CartItem($cartID + 2, 'SHIPPING123', 11010, 8.95, 1);
$cartItems[] = $cartItemShipping;
print_r($cartItemShipping);

step('Cart Items Array');

print_r($cartItems);

step('Verify Address');
$address = new \TaxCloud\Address(
  '1600 Pennsylvania Ave NW',
  '',
  'Washington',
  'DC',
  // Intentionally wrong zip
  '20006',
  '1234'
);

$verifyAddress = new \TaxCloud\Request\VerifyAddress($uspsUserID, $address);

$verifiedAddress = $client->VerifyAddress($verifyAddress);
print_r($verifiedAddress);

step('Lookup');

print_r($address);

$originAddress = new \TaxCloud\Address(
  $verifiedAddress->getAddress1(),
  $verifiedAddress->getAddress2(),
  $verifiedAddress->getCity(),
  $verifiedAddress->getState(),
  $verifiedAddress->getZip5(),
  $verifiedAddress->getZip4()
);

$destAddress = new \TaxCloud\Address(
  'PO Box 573',
  '',
  'Clinton',
  'OK',
  '73601',
  ''
);

print_r($destAddress);

$lookup = new \TaxCloud\Request\Lookup($apiLoginID, $apiKey, '123', $cartID, $cartItems, $originAddress, $destAddress);
print_r($client->Lookup($lookup));

step('Authorized');

$authorization = new \TaxCloud\Request\Authorized($apiLoginID, $apiKey, '123', $cartID, $cartItems, $orderID, date("c"));
print_r($client->Authorized($authorization));

step('Captured');
$capture = new \TaxCloud\Request\Captured($apiLoginID, $apiKey, $orderID);
print_r($client->Captured($capture));

step('Authorized With Capture');
$lookup = new \TaxCloud\Request\Lookup($apiLoginID, $apiKey, '123', $cartID + 1, $cartItems, $originAddress, $destAddress);
$client->Lookup($lookup);
$authcap = new \TaxCloud\Request\AuthorizedWithCapture($apiLoginID, $apiKey, '123', $cartID + 1, $orderID + 1, date("c"), date("c"));
print_r($client->AuthorizedWithCapture($authcap));

step('Returned');
$return = new \TaxCloud\Request\Returned($apiLoginID, $apiKey, $orderID + 1, $cartItems, date("c"));
print_r($client->Returned($return));
