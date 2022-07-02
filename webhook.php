<?php
    // This path should point to Composer's autoloader
    require 'vendor/autoload.php';
    require 'functions.php';

    // Test information
    $debug = '{
        "message_id":"3a1fac0c-f960-4506-a60e-824979a74e74",
        "timestamp":"2022-01-01T13:04:30Z",
        "type":"Subscription",
        "is_public":false,
        "from_name":"Jam",
        "message":"Good luck with the integration!",
        "amount":"5.00",
        "url":"https://ko-fi.com/Home/CoffeeShop?txid=0a1fac0c-f960-4506-a60e-824979a74e71",
        "email":"someone@example.com",
        "currency":"GBP",
        "is_subscription_payment":true,
        "is_first_subscription_payment":true,
        "kofi_transaction_id":"0a1fac0c-f960-4506-a60e-824979a74e71",
        "verification_token":"8cfeb5b0-3f94-4deb-8705-a5e13e10f4a1",
        "shop_items":null,
        "tier_name":"Bandit"
    }';

    // Parse the JSON from the response
    parse_str(urldecode(file_get_contents("php://input")), $input);
    $webhook = json_decode($debug ?? $input['data']);

    // Create the database connection
    $client = new MongoDB\Client("mongodb://localhost:27017");

    // Select the database
    $collection = $client->payments->users;

    // Grabs user id from Jellyfin if the Username exists
    $userID = userID($webhook->from_name);

    if ($userID) {
        switch ($webhook->type) {
            case "Subscription":
                $doesExist = $collection->findOne([
                    'userID' => $userID,
                ]);

                // If the user doesn't exist, create them
                if ($doesExist) {
                    // Check to see if the package in the database is the same as the package in the webhook
                    if ($doesExist['package'] == $webhook->tier_name) {
                        // Update the user's subscription date
                        $collection->updateOne([
                            'userID' => $userID,
                        ], [
                            '$set' => [
                                'updated' => strtotime('now'),
                                'expiry' => strtotime('+1 month', $doesExist['expiry']),
                            ],
                        ]);
                    } else {
                        // Update the user's subscription date
                        $collection->updateOne([
                            'userID' => $userID,
                        ], [
                            '$set' => [
                                'package' => $webhook->tier_name,
                                'updated' => strtotime('now'),
                                'expiry' => strtotime('+1 month'),
                            ],
                        ]);
                    }
                } else {
                    // Insert the user into the database
                    $collection->insertOne([
                        'userID' => $userID,
                        'package' => $webhook->tier_name,
                        'updated' => strtotime('now'),
                        'expiry' => strtotime('+1 month'),
                    ]);
                }

                $currency = (new Currency\Util\CurrencySymbolUtil)::getSymbol($webhook->currency);
                $webhook_url = "https://discord.com/api/webhooks/934490584264106095/OHHdlFDF0US2pPdDYR8X1qXJF5KDK5KhKoh-EYxpBQXJRqcaJ-78SXEa2sBQsg86kyeF";

                if ($webhook->is_first_subscription_payment) {
                    $webhook_title = "New Subscription Payment";
                    $webhook_description = "**{$webhook->from_name}** *({$userID})* subscribed to **{$webhook->tier_name}** *({$webhook->kofi_transaction_id})* for **{$currency}{$webhook->amount}**.";
                    $webhook_colour = "4ee51b";
                } else {
                    $webhook_title = "Ongoing Subscription Payment";
                    $webhook_description = "**{$webhook->from_name}** *({$userID})* renewed their subscription to **{$webhook->tier_name}** *({$webhook->kofi_transaction_id})* for **{$currency}{$webhook->amount}**.";
                    $webhook_colour = "F28C28";
                }

                sendEmbed($webhook_url, $webhook_title, $webhook_description, $webhook_colour);
            case "Shop Order":

            default:

        }
    }
?>