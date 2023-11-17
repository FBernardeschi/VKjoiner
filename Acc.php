<?php

class Account {
    private $login;
    private $password;
    private $token;
    public $user_id;
    public $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:108.0) Gecko/20100101 Firefox/108.0' . "\r\n";

    public function __construct(string $login, string $password) {
        $this->login = $login;
        $this->password = $password;

        $params = array(
            'grant_type'=>'password',
            'client_id'=>'2274003',
            'client_secret'=>'hHbZxrka2uZ6jB1inYsH',
            'username'=>$this->login,
            'password'=>$this->password,
            'scope'=>'offline',
            'v'=>'5.81',
            '2fa_supported'=>'1'
        );

        $options = array(
            'http'=>array(
                'method' => 'GET',
                'user_agent' => $this->userAgent
            )
        );

        $context = stream_context_create($options);

        $content = file_get_contents('http://oauth.vk.com/token?' . http_build_query($params), false, $context);
        $response = json_decode($content, true);

        $this->token = isset($response['access_token']) ? $response['access_token'] : '';
        $this->user_id = isset($response['user_id']) ? $response['user_id'] : '';
    }

    function getToken() {
        return $this->token;
    }

    function getUserId() {
        return $this->user_id;
    }

    function giftSender(string $user_id, string $gift_id, string $text) : string
    {
        $params = array(
            'user_ids' => $user_id,
            'gift_id' => $gift_id,
            'message' => $text,
            'guid' => rand(1000, 2000),
            'access_token' => $this->token,
            'v' => '5.81'
        );
        
        $request = file_get_contents('https://api.vk.com/method/gifts.send?' . http_build_query($params));
        return $request;
    }
}