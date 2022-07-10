<?php
    // This path should point to Composer's autoloader
    require 'vendor/autoload.php';
    require 'functions.php';

    // Create the database connection
    $client = new MongoDB\Client("mongodb://mongo:27017");

    // Test information
    // $debug = '{
    //     "message_id":"3a1fac0c-f960-4506-a60e-824979a74e74",
    //     "timestamp":"2022-01-01T13:04:30Z",
    //     "type":"Shop Order",
    //     "is_public":false,
    //     "from_name":"Jam",
    //     "message":"Good luck with the integration!",
    //     "amount":"5.00",
    //     "url":"https://ko-fi.com/Home/CoffeeShop?txid=0a1fac0c-f960-4506-a60e-824979a74e71",
    //     "email":"someone@example.com",
    //     "currency":"GBP",
    //     "is_subscription_payment":false,
    //     "is_first_subscription_payment":false,
    //     "kofi_transaction_id":"0a1fac0c-f960-4506-a60e-824979a74e71",
    //     "verification_token":"8cfeb5b0-3f94-4deb-8705-a5e13e10f4a1",
    //     "shop_items":[
    //         {"direct_link_code":"6139d6923b"}
    //     ],
    //     "tier_name":null
    // }';

    // Parse the JSON from the response
    parse_str(urldecode(file_get_contents("php://input")), $input);
    $webhook = json_decode($debug ?? $input['data'] ?? null);

    // Runs the webhook if the payload is present
    if ($webhook) {
        // Build an array of translations for Shop items to be used in the webhook
        $shop = array(
            '6139d6923b' => array(
                'label' => '1 Month of Bandit',
                'name' => 'Bandit',
                'months' => '+1 month',
            ),
        );

        // Grabs user id from Jellyfin if the Username exists
        $userID = userID($webhook->from_name);
        echo "User ID: " . $userID . "\n";

        if ($userID) {
            $currency = (new Currency\Util\CurrencySymbolUtil)::getSymbol($webhook->currency);
            $webhook_url = "https://discord.com/api/webhooks/937484287673008169/XMIJuAA2aK4he2eX_jIW9ZNYO0PwQoSk_tkJ13oyXsLL6KQl3Kf5oMiGDO8D18eBiGYs";

            switch ($webhook->type) {
                case "Subscription":
                    addTime($webhook->tier_name, '+1 month');

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
                    // Loop through all the items purchased and add the time to the user
                    foreach ($webhook->shop_items as $product) {
                        $productExists = $shop[$product->direct_link_code];

                        // If the product exists in the array, add the time to the user
                        if ($productExists) {
                            addTime($productExists['name'], $productExists['months']);

                            $webhook_title = "New Purchase Payment";
                            $webhook_description = "**{$webhook->from_name}** *({$userID})* has bought **{$productExists['label']}** *({$webhook->kofi_transaction_id})* for **{$currency}{$webhook->amount}**.";
                            $webhook_colour = "4ee51b";

                            sendEmbed($webhook_url, $webhook_title, $webhook_description, $webhook_colour);
                        }
                    }
                default:

            }
        }
    } else {
        // If the payload is not present act as the cronjob
        $users = $client->payments->users;

        // Loop through all the users in the database
        foreach ($users->find() as $user) {
            if (time() > $user['expiry']) {
                // If the user has expired, remove them from the database
                $users->deleteOne(['_id' => $user['_id']]);

                // Remove from the whitelist too
                $whitelist = $client->session_timer->whitelist;
                $whitelist->deleteOne(['UserId' => $user['userID']]);
            }
        }

        // Now we can send a message to the Discord channel to let people know that the cronjob has run
        $webhook_url = "https://discord.com/api/webhooks/995788111525187684/vkHdQMaUbdzA6o2VBzm6lxlz_ogIv1EiGsRjbf0_yKZFj6FNElFx9SQuBBpHrqufNCnM";
        $webhook_title = "Checking for Expired Users";
        $webhook_description = "The cronjob has run successfully.\n Any expired users have been removed from the databases.";
        $webhook_colour = "4ee51b";
        sendEmbed($webhook_url, $webhook_title, $webhook_description, $webhook_colour);
    }
?>