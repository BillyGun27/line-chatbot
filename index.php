<?php
/*
contoh json
{
  "events": [
      {
        "replyToken": "nHuyWiB7yP5Zw52FIkcQobQuGDXCTA",
        "type": "message",
        "timestamp": 1462629479859,
        "source": {
             "type": "group",
             "groupId": "Ca56f94637cc4347f90a25382909b24b9",
             "userId": "U206d25c2ea6bd87c17655609a1c37cb8"
         },
         "message": {
             "id": "325708",
             "type": "text",
             "text": "Hello, world"
          }
      }
  ]
}

cmd 

$ heroku login

$ heroku login                         // jika belum login
$ heroku create namaproyekkamu         // membuat project baru di heroku

$ git init
$ heroku git:remote -a namaproject

deploy
$ git add .
$ git commit -am "first commit"
$ git push heroku master

$ heroku ps

//create link
$ mkdir LineBotPHP
$ cd LineBotPHP
$ composer init

$ composer install

//contoh deploy

$ git add .
$ git commit -m "implementasi reply message"
$ git push heroku master


*/
require __DIR__ . '/vendor/autoload.php';

use \LINE\LINEBot;
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use \LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use \LINE\LINEBot\SignatureValidator as SignatureValidator;

// set false for production
$pass_signature = true;

// set LINE channel_access_token and channel_secret
$channel_access_token = "z38Mt13fSmdaMFX/TEKgkMlykuTWD70jCpdgqe8nNYWuYwr5sEGSB0aeXToCRVJM628IcdwSA+yylI1/6d0+WtW5Ao4lnEJYnuM3Ag354LsLVAGGxj5O2o85eqKq4plf2iPyTKnHtAW/6IFih29yUgdB04t89/1O/w1cDnyilFU=";
$channel_secret = "e7fd5130b7d9e0bb1865dc81dd4d60dd";

// set LINE channel_access_token and channel_secret
//this one with push message
//$channel_access_token = "KejhqvCuQIngisOkzHovUknJ9vzYWTvmoPvI56c3GzR1hChtLSFSi6MA84GRQECI/sSnI3lfUaeHIVVanNU/TxZlYEELzOPuKuxAhN9IZkSppL0iO6Nf1DJADs7lGwPIVahOs5j8UYpny7gZ3sXI5gdB04t89/1O/w1cDnyilFU=";
//$channel_secret = "8cd357efd009c5db179d3532b5195939";

//my user id  -->> U3ab394427cbbfb1eb41d5b39b5270e93 

// inisiasi objek bot
$httpClient = new CurlHTTPClient($channel_access_token);
$bot = new LINEBot($httpClient, ['channelSecret' => $channel_secret]);

$configs =  [
    'settings' => ['displayErrorDetails' => true],
];
$app = new Slim\App($configs);

// buat route untuk url homepage
$app->get('/', function($req, $res)
{
  echo "Welcome at Slim Framework";
});

// buat route untuk webhook
$app->post('/webhook', function ($request, $response) use ($bot, $pass_signature)
{
    // get request body and line signature header
    $body        = file_get_contents('php://input');
    $signature = isset($_SERVER['HTTP_X_LINE_SIGNATURE']) ? $_SERVER['HTTP_X_LINE_SIGNATURE'] : '';

    // log body and signature
    file_put_contents('php://stderr', 'Body: '.$body);

    if($pass_signature === false)
    {
        // is LINE_SIGNATURE exists in request header?
        if(empty($signature)){
            return $response->withStatus(400, 'Signature not set');
        }

        // is this request comes from LINE?
        if(! SignatureValidator::validateSignature($body, $channel_secret, $signature)){
            return $response->withStatus(400, 'Invalid signature');
        }
    }

    // kode aplikasi nanti disini

    $data = json_decode($body, true);
    if(is_array($data['events'])){
        foreach ($data['events'] as $event)
        {
            if ($event['type'] == 'message')
            {

                if(
                    $event['source']['type'] == 'group' or
                    $event['source']['type'] == 'room'
                  ){
                   //message from group / room     
                   if($event['source']['userId']){
                    
                        $userId     = $event['source']['userId'];
                        $getprofile = $bot->getProfile($userId);
                        $profile    = $getprofile->getJSONDecodedBody();
                        $greetings  = new TextMessageBuilder("Halo, ".$profile['displayName']);
                    
                        $result = $bot->replyMessage($event['replyToken'], $greetings);
                        return $res->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
                    
                    } else {
                        // send same message as reply to user
                        $result = $bot->replyText($event['replyToken'], $event['message']['text']);
                        return $res->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
                    }
                   
                  } else {
                   //message from single user

                    if($event['message']['type'] == 'text')
                    {
                        // send same message as reply to user
                        $result = $bot->replyText($event['replyToken'], $event['message']['text']);
        
                        // or we can use replyMessage() instead to send reply message
                        // $textMessageBuilder = new TextMessageBuilder($event['message']['text']);
                        // $result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
                        file_put_contents('php://stderr', $output);
        
                        return $response->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
                    }if(
                        $event['message']['type'] == 'image' or
                        $event['message']['type'] == 'video' or
                        $event['message']['type'] == 'audio' or
                        $event['message']['type'] == 'file'
                    ){
                        $basePath  = $request->getUri()->getBaseUrl();
                        $contentURL  = $basePath."/content/".$event['message']['id'];
                        $contentType = ucfirst($event['message']['type']);
                        $result = $bot->replyText($event['replyToken'],
                            $contentType. " yang kamu kirim bisa diakses dari link:\n " . $contentURL);
                    
                        return $res->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
                    }

                  }

               
            }
        }
    }

});

//multicast to all user
/*
Untuk mencobanya, buka alamat route index.php/multicast pada browser Anda, 
yaitu https://aplikasianda.herokuapp.com/index.php/multicast. 
Pesan multicast akan dikirimkan ke list user begitu alamat tersebut diakses.
*/
$app->get('/multicast', function($req, $res) use ($bot)
{
    // list of users
    $userList = [
        'U3ab394427cbbfb1eb41d5b39b5270e93',
        'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'];

    // send multicast message to user
    $textMessageBuilder = new TextMessageBuilder('Halo, ini pesan multicast');
    $result = $bot->multicast($userList, $textMessageBuilder);
   
    return $res->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
});

//get user profile

$app->get('/profile', function($req, $res) use ($bot)
{
    // get user profile
    $userId = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
    $result = $bot->getProfile($userId);
   
    return $res->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
});

$app->get('/profile/{userId}', function($req, $res) use ($bot)
{
    // get user profile
    $route  = $req->getAttribute('route');
    $userId = $route->getArgument('userId');
    $result = $bot->getProfile($userId);
             
    return $res->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
});

//the response if user send image,video,audio,file
$app->get('/content/{messageId}', function($req, $res) use ($bot)
{
    // get message content
    $route      = $req->getAttribute('route');
    $messageId = $route->getArgument('messageId');
    $result = $bot->getMessageContent($messageId);

    // set response
    $res->write($result->getRawBody());

    return $res->withHeader('Content-Type', $result->getHeader('Content-Type'));
});



$app->run();