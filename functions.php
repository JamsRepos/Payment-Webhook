<?php
    date_default_timezone_set('Europe/London');
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    function userID($username = null) {
        $url = "https://karna.ge/Users";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            "Accept: application/json",
            "X-Emby-Authorization: MediaBrowser Client=\"Payments\", DeviceId=\"Webhook\", Device=\"1\", Version=\"1.0\", Token=\"8b46dd46be6648fdb6422a3a2da8ca63\"",
        );

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $resp = curl_exec($curl);
        curl_close($curl);

        $json = json_decode($resp);
        foreach ($json as $item) {
            if ($item->Name == $username) {
                return $item->Id;
            }
        }
    }

    function addLibrary($package) {
        global $client, $collection, $userID;

        // Select the database
        $collection = $client->session_timer->whitelist;

        // Check if the user is in the database
        $userExists = $collection->findOne([
            'UserId' => $userID,
        ]);

        if ($package == "Survivor") {
            $types = ["episode", "tvchannel"];
        } else {
            $types = ["episode"];
        }

        // If the user doesn't exist, add them to the database
        if (!$userExists) {
            $collection->insertOne([
                'UserId' => $userID,
                'MediaTypes' => $types,
            ]);
        } else {
            $collection->updateOne([
                'UserId' => $userID,
            ], [
                '$set' => [
                    'MediaTypes' => $types,
                ],
            ]);
        }
    }

    function removeLibraries() {
        global $client, $collection, $userID;

        // Select the database
        $collection = $client->session_timer->whitelist;

        // Check if the user is in the database
        $userExists = $collection->findOne([
            'UserId' => $userID,
        ]);

        // If the user doesn't exist, add them to the database
        if ($userExists) {
            $collection->deleteOne([
                'UserId' => $userID,
            ]);
        }
    }

    function addTime($package, $duration) {
        global $client, $collection, $userID, $webhook;

        // Needs to run first as it changes the database
        addLibrary($package);

        // Select the database
        $collection = $client->payments->users;

        $userExists = $collection->findOne([
            'userID' => $userID,
        ]);

        // If the user doesn't exist, create them
        if ($userExists) {
            // Check to see if the package in the database is the same as the package in the webhook
            if ($userExists['package'] == $package) {
                // Update the user's subscription date
                $collection->updateOne([
                    'userID' => $userID,
                ], [
                    '$set' => [
                        'updated' => strtotime('now'),
                        'expiry' => strtotime($duration, $userExists['expiry']),
                    ],
                ]);
            } else {
                // Update the user's subscription date
                $collection->updateOne([
                    'userID' => $userID,
                ], [
                    '$set' => [
                        'package' => $package,
                        'updated' => strtotime('now'),
                        'expiry' => strtotime($duration),
                    ],
                ]);
            }
        } else {
            // Insert the user into the database
            $collection->insertOne([
                'userID' => $userID,
                'package' => $webhook->tier_name,
                'updated' => strtotime('now'),
                'expiry' => strtotime($duration),
            ]);
        }
    }

    function sendEmbed($webhook_url, $webhook_title, $webhook_description, $webhook_colour) {
        $message = (new Woeler\DiscordPhp\Message\DiscordEmbedMessage())
        ->setTitle($webhook_title)
        ->setDescription($webhook_description)
        ->setTimestamp(new DateTime())
        ->setThumbnail('https://i.imgur.com/d5SBQ6v.png')
        ->setColorWithHexValue($webhook_colour);

        $webhook = new Woeler\DiscordPhp\Webhook\DiscordWebhook($webhook_url);
        $messageData = $webhook->send($message);
    }
?>