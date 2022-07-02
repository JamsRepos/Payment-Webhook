<?php
    date_default_timezone_set('Europe/London');

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
        $url = 'http://jellyfin-session-kicker:8887';

        $postdata = http_build_query(
            array(
                'UserId' => $userID,
                'MediaTypes' => $types
            )
        );

        $opts = array('http' =>
            array(
                'method' => 'POST',
                'header' => array(
                    'Content-type: application/x-www-form-urlencoded',
                    'Authorization: Basic yDe0ypZAQCQ42Y1qkaHwgN7mbw0dgn1Wj1R38NwKHOK9d9MrfpgBQw',
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36'
                ),
                'content' => $postdata
            )
        );
        $context = stream_context_create($opts);
        $result = file_get_contents($url, false, $context);
        echo $result;

        var_dump($result);
    }

    function addTime($package, $duration) {
        global $client, $collection, $userID, $webhook;

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