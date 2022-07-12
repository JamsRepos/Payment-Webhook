<?php
$url = "http://86.180.181.114:3000/webhook.php";

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$headers = array(
   "Accept: application/json",
   "Content-Type: application/json",
);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

$data = "data=%7b%22message_id%22%3a%2252d862d9-6d69-4aee-90ec-d641fe1cbb30%22%2c%22timestamp%22%3a%222022-07-12T17%3a15%3a27Z%22%2c%22type%22%3a%22Shop+Order%22%2c%22is_public%22%3atrue%2c%22from_name%22%3a%22Venom%22%2c%22message%22%3a%22%22%2c%22amount%22%3a%225.00%22%2c%22url%22%3a%22https%3a%2f%2fko-fi.com%2fHome%2fCoffeeShop%3ftxid%3d7796ee3d-3f2c-480e-877e-3051c1809a76%26readToken%3dfa303460-4df1-4d19-9702-fc9bdfc3514e%22%2c%22email%22%3a%22lubricantjam%40pm.me%22%2c%22currency%22%3a%22gbp%22%2c%22is_subscription_payment%22%3afalse%2c%22is_first_subscription_payment%22%3afalse%2c%22kofi_transaction_id%22%3a%227796ee3d-3f2c-480e-877e-3051c1809a76%22%2c%22verification_token%22%3a%228cfeb5b0-3f94-4deb-8705-a5e13e10f4a1%22%2c%22shop_items%22%3a%5b%7b%22direct_link_code%22%3a%226139d6923b%22%7d%5d%2c%22tier_name%22%3anull%7d";

curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

$resp = curl_exec($curl);
curl_close($curl);

echo $resp;
?>