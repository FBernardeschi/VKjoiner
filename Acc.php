<?php

class Account {
    public function __construct($login, $password) {
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

        $content = file_get_contents('http://oauth.vk.com/token?' . http_build_query($params));
        $response = json_decode($content);

        $this->token = $response->access_token;
        $this->user_id = $response->user_id;
    }

    function getToken() {
        return $this->token;
    }

    function getUserId() {
        return $this->user_id;
    }
}