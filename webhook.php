<?php
    // This path should point to Composer's autoloader
    require 'vendor/autoload.php';
    require 'functions.php';

    // Create the database connection
    $client = new MongoDB\Client("mongodb://mongo:27017");

    // Check to see if it is a staff ran command
    $staffID = $_GET['staffDiscordID'] ?? null;
    $targetName = $_GET['targetKarnageName'] ?? null;
    $targetLength = $_GET['targetTimeLength'] ?? null;
    $targetType = $_GET['targetTierPackage'] ?? null;

    if ($staffID && $targetName && $targetLength && $targetType) {
        $manual = array(
            'type' => 'Staff Command',
            'from_name' => $targetName,
            'duration' => $targetLength,
            'tier_name' => $targetType,
            'currency' => 'GBP',
        );
    } else {
        $manual = null;
    }
    // Parse the JSON from the response
    parse_str(urldecode(file_get_contents("php://input")), $input);
    $public = $input["data"] ?? null;
    $private = $input["readToken"] ?? null;

    if ($private) {
        // Check if the response has a key
        $result = $public . "&readToken=" .  $private;
    } else {
        // If the response doesn't have a key, resume as normal
        $result = $public;
    }

    $webhook = json_decode(json_encode($manual)) ?? json_decode($result) ?? null;

    // Runs the webhook if the payload is present
    if ($webhook) {
        // General logging
        echo "Webhook Received!\nPayload: " . $result . "\n\n";

        // Build an array of translations for Shop items to be used in the webhook
        $shop = array(
            'd4b1a8a85f' => array(
                'label' => '1 Month of Bandit',
                'name' => 'Bandit',
                'duration' => '+1 month',
            ),
            'f446efa005' => array(
                'label' => '3 Months of Bandit',
                'name' => 'Bandit',
                'duration' => '+3 months',
            ),
            '154b2e4b84' => array(
                'label' => '6 Months of Bandit',
                'name' => 'Bandit',
                'duration' => '+6 months',
            ),
            '63da93503d' => array(
                'label' => '1 Year of Bandit',
                'name' => 'Bandit',
                'duration' => '+1 year',
            ),
            '6139d6923b' => array(
                'label' => '1 Month of Survivor',
                'name' => 'Survivor',
                'duration' => '+1 month',
            ),
            'ea1468c715' => array(
                'label' => '3 Months of Survivor',
                'name' => 'Survivor',
                'duration' => '+3 months',
            ),
            '317683bd2a' => array(
                'label' => '6 Months of Survivor',
                'name' => 'Survivor',
                'duration' => '+6 months',
            ),
            'e67da1cfd0' => array(
                'label' => '1 Year of Survivor',
                'name' => 'Survivor',
                'duration' => '+1 year',
            ),
        );

        // Grabs user id from Jellyfin if the Username exists
        $userID = userID($webhook->{'from_name'}) ?? null;

        $webhook_url = "https://discord.com/api/webhooks/937484287673008169/XMIJuAA2aK4he2eX_jIW9ZNYO0PwQoSk_tkJ13oyXsLL6KQl3Kf5oMiGDO8D18eBiGYs";
        $currency = (new Currency\Util\CurrencySymbolUtil)::getSymbol(strtoupper($webhook->{'currency'}));

        if ($userID) {
            switch ($webhook->{'type'}) {
                case "Subscription":
                    addTime($webhook->{'tier_name'}, '+1 month');

                    if ($webhook->{'is_first_subscription_payment'}) {
                        $webhook_title = "New Subscription Payment";
                        $webhook_description = "**{$webhook->{'from_name'}}** *({$userID})* subscribed to **{$webhook->{'tier_name'}}** *({$webhook->{'kofi_transaction_id'}})* for **{$currency}{$webhook->{'amount'}}**.";
                        $webhook_colour = "4ee51b";
                    } else {
                        $webhook_title = "Ongoing Subscription Payment";
                        $webhook_description = "**{$webhook->{'from_name'}}** *({$userID})* renewed their subscription to **{$webhook->{'tier_name'}}** *({$webhook->{'kofi_transaction_id'}})* for **{$currency}{$webhook->{'amount'}}**.";
                        $webhook_colour = "F28C28";
                    }

                    sendEmbed($webhook_url, $webhook_title, $webhook_description, $webhook_colour);

                    return;
                case "Shop Order":
                    // Loop through all the items purchased and add the time to the user
                    foreach ($webhook->{'shop_items'} as $product) {
                        $productExists = $shop[$product->direct_link_code];

                        // If the product exists in the array, add the time to the user
                        if ($productExists) {
                            addTime($productExists['name'], $productExists['duration']);

                            $webhook_title = "New Purchase Payment";
                            $webhook_description = "**{$webhook->{'from_name'}}** *({$userID})* has bought **{$productExists['label']}** *({$webhook->{'kofi_transaction_id'}})* for **{$currency}{$webhook->{'amount'}}**.";
                            $webhook_colour = "4ee51b";

                            sendEmbed($webhook_url, $webhook_title, $webhook_description, $webhook_colour);
                        }
                    }

                    return;
                case "Staff Command":
                    // If the payment came from the bot
                    addTime($webhook->{'tier_name'}, $webhook->{'duration'});

                    $webhook_title = "Manual Staff Assignment";
                    $webhook_description = "<@{$staffID}> gave **{$webhook->{'from_name'}}** *({$userID})* the product **{$webhook->{'tier_name'}}** for **{$webhook->{'duration'}}**.";
                    $webhook_colour = "F28C28";

                    sendEmbed($webhook_url, $webhook_title, $webhook_description, $webhook_colour);

                    return;
            }
        } else {
            // Let us know that the user does not exist and to contact them.
            $webhook_title = "Error - User Not Found";
            $webhook_description = "**{$webhook->{'from_name'}}** *({$webhook->{'email'}})* has just purchased an item *({$webhook->{'kofi_transaction_id'}})* but does not exist on Karnage.\nPlease contact them for manual approval.";
            $webhook_colour = "FF0000";
            sendEmbed($webhook_url, $webhook_title, $webhook_description, $webhook_colour);

            return;
        }
    } else {
        // General Logging
        echo "Webhook Not Received\nContinung to run a expiry check\n\n";

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

        return;
    }
?>
