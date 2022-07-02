<?php
    $debug = 'data: {
        "message_id":"3a1fac0c-f960-4506-a60e-824979a74e74",
        "timestamp":"2022-08-21T13:04:30Z",
        "type":"Subscription",
        "is_public":false,
        "from_name":"Ko-fi Team",
        "message":"Good luck with the integration!",
        "amount":"3.00",
        "url":"https://ko-fi.com/Home/CoffeeShop?txid=0a1fac0c-f960-4506-a60e-824979a74e71",
        "email":"someone@example.com",
        "currency":"USD",
        "is_subscription_payment":true,
        "is_first_subscription_payment":true,
        "kofi_transaction_id":"0a1fac0c-f960-4506-a60e-824979a74e71",
        "verification_token":"8cfeb5b0-3f94-4deb-8705-a5e13e10f4a1",
        "shop_items":null,
        "tier_name":null}';


    parse_str(urldecode($debug ?? file_get_contents("php://input")), $input);
    $webhook = json_decode($input['data']);
    var_dump($webhook->message_id);

    echo "test";

?>