<?php
session_start();
ini_set('max_execution_time', 60000);
$_SESSION['account'] = (isset($_SESSION['account'])) ? $_SESSION['account'] : '';
$_SESSION['accounts'] = (isset($_SESSION['accounts'])) ? $_SESSION['accounts'] : '';

require_once __DIR__ . '/vendor/autoload.php';
require_once 'Acc.php';

$loader = new Twig\Loader\FilesystemLoader('templates');
$twig = new Twig\Environment($loader);

$result = array();
$newLoginPass = '';
$ids_for_gifts = file_get_contents('ids.txt');
$_SESSION['ids'] = (strlen($ids_for_gifts) > 1) ? $ids_for_gifts : '';


if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['joinGroups'])) {
        $account = $_POST['account'];
        $arrayLoginPass = explode(':', $account);

        $_SESSION['account'] = $account;

        $acc = new Account($arrayLoginPass[0], $arrayLoginPass[1]);
        
        $params = array(
            'access_token'=>$acc->getToken(),
            'v'=>'5.81'
        );

        $groups = $_POST['groups'];

        $exeption = array(
            'club',
            'https://vk.com/club',
            'vk.com/club'
        );

        $options  = array('http' => array('method' => 'GET', 'user_agent' => $acc->userAgent));
        $context  = stream_context_create($options);
    
        foreach(explode("\n", $groups) as $group) {
            $group = str_replace($exeption , '', $group);
            $params['group_id'] = $group;
            $request = file_get_contents('http://api.vk.com/method/groups.join?' . http_build_query($params), false, $context);
            $response = json_decode($request, true);
            if(isset($response['error'])) {
                $result['club' . $group] = $response['error']['error_msg'];
            } else {
                $result['club' . $group] = 'Успешно!';
            }
            sleep(3);
        };
    };

    if(isset($_POST['comments'])) {
        $wall = $_POST['wall'];
        $accounts = $_POST['accs'];
        $text = $_POST['text'];

        // Если пришёл акк, сохраняем его в сессию
        $_SESSION['accounts'] = $accounts;

        $exeption = array(
            'https://vk.com/wall',
            'vk.com/wall',
            'wall'
        );
        
        $post = str_replace($exeption, '', $wall);
        $postArray = explode('_', $post);

        $params = array(
            'owner_id'=>$postArray[0],
            'post_id'=>$postArray[1],
            'v'=>'5.81'
        );

        $textList = explode("\n", $text);
        $accList = explode("\n", $accounts);
        $contTextList = count($textList);
        $contAccList = count($accList);

        for($i = 0; $i < $contTextList; $i++) {
            $params['message'] = $textList[$i];

            if($i >= $contAccList) {
                $accountNow = $accList[$i % $contAccList];
                $arrayLoginPass = explode(':', $accountNow);
                $acc = new Account($arrayLoginPass[0], $arrayLoginPass[1]);
                $params['access_token'] = $acc->getToken();


            } else {
                $accountNow = $accList[$i];
                $arrayLoginPass = explode(':', $accountNow);
                $acc = new Account($arrayLoginPass[0], $arrayLoginPass[1]);
                $params['access_token'] = $acc->getToken();

            };

            $request = file_get_contents('http://api.vk.com/method/wall.createComment?' . http_build_query($params));
            $response = json_decode($request, true);
            // echo '<p>' . $request . '</p>';
            if(isset($response['error'])) {
                $result['id' . $acc->getUserid()] = $response['error']['error_msg'];
            } else {
                $result['id' . $acc->getUserid()] = 'Успешно!';
            }
            
            sleep(2);
        }
    }

    // if(isset($_POST['comments']))
    // проверяем нажатие кнопки comments

    if(isset($_POST['blacklist'])) {
        $account = $_POST['account'];
        $users = $_POST['users'];

        // Если пришёл акк, сохраняем его в сессию
        $_SESSION['account'] = $account;

        $arrayLoginPass = explode(':', $account);
        $acc = new Account($arrayLoginPass[0], $arrayLoginPass[1]);

        $exeption = array(
            'https://vk.com/id',
            'vk.com/id',
            'id',
            'https://vk.com/'
        );

        $params = array(
            'access_token'=>$acc->getToken(),
            'v'=>'5.81'
        );

        foreach(explode("\n", $users) as $user) {
            $user = str_replace($exeption, '', $user);
            $params['owner_id'] = $user;            

            $request = file_get_contents('http://api.vk.com/method/account.ban?' . http_build_query($params));
            $response = json_decode($request, true);
            if(isset($response['error'])) {
                $result['id' . $user] = $response['error']['error_msg'];
            } else {
                $result['id' . $user] = 'Успешно!';
            }
            sleep(2);
        };
        
    };

    // if(isset($_POST['passChange']))
    // проверяем нажатие кнопки смены пароля

    if(isset($_POST['passChange'])) {
        $account = $_POST['account'];
        $newPass = $_POST['newPass'];

        $arrayLoginPass = explode(':', $account);
        $acc = new Account($arrayLoginPass[0], $arrayLoginPass[1]);

        $userId = $acc->getUserId();

        $params = array(
            'access_token'=>$acc->getToken(),
            'v'=>'5.81',
            'old_password'=>$arrayLoginPass[1],
            'new_password'=>$newPass
        );

        $url = 'https://api.vk.com/method/account.changePassword?' . http_build_query($params);
        $request = file_get_contents($url);
        $response = json_decode($request, true);

        $newToken = $response['response']['token'];
        $newLoginPass = $arrayLoginPass[0] . ':' . $newPass;

        $log = date('Y-m-d H:i:s') . ' смена пароля ' . 'id' . $userId . ': ' . $newLoginPass . PHP_EOL . $newToken . ';' . $userId . PHP_EOL . '==========';
        file_put_contents('log.txt', $log . PHP_EOL, FILE_APPEND);

    };

    // Отправка подарков
    // Проверяем нажатие кнопки gifts

    if (isset($_POST['gifts'])) {
        $accounts = explode("\r\n", $_POST['accounts']);
        $gift_id = $_POST['gift_id'];
        $text = $_POST['text'];

        // CREATE ACCOUNT OBJECT
        $acc = array_pop($accounts);
        [$login, $password] = explode(':', $acc);
        $accObj = new Account($login, $password);

        $search = array(
            'https://vk.com/id',
            'https://vk.com/'
        );
        
        foreach(explode("\r\n", $_POST['users']) as $user_id) {
            $user_id = str_replace($search, '', $user_id);
            while(count($accounts) >= 0) {
                $response = json_decode($accObj->giftSender($user_id, $gift_id, $text), true);
                if(isset($response['error'])) {
                    $result['id' . $accObj->user_id] = $response['error']['error_msg'];
                    if(count($accounts) == 0) {
                        break 2;
                    } else {
                        $acc = array_pop($accounts);
                        [$login, $password] = explode(':', $acc);
                        $accObj = new Account($login, $password);
                    }    
                } else {
                    $result['id' . $user_id] = 'Успешно!';
                    break;
                }
            }
        }
    }
}

function passGen() {
    $chars = 'qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP';
    $password = '';
    $charsLen = strlen($chars)-1;
    for($i = 0; $i < 8; $i++) {
        $password .= $chars[rand(0, $charsLen)];
    }

    return $password;
};

echo $twig->render('index.html', array(
    'accounts'=>$_SESSION['accounts'],
    'account'=>$_SESSION['account'],
    'ids'=>$_SESSION['ids'],
    'result'=>$result,
    'newLoginPass'=>$newLoginPass,
    'passGen'=>passGen()
) );