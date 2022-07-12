<?php
    date_default_timezone_set('Europe/London');
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    function isValidJSON($str) {
        json_decode($str);
        return json_last_error() == JSON_ERROR_NONE;
    }

    function userID($username = null) {
        $url = "https://jellyfin:8096/Users";

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
        global $client, $userID;

        // Select the database
        $whitelist = $client->session_timer->whitelist;

        // Check if the user is in the database
        $userExists = $whitelist->findOne([
            'UserId' => $userID,
        ]);

        if ($package == "Survivor") {
            $types = ["episode", "tvchannel"];
        } else {
            $types = ["episode"];
        }

        // If the user doesn't exist, add them to the database
        if (!$userExists) {
            $whitelist->insertOne([
                'UserId' => $userID,
                'MediaTypes' => $types,
            ]);
        } else {
            $whitelist->updateOne([
                'UserId' => $userID,
            ], [
                '$set' => [
                    'MediaTypes' => $types,
                ],
            ]);
        }
    }

    function addTime($package, $duration) {
        global $client, $userID, $webhook;

        // Needs to run first as it changes the database
        addLibrary($package);

        // Select the database
        $users = $client->payments->users;

        $userExists = $users->findOne([
            'userID' => $userID,
        ]);

        // If the user doesn't exist, create them
        if ($userExists) {
            // Check to see if the package in the database is the same as the package in the webhook
            if ($userExists['package'] == $package) {
                // Update the user's subscription date
                $users->updateOne([
                    'userID' => $userID,
                ], [
                    '$set' => [
                        'updated' => strtotime('now'),
                        'expiry' => strtotime($duration, $userExists['expiry']),
                    ],
                ]);
            } else {
                // Update the user's subscription date
                $users->updateOne([
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
            $users->insertOne([
                'userID' => $userID,
                'package' => $webhook->{'tier_name'},
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