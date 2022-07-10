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

    function addLibrary($userID, $types) {
        //The url you wish to send the POST request to
        $url = 'http://jellyfin-session-kicker:8887';

        //The headers we are going to use for the request
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_decode(utf8_encode("yDe0ypZAQCQ42Y1qkaHwgN7mbw0dgn1Wj1R38NwKHOK9d9MrfpgBQw")) // <---
        );

        //The data you want to send via POST
        $fields = array(
            'UserId' => $userID,
            'MediaTypes' => $types
        );

        //url-ify the data for the POST
        $fields_string = http_build_query($fields);
        echo $fields_string;

        //open connection
        $ch = curl_init($url);

        //set the url, number of POST vars, POST headers, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);

        //So that curl_exec returns the contents of the cURL; rather than echoing it
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //execute post
        $result = curl_exec($ch);
        echo($result);
        curl_close($ch);
    }

    function addTime($package, $duration) {
        global $collection, $userID, $webhook;

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