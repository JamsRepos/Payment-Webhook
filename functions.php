<?php
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
        //for debug only!
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $resp = curl_exec($curl);
        curl_close($curl);

        $json = json_decode($resp);
        foreach ($json as $item) {
            if ($item->Name == $username) {
                return $item->Id;
            }
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