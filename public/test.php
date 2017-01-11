<?php
/*
	 ini_set('display_errors', 1);
	 ini_set('display_startup_errors', 1);
	 ini_set('upload_max_filesize', '512M');
	 ini_set('post_max_size', '1024M');
	 ini_set('max_execution_time', '2592000');
	 ini_set('max_input_time', '2592000');
	error_reporting(E_ALL);
	ini_set('html_errors', true);
*/
use Aws\S3\S3Client;
use PHP_GCM\Sender;
use PHP_GCM\Message;

use Apple\ApnPush\Certificate\Certificate;
use Apple\ApnPush\Notification;
use Apple\ApnPush\Notification\Connection;


require realpath(__DIR__ . '/../vendor/autoload.php');

$s3_config = require('../app/s3_config.php');


/*$s3 = S3Client::factory([
    'key' => $s3_config['s3']['key'],
    'secret' => $s3_config['s3']['secret']
]);*/

// Instantiate an Amazon S3 client.
$s3 = new S3Client([
    'key' => $s3_config['s3']['key'],
    'secret' => $s3_config['s3']['secret'],
    'version' => 'latest',
    'region'  => 'us-west-2'
]);

$sender = new Sender('AIzaSyB1LrS5_3aQ9V6pnuzh-YWNqDWTlHUwErg');


$app = new Slim\Slim();

$mail = new PHPMailer;

//setting up mail configuration
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'niraj.1991@gmail.com';
$mail->Password = '';
$mail->SMTPSecure = 'tls';
$mail->Port = 587;
$mail->isHTML(true);
$mail->setFrom('freelancernirajchauhan@gmail.com', 'AMASI support');

function sendWelcomeMail($email, $name){	
	$mail = $GLOBALS['mail'];
	$mail->addAddress($email, $name);
	$mail->Subject = 'Welcome to AMASI Live';
	$mail->Body = 'Dear '.$name.'</br> Welcome to Amasi Live App </br> Thank you and we hope you will enjoy the app experience. </br> Amasi Live Team';
	//$mail->send();
	if(!$mail->send()) {
       		echo 'Message could not be sent.';
        	echo 'Mailer Error: ' . $mail->ErrorInfo;
    	} else {
        	echo 'Message has been sent';
    	}
}


$app->response()->headers->set('Access-Control-Allow-Headers', 'Content-Type');
$app->response()->headers->set('Access-Control-Allow-Methods', 'GET, POST');
$app->response()->headers->set('Access-Control-Allow-Origin', '*');
$app->response->header('charset', 'utf-8');

function CallAPI($method, $url, $data = false)
{
    $curl = curl_init();

    switch ($method)
    {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);

            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_PUT, 1);
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }

    // Optional Authentication:
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, "username:password");

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($curl);

    curl_close($curl);

    return $result;
}

$app->get('/test/mail',function() use ($app){
	sendWelcomeMail('niraj.1991@gmail.com', 'Niraj');
});

$app->get('/test/push',function() use ($app){
    $pemFilePath = realpath('../apn/AmasiLivepush.pem');
    $app->response->headers->set('Content-Type', 'application/json');
    try{
        $certificate = new Certificate($pemFilePath, '');
        $connection = new Connection($certificate, false); // if not sandbox make the second parameter true
        $notification = new Notification($connection);
        $resultMsg =  $notification->sendMessage('3c22c4f4a0a1981352975becb475d0d39bc1611bb9fe502de7dba14eddf82455', 'ذلك. لفشل الأثنان');
        $result = [
            "status" => "success",
            "message" => json_decode($resultMsg)
        ];
    }catch(Exception $error){
        $result = [
            "status" => "error",
            "message" => $error->getMessage()
        ];
    }
    echo json_encode($result);
});

$app->get('/rest',function() use ($app) {

    $pushIOsUsers = PushUser::select('token')->where('type', 'ios')->where('update', 1)->get();
    // @TODO : JSON To Send
    /**
     * {
    "tokens" : [
    {"token": "3c22c4f4a0a1981352975becb475d0d39bc1611bb9fe502de7dba14eddf82455"},
    {"token": "d823705b8fafdad93a8dcbb0f50d0b14de0b9f72c1404b617d6e9b04e118bbbb"},
    {"token": "0907abf26eb9f75e6380fda8225777d68a682093ae412644e85d0b8e27345715"}
    ],
    "message": "Test Message throughghg",
    "title": "Message Title"
    }
     */
    $data = [
        "tokens" => json_decode($pushIOsUsers),
        "message" => "Test message"
    ];
    $result = CallAPI('POST', 'http://ec2-54-200-155-63.us-west-2.compute.amazonaws.com/public/index.php/data',json_encode($data));

});

$app->get('/users', function () use ($app) {
    try {
        $app->response->headers->set('Content-Type', 'application/json');
        $alluser = User::all();
        if ($alluser) {
            $result = [
                "status" => "success",
                "data" => json_decode($alluser->toJson())
            ];

        } else {
            $result = [
                "status" => "success",
                "data" => "No Data"
            ];
        }
        print(json_encode($result));
    } catch (Exception $error) {
        print_r($error);
        $result = [
            "status" => "error",
            "message" => $error->getMessage()
        ];
        print(json_encode($result));
    }

//        $user = new User;
//    $user->username = "Test User2";
//   echo $user->save();
//    $alluser = User::all();
//    $alluser = User::where('first_name','niraj')->first();
//    echo $alluser->toJson();
});

/*This will get all the news based on dates descending*/
$app->get('/news', function () use ($app) {
    try {
        $app->response->headers->set('Content-Type', 'application/json');
        $allnews = News::orderBy('updated_at', 'desc')->get();

        if ($allnews) {
            $result = [
                "status" => "success",
                "data" => json_decode($allnews->toJson())
            ];

        } else {
            $result = [
                "status" => "success",
                "data" => "No Data"
            ];
        }
        print(json_encode($result));
    } catch (Exception $error) {
        $result = [
            "status" => "error",
            "message" => $error->getMessage()
        ];
        print(json_encode($result));
    }
});

/*This will get all the news details related to the news/id along with comments on that news*/
$app->get('/news/:newsId', function ($newsId) use ($app) {
    try {
        $commentsArray = array();
        $app->response->headers->set('Content-Type', 'application/json');
        $allnews = News::where('id', $newsId)->first();
        if ($allnews) {
            $comments = News::find($newsId)->getComments();
            foreach ($comments as $comment) {
                array_push($commentsArray, array(
                    'comment' => $comment->comment,
                    //'username' => $comment->User->first_name . '' .$comment->User->last_name,
                    'updated_at' => $comment->updated_at,
                    'user' => (object)$comment->getUser()->first()
                ));

            }

            $result = [
                "status" => "success",
                "data" => array('news' => json_decode($allnews->toJson()), 'comments' => json_decode(json_encode($commentsArray)))
            ];

        } else {
            $result = [
                "status" => "error",
                "message" => "No Data"
            ];
        }
        print(json_encode($result));
    } catch (Exception $error) {
        $result = [
            "status" => "error",
            "message" => $error->getMessage()
        ];
        print(json_encode($result));
    }
});

$app->get('/news/like/:newsId',function($newsId) use($app){
    try{
        $app->response->headers->set('Content-Type', 'application/json');
        $news = News::select('id','likes')->where('id', $newsId)->first();
        $news->likes = $news->likes + 1;
        $news->save();
        $result = [
            "status" => "success",
            "data" => json_decode($news->toJson())
        ];
        print(json_encode($result));
    }catch (Exception $error){
        echo $error;
    }
});


$app->post('/news/like',function() use($app){
    try{
        $app->response->headers->set('Content-Type', 'application/json');
        $json = $app->request->getBody();
        $allPostVars = json_decode($json, true);
        $newsId = $allPostVars['news_id'];
        $userId = $allPostVars['user_id'];
        $like = new NewsLikes;
        $like->user_id = $userId;
        $like->news_id = $newsId;
        if($like->save() > 0){
            $news = News::select('id','likes')->where('id', $newsId)->first();
            $news->likes = $news->likes + 1;
            $counter = $news->save();
            if($counter > 0){
                $result = [
                    "status" => "success",
                    "data" => json_decode($news->toJson())
                ];
                print(json_encode($result));
            }
        };
       
    }catch (Exception $error){
        $result = [
                    "status" => "error",
                    "message" => "Server unable to process your data"
                ];
                print(json_encode($result));
    }
});

$app->get('/video/like/:postId',function($postId) use($app){
    try{
        $app->response->headers->set('Content-Type', 'application/json');
        $post = Post::select('id','likes')->where('id', $postId)->first();
        $post->likes = $post->likes + 1;
        $post->save();
        $result = [
            "status" => "success",
            "data" => json_decode($post->toJson())
        ];
        print(json_encode($result));
    }catch (Exception $error){
        echo $error;
    }
});


$app->post('/post/like',function() use($app){
    try{
        $app->response->headers->set('Content-Type', 'application/json');
        $json = $app->request->getBody();
        $allPostVars = json_decode($json, true);
        $postId = $allPostVars['post_id'];
        $userId = $allPostVars['user_id'];
        $like = new PostLikes;
        $like->user_id = $userId;
        $like->post_id = $postId;
        if($like->save() > 0){
            $post = Post::select('id','likes')->where('id', $postId)->first();
            $post->likes = $post->likes + 1;
            $counter = $post->save();
            if($counter > 0){
                $result = [
                    "status" => "success",
                    "data" => json_decode($post->toJson())
                ];
                print(json_encode($result));
            }
        };

    }catch (Exception $error){
        $result = [
            "status" => "error",
            "message" => "Server unable to process your data"
        ];
        print(json_encode($result));
    }
});

$app->get('/premium/video/like/:postId',function($postId) use($app){
    try{
        $app->response->headers->set('Content-Type', 'application/json');
        $post = PremiumPost::select('id','likes')->where('id', $postId)->first();
        $post->likes = $post->likes + 1;
        $post->save();
        $result = [
            "status" => "success",
            "data" => json_decode($post->toJson())
        ];
        print(json_encode($result));
    }catch (Exception $error){
        echo $error;
    }
});

$app->post('/premium/like',function() use($app){
    try{
        $app->response->headers->set('Content-Type', 'application/json');
        $json = $app->request->getBody();
        $allPostVars = json_decode($json, true);
        $postId = $allPostVars['post_id'];
        $userId = $allPostVars['user_id'];
        $like = new PremiumPostLikes;
        $like->user_id = $userId;
        $like->post_id = $postId;
        if($like->save() > 0){
            $post = PremiumPost::select('id','likes')->where('id', $postId)->first();
            $post->likes = $post->likes + 1;
            $counter = $post->save();
            if($counter > 0){
                $result = [
                    "status" => "success",
                    "data" => json_decode($post->toJson())
                ];
                print(json_encode($result));
            }
        };

    }catch (Exception $error){
        $result = [
            "status" => "error",
            "message" => "Server unable to process your data"
        ];
        print(json_encode($result));
    }
});

$app->get('/video/:eventId', function ($eventId) use ($app) {
    try {
        $app->response->headers->set('Content-Type', 'application/json');
        $allVideo = Post::select('id', 'post_title', 'likes')->where('event_id', $eventId)->get();
        if ($allVideo) {
            $result = [
                "status" => "success",
                "data" => json_decode($allVideo->toJson())
            ];
        } else {
            $result = [
                "status" => "error",
                "message" => "No Data"
            ];
        }
        print(json_encode($result));
    } catch (Exception $error) {
        $result = [
            "status" => "error",
            "message" => $error->getMessage()
        ];
        print(json_encode($result));
    }
});


$app->get('/video/detail/:videoId', function ($videoId) use ($app) {
    try {
        $app->response->headers->set('Content-Type', 'application/json');
        $allVideo = Post::select('id', 'post_title', 'video_src', 'likes')->where('id', $videoId)->get();
        $commentsArray = array();
        if ($allVideo) {
            $comments = Post::find($videoId)->getComments();
            foreach ($comments as $comment) {
                array_push($commentsArray, array(
                    'comment' => $comment->comment,
                    'updated_at' => $comment->updated_at,
                    'user' => (object)$comment->getUser()->first()
                ));

            }
            $result = [
                "status" => "success",
                "data" => array('video' => json_decode($allVideo->toJson()), 'comments' => json_decode(json_encode($commentsArray)))
            ];
        } else {
            $result = [
                "status" => "error",
                "message" => "No Data"
            ];
        }
        print(json_encode($result));
    } catch (Exception $error) {
        $result = [
            "status" => "error",
            "message" => $error->getMessage()
        ];
        print(json_encode($result));
    }
});


$app->get('/video', function () use ($app) {
    try {
        $app->response->headers->set('Content-Type', 'application/json');
        $approved = "APPROVED";
        $allVideo = Post::select('id', 'post_title', 'video_src')->where('post_status', $approved)->orderBy('updated_at', 'desc')->get();
        if ($allVideo) {
            $result = [
                "status" => "success",
                "data" => json_decode($allVideo->toJson())
            ];
        } else {
            $result = [
                "status" => "error",
                "message" => "No Data"
            ];
        }
        print(json_encode($result));
    } catch (Exception $error) {
        $result = [
            "status" => "error",
            "message" => $error->getMessage()
        ];
        print(json_encode($result));
    }
});

$app->get('/selected/videos', function () use ($app) {
    try {
        $app->response->headers->set('Content-Type', 'application/json');
        $allVideo = PremiumPost::select('id', 'post_title', 'video_src', 'likes')->orderBy('updated_at', 'desc')->get();
        if ($allVideo) {
            $result = [
                "status" => "success",
                "data" => json_decode($allVideo->toJson())
            ];
        } else {
            $result = [
                "status" => "error",
                "message" => "No Data"
            ];
        }
        print(json_encode($result));
    } catch (Exception $error) {
        $result = [
            "status" => "error",
            "message" => $error->getMessage()
        ];
        print(json_encode($result));
    }
});

$app->get('/selected/video/:videoId', function ($videoId) use ($app) {
    try {
        $app->response->headers->set('Content-Type', 'application/json');
        $allVideo = PremiumPost::where('id',$videoId)->get();
        $commentsArray = array();
        if ($allVideo) {
            $comments = PremiumPost::find($videoId)->getComments();
            foreach ($comments as $comment) {
                array_push($commentsArray, array(
                    'comment' => $comment->comment,
                    'updated_at' => $comment->updated_at,
                    'user' => (object)$comment->getUser()->first()
                ));

            }
            $result = [
                "status" => "success",
                "data" => array('video' => json_decode($allVideo->toJson()), 'comments' => json_decode(json_encode($commentsArray)))
            ];
        } else {
            $result = [
                "status" => "error",
                "message" => "No Data"
            ];
        }
        print(json_encode($result));
    } catch (Exception $error) {
        $result = [
            "status" => "error",
            "message" => $error->getMessage()
        ];
        print(json_encode($result));
    }
});

$app->get('/pending/videos', function () use ($app) {
    try {
        $app->response->headers->set('Content-Type', 'application/json');
        $approved = "PENDING";
        //$allVideo = Post::select('id', 'post_title', 'video_src')->where('post_status', $approved)->orderBy('updated_at', 'desc')->get();
        $allVideo = Post::orderBy('updated_at', 'desc')->get();
        if ($allVideo) {
            $result = [
                "status" => "success",
                "data" => json_decode($allVideo->toJson())
            ];
        } else {
            $result = [
                "status" => "error",
                "message" => "No Data"
            ];
        }
        print(json_encode($result));
    } catch (Exception $error) {
        $result = [
            "status" => "error",
            "message" => $error->getMessage()
        ];
        print(json_encode($result));
    }
});

$app->get('/role/:userId', function ($userId) use ($app) {
    try {
        $app->response->headers->set('Content-Type', 'application/json');
        $user = User::where('id', $userId)->get();
        if ($user) {
            $result = [
                "status" => "success",
                "data" => json_decode($user->toJson())
            ];
        } else {
            $result = [
                "status" => "error",
                "message" => "No Data"
            ];
        }
        print(json_encode($result));
    } catch (Exception $error) {
        $result = [
            "status" => "error",
            "message" => $error->getMessage()
        ];
        print(json_encode($result));
    }
});

$app->post('/create/news', function () use ($app,$sender) {
    try {
        $app->response->headers->set('Content-Type', 'application/json');
        $json = $app->request->getBody();
        $allPostVars = json_decode($json, true); // parse the JSON into an associative array
        $news = new News;
        $news->news_title = $allPostVars['news_title'];
        $news->news_content = $allPostVars['news_content'];
        $news->news_img = $allPostVars['img_name'];
        $news->likes = 0;
        if (($news->save()) > 0) {
            $result = [
                "status" => "success",
                "message" => "News Created"
            ];
            print(json_encode($result));
            $postData = array(
                "type" => "android",
                "message" => $allPostVars['news_title'],
                "title" => "أماسي الشعر والشعراء"
            );
            $pushUsers = PushUser::select('token')->where('type', $postData['type'])->where('news', 1)->get();
            $pushIOsUsers = PushUser::select('token')->where('type', 'ios')->where('news', 1)->get();
            $data = [
                "tokens" => json_decode($pushIOsUsers),
                "message" => $allPostVars['news_title']
            ];
            $result = CallAPI('POST', 'http://ec2-54-200-155-63.us-west-2.compute.amazonaws.com/public/index.php/data',json_encode($data));
            //public/index.php/data

            $payloadData = array
            (
                'message' => $postData['message'],
                'title' => $postData['title'],
                'subtitle' => '',
                'tickerText' => '',
                'vibrate' => 1,
                'sound' => 'default',
                'largeIcon' => 'icon',
                'smallIcon' => 'icon'
            );
            $collapseKey = "optional";
            $message = new Message($collapseKey, $payloadData);
            $numberOfRetryAttempts =1;
            try {
                $deviceRegistrationId = array();
                foreach ($pushUsers as $pushUser) {
                    array_push($deviceRegistrationId, $pushUser->token);
                }
                $result = $sender->sendMulti($message, $deviceRegistrationId, $numberOfRetryAttempts);
            } catch (\InvalidArgumentException $e) {
                // $deviceRegistrationId was null
                $result = [
                    "status" => "ERROR",
                    "data" => "invalid data found"
                ];
                echo json_encode($result);
            } catch (PHP_GCM\InvalidRequestException $e) {
                // server returned HTTP code other than 200 or 500
                $result = [
                    "status" => "ERROR",
                    "data" => "Invalid request"
                ];
                echo json_encode($result);
            } catch (\Exception $e) {
                // message could not be sent
                $result = [
                    "status" => "ERROR",
                    "data" => "message could not be sent"
                ];
                echo json_encode($result);
            }
        };
    } catch (Exception $error) {
        $result = [
            "status" => "error",
            "message" => $error->getMessage()
        ];
        print(json_encode($result));
    }
});

//comments
$app->post('/create/news/comment', function () use ($app) {
    try {
        $app->response->headers->set('Content-Type', 'application/json');
        $json = $app->request->getBody();
        $allPostVars = json_decode($json, true); // parse the JSON into an associative array
        $comment = new Comment;
        $comment->user_id = $allPostVars['user_id'];
        $comment->news_id = $allPostVars['news_id'];
        $comment->comment = $allPostVars['comment'];
        if (($comment->save()) > 0) {
            $result = [
                "status" => "success",
                "message" => "Comment Added"
            ];
            print(json_encode($result));
        };
    } catch (Exception $error) {
        $result = [
            "status" => "error",
            "message" => $error->getMessage()
        ];
        print(json_encode($result));
    }
});

$app->post('/create/post/comment', function () use ($app) {
    try {
        $app->response->headers->set('Content-Type', 'application/json');
        $json = $app->request->getBody();
        $allPostVars = json_decode($json, true); // parse the JSON into an associative array
        $comment = new PostComment;
        $comment->user_id = $allPostVars['user_id'];
        $comment->post_id = $allPostVars['post_id'];
        $comment->comment = $allPostVars['comment'];
        if (($comment->save()) > 0) {
            $result = [
                "status" => "success",
                "message" => "Comment Added"
            ];
            print(json_encode($result));
        };
    } catch (Exception $error) {
        $result = [
            "status" => "error",
            "message" => $error->getMessage()
        ];
        print(json_encode($result));
    }
});

$app->post('/create/selected/post/comment', function() use ($app){
    try {
        $app->response->headers->set('Content-Type', 'application/json');
        $json = $app->request->getBody();
        $allPostVars = json_decode($json, true); // parse the JSON into an associative array
        $comment = new PremiumPostComment();
        $comment->user_id = $allPostVars['user_id'];
        $comment->post_id = $allPostVars['post_id'];
        $comment->comment = $allPostVars['comment'];
        if (($comment->save()) > 0) {
            $result = [
                "status" => "success",
                "message" => "Comment Added"
            ];
            print(json_encode($result));
        };
    } catch (Exception $error) {
        $result = [
            "status" => "error",
            "message" => $error->getMessage()
        ];
        print(json_encode($result));
    }
});

$app->get('/events', function () use ($app) {
    try {
        $app->response->headers->set('Content-Type', 'application/json');
        $events = Event::all();

        if ($events) {
            $result = [
                "status" => "success",
                "data" => json_decode($events->toJson())
            ];

        } else {
            $result = [
                "status" => "error",
                "message" => "No Data"
            ];
        }
        print(json_encode($result));
    } catch (Exception $error) {
        $result = [
            "status" => "error",
            "message" => $error->getMessage()
        ];
        print(json_encode($result));
    }
});

$app->get('/events/:eventId', function ($eventId) use ($app) {
    try {
        //$commentsArray = array();
        $app->response->headers->set('Content-Type', 'application/json');
        $events = Event::where('id', $eventId)->first();
        if ($events) {
            $result = [
                "status" => "success",
                "data" => array('event' => json_decode($events->toJson()))
            ];

            } else {
            $result = [
                "status" => "error",
                "message" => "No Data"
            ];
        }
        print(json_encode($result));
    } catch (Exception $error) {
        $result = [
            "status" => "error",
            "message" => $error->getMessage()
        ];
        print(json_encode($result));
    }
});

$app->get('/upcoming/events', function () use ($app) {
    try {
        $app->response->headers->set('Content-Type', 'application/json');
        //print_r(date("Y-m-d"));
        $events = Event::where('schedule_start', '>', date("Y-m-d", time()))->get();

        if ($events) {
            $result = [
                "status" => "success",
                "data" => json_decode($events->toJson())
            ];

        } else {
            $result = [
                "status" => "error",
                "message" => "No Data"
            ];
        }
        print(json_encode($result));
    } catch (Exception $error) {
        $result = [
            "status" => "error",
            "message" => $error->getMessage()
        ];
        print(json_encode($result));
    }
});

$app->get('/live/events', function () use ($app) {
    try {
        $app->response->headers->set('Content-Type', 'application/json');
        //print_r(date("Y-m-d"));
        $events = Event::where('schedule_start', '=', date("Y-m-d", time()))->get();

        if ($events) {
            $result = [
                "status" => "success",
                "data" => json_decode($events->toJson())
            ];

        } else {
            $result = [
                "status" => "error",
                "message" => "No Data"
            ];
        }
        print(json_encode($result));
    } catch (Exception $error) {
        $result = [
            "status" => "error",
            "message" => $error->getMessage()
        ];
        print(json_encode($result));
    }
});

$app->get('/archive/events', function () use ($app) {
    try {
        $app->response->headers->set('Content-Type', 'application/json');
        //echo date("Y-m-d", strtotime("-2 day"));
        $events = Event::where('schedule_end', '<=', date("Y-m-d", strtotime("-1 day")))->orderBy('schedule_start', 'desc')->get();
        if ($events) {
            $result = [
                "status" => "success",
                "data" => json_decode($events->toJson())
            ];

        } else {
            $result = [
                "status" => "error",
                "message" => "No Data"
            ];
        }
        print(json_encode($result));
    } catch (Exception $error) {
        $result = [
            "status" => "error",
            "message" => $error->getMessage()
        ];
        print(json_encode($result));
    }
});

$app->post('/create/event', function () use ($app,$sender) {
    try {
        $app->response->headers->set('Content-Type', 'application/json');
        $json = $app->request->getBody();
        $allPostVars = json_decode($json, true); // parse the JSON into an associative array
        $event = new Event;
        $event->event_name = $allPostVars['event_name'];
        $event->event_details = $allPostVars['event_details'];
        $event->event_venue = $allPostVars['venue'];
        $event->schedule_start = date('Y-m-d', strtotime($allPostVars['start_date'])); //'10/16/2003'
        $event->schedule_end = date('Y-m-d', strtotime($allPostVars['end_date']));
        if (($event->save()) > 0) {
            $result = [
                "status" => "success",
                "message" => "Event Created"
            ];
            print(json_encode($result));
            $postData = array(
                "type" => "android",
                "message" => "إسم الأمسية" . " : {$allPostVars['event_name']}\n". "المكان" . " : {$allPostVars['venue']}\n". "التاريخ". " : ".$event->schedule_start,
                "title" => "أماسي جديدة"
            );
            $pushUsers = PushUser::select('token')->where('type', $postData['type'])->where('event', 1)->get();
            /** start of ios push */

            $pushIOsUsers = PushUser::select('token')->where('type', 'ios')->where('event', 1)->get();
            $data = [
                "tokens" => json_decode($pushIOsUsers),
                "message" => "إسم الأمسية" . " : {$allPostVars['event_name']}\n". "المكان" . " : {$allPostVars['venue']}\n". "التاريخ". " : ".$event->schedule_start
            ];
            $result = CallAPI('POST', 'http://ec2-54-200-155-63.us-west-2.compute.amazonaws.com/public/index.php/data',json_encode($data));

            /** end  */
            $payloadData = array
            (
                'message' => $postData['message'],
                'title' => $postData['title'],
                'subtitle' => '',
                'tickerText' => '',
                'vibrate' => 1,
                'sound' => 'default',
                'largeIcon' => 'icon',
                'smallIcon' => 'icon'
            );
            $collapseKey = "optional";
            $message = new Message($collapseKey, $payloadData);
            $numberOfRetryAttempts =1;
            try {
                $deviceRegistrationId = array();
                foreach ($pushUsers as $pushUser) {
                    array_push($deviceRegistrationId, $pushUser->token);
                }
                $result = $sender->sendMulti($message, $deviceRegistrationId, $numberOfRetryAttempts);
            } catch (\InvalidArgumentException $e) {
                // $deviceRegistrationId was null
                $result = [
                    "status" => "ERROR",
                    "data" => "invalid data found"
                ];
                echo json_encode($result);
            } catch (PHP_GCM\InvalidRequestException $e) {
                // server returned HTTP code other than 200 or 500
                $result = [
                    "status" => "ERROR",
                    "data" => "Invalid request"
                ];
                echo json_encode($result);
            } catch (\Exception $e) {
                // message could not be sent
                $result = [
                    "status" => "ERROR",
                    "data" => "message could not be sent"
                ];
                echo json_encode($result);
            }
        };

    } catch (Exception $error) {
        $result = [
            "status" => "error",
            "message" => $error->getMessage()
        ];
        print(json_encode($result));
    }
});

$app->post('/create/post', function () use ($app) {
    try {
        $app->response->headers->set('Content-Type', 'application/json');
        $json = $app->request->getBody();
        $allPostVars = json_decode($json, true); // parse the JSON into an associative array
        $post = new Post;
        $post->post_title = $allPostVars['post_title'];
        $post->video_src = $allPostVars['video_src'];
        $post->likes = 0; //@TODO user_id need to be included too here.
        $post->post_status = "APPROVED";
        if (isset($allPostVars['category'])) {
            $post->category = $allPostVars['category'];
        } else {
            $post->category = "EVENT";
        }
        $post->event_id = $allPostVars['event_id'];
        $post->updated_at = date('Y-m-d', time()); //'10/16/2003'
        $post->created_at = date('Y-m-d', time());
        if (($post->save()) > 0) {
            $result = [
                "status" => "success",
                "message" => $post
            ];
            print(json_encode($result));
        };

    } catch (Exception $error) {
        $result = [
            "status" => "error",
            "message" => $error->getMessage()
        ];
        print(json_encode($result));
    }
});

$app->post('/create/premium/post', function () use ($app) {
    try {
        $app->response->headers->set('Content-Type', 'application/json');
        $json = $app->request->getBody();
        $allPostVars = json_decode($json, true); // parse the JSON into an associative array
        $post = new PremiumPost;
        $post->post_title = $allPostVars['post_title'];
        $post->video_src = $allPostVars['video_src'];
        $post->likes = 0;
        $post->updated_at = date('Y-m-d', time()); //'10/16/2003'
        $post->created_at = date('Y-m-d', time());
        if (($post->save()) > 0) {
            $result = [
                "status" => "success",
                "message" => $post
            ];
            print(json_encode($result));
        };

    } catch (Exception $error) {
        $result = [
            "status" => "error",
            "message" => $error->getMessage()
        ];
        print(json_encode($result));
    }
});

$app->post('/check/user', function () use ($app) {
    try {
        $app->response->headers->set('Content-Type', 'application/json');
        $result = array();
        $json = $app->request->getBody();
        $allPostVars = json_decode($json, true);
        $data = User::select('id', 'first_name', 'last_name', 'email', 'city', 'role', 'age', 'profile')->where('email', $allPostVars['email'])->where('password', md5($allPostVars['password']))->first();
        if ($data) {
            $result["status"] = "success";
            $result["data"] = json_decode($data->toJson());
        } else {
            $result["status"] = "error";
            $result["message"] = "Incorrect Credential.";
        }
        print_r(json_encode($result));

    } catch (Exception $error) {
        $result = [
            "status" => "error",
            "message" => "Incorrect Credential.",
            "error" => $error->getMessage()
        ];
        print(json_encode($result));
    }

});

$app->get('/user/exist/:email', function ($email) use ($app) {
    try {
        $app->response->headers->set('Content-Type', 'application/json');
        if (User::where('email', '=', $email)->exists()) {
            // user found
            $result = [
                "status" => "success",
                "data" => (object)User::where('email', '=', $email)->get()->first()
            ];
        } else {
            $result = [
                "status" => "error",
                "message" => "No User Found!!!"
            ];
        }
        print(json_encode($result));
    } catch (Exception $error) {
        $result = [
            "status" => "error",
            "message" => $error->getMessage()
        ];
        print(json_encode($result));
    }
});

$app->post('/edit/role/user', function () use ($app) {

    try {
        $app->response->headers->set('Content-Type', 'application/json');
        $json = $app->request->getBody();
        $allPostVars = json_decode($json, true);
        $user = User::where('id', $allPostVars['id'])->get()->first();
        if ($user) {
            $user->role = $allPostVars['role'];
            if ($user->save() > 0) {
                if($user->role === "BANNED"){
                    $liveVideoComment = PostComment::where('user_id', 'like', $user->id )->delete();
                    $selectedVideoComment = PremiumPostComment::where('user_id', 'like', $user->id )->delete();
                    $newsComment = Comment::where('user_id', 'like', $user->id )->delete();
                    $videoPosted = Post::where('user_id', 'like', $user->id )->delete();

                    $result = [
                        "status" => "success",
                        "data" => json_decode($user->toJson()),
                        "message" => "{$liveVideoComment} video comments , {$selectedVideoComment} selected video comment, {$newsComment} news comment, {$videoPosted} videos deleted."
                    ];
                }
                $result = [
                    "status" => "success",
                    "data" => json_decode($user->toJson())
                ];
                print(json_encode($result));
            }

        }
    } catch (Exception $error) {
        $result = [
            "status" => "error",
            "message" => $error->getMessage()
        ];
        print(json_encode($result));
    }

});

$app->post('/edit/user', function () use ($app) {

    try {
        $app->response->headers->set('Content-Type', 'application/json');
        $json = $app->request->getBody();
        $allPostVars = json_decode($json, true);
        $user = User::where('id', $allPostVars['id'])->get()->first();
        if ($user) {
            $user->first_name = $allPostVars['first_name'];
            $user->last_name = $allPostVars['last_name'];
            $user->city = $allPostVars['city'];
            $user->age = $allPostVars['age'];
            if (isset($allPostVars['profile'])) {
                $user->profile = $allPostVars['profile'];
            }
            if ($user->save() > 0) {
                $result = [
                    "status" => "success",
                    "data" => json_decode($user->toJson())
                ];
                print(json_encode($result));
            }

        }
    } catch (Exception $error) {
        $result = [
            "status" => "error",
            "message" => $error->getMessage()
        ];
        print(json_encode($result));
    }

});

$app->post('/create/user', function () use ($app) {

    try {
        $app->response->headers->set('Content-Type', 'application/json');
        $json = $app->request->getBody();
        $allPostVars = json_decode($json, true); // parse the JSON into an associative array
//      $allPostVars = $app->request->post(); if the data is coming through form-data
        $user = new User;
        $user->first_name = $allPostVars["first_name"];
        $user->last_name = $allPostVars["last_name"];
        $user->password = md5($allPostVars["password"]);
        $user->email = $allPostVars["email"];
        $user->city = $allPostVars["city"];
        $user->age = $allPostVars["age"];
        $user->profile = $allPostVars["profile"];
        $user->role = "GENERAL";
        $user->time_stamp = date('Y-m-d H:i:s');
        if (($user->save()) > 0) {
            //sending welcome mail
		sendWelcomeMail($allPostVars["email"],  $allPostVars["first_name"]);
	    $result = [
                "status" => "success",
                "data" => json_decode($user->toJson())
            ];
            print(json_encode($result));
        };
    } catch (Exception $error) {
        $result = [
            "status" => "error",
            "message" => $error->getMessage()
        ];
        print(json_encode($result));
    }
});

$app->post('/upload/image', function () use ($app) {
    //echo ini_get('upload_max_filesize');

    //echo intval(ini_get('upload_max_filesize'))*1024*1024;
    $app->response()->header("Content-Type", "application/json");
    //print_r($_FILES['uploads']);

    //echo($_FILES['uploads']['size']);

    if (!isset($_FILES['uploads'])) {
        $result = [
            "status" => "error",
            "message" => "No files to upload!!"
        ];
        print(json_encode($result));
        return;
    } else if ($_FILES['uploads']['size'] > (intval(ini_get('upload_max_filesize')) * 1024 * 1024)) {
        $result = [
            "status" => "error",
            "message" => "Sorry can't upload such long file"
        ];
        print(json_encode($result));
        return;
    }


    $imgs = array();

    $files = $_FILES['uploads'];
    $cnt = count($files['name']);

    if ($cnt > 1) {
        $result = [
            "status" => "error",
            "message" => "Can't upload multiple files"
        ];
        print(json_encode($result));
        return;
    } else if ($files['error'] === 0) {
        $name = uniqid('img-' . date('Ymd') . '-');
        $ext = explode(".", $files["name"]);
        if (move_uploaded_file($files['tmp_name'], 'uploads/images/' . $name . '.' . $ext[1]) === true) {
            $imgs[] = array('url' => '/uploads/images/' . $name . '.' . $ext[1], 'name' => $files['name']);
            $result = [
                "status" => "success",
                "data" => $imgs
            ];
            print(json_encode($result));
        }
    } else {
        $result = [
            "status" => "error",
            "message" => "Files not uploaded!! Try Again"
        ];
        print(json_encode($result));
    }


});

$app->post('/upload/video', function () use ($app, $s3, $s3_config) {

    //$app->response()->header("Content-Type", "application/json");

    echo($_FILES['uploads']['size']);

    if (!isset($_FILES['uploads'])) {
        $result = [
            "status" => "error",
            "message" => "No files to upload!!"
        ];
        print(json_encode($result));
        return;
    } else if ($_FILES['uploads']['size'] > (intval(ini_get('upload_max_filesize')) * 1024 * 1024)) {
        $result = [
            "status" => "error",
            "message" => "Sorry can't upload such long file"
        ];
        print(json_encode($result));
        return;
    }


    $imgs = array();

    $files = $_FILES['uploads'];
    $cnt = count($files['name']);

    if ($cnt > 1) {
        $result = [
            "status" => "error",
            "message" => "Can't upload multiple files"
        ];
        print(json_encode($result));
        return;
    } else if ($files['error'] === 0) {
        $name = uniqid('vid-' . date('Ymd') . '-');
        $ext = explode(".", $files["name"]);

        $ext = strtolower($ext[1]);
        echo $ext;
        if (move_uploaded_file($files['tmp_name'], 'uploads/videos/' . $name . '.' . $ext) === true) {
            $imgs[] = array('url' => '/uploads/videos/' . $name . '.' . $ext, 'name' => $files['name']);
            $result = [
                "status" => "success",
                "data" => $imgs
            ];
            try {
                $s3->putObject([
                    'Bucket' => $s3_config['s3']['bucket'],
                    'Key' => "{$name}.{$ext}",
                    'Body' => fopen('uploads/videos/' . $name . '.' . $ext, 'rb'),
                    'ACL' => 'public-read'
                ]);
            } catch (\Aws\S3\Exception\S3Exception $e) {
                print($e);
            }
            print(json_encode($result));
        }
    } else {
        $result = [
            "status" => "error",
            "message" => "Files not uploaded!! Try Again"
        ];
        print(json_encode($result));
    }


});

$app->post('/upload', function () use ($app, $s3, $s3_config) {
    $app->response()->header("Content-Type", "application/json");
    $allowed = array('image/png', 'image/jpeg', 'video/mp4');
    print_r($_FILES['uploads']);
    if (!isset($_FILES['uploads'])) {
        $result = [
            "status" => "error",
            "message" => "No files to upload!!"
        ];
        print(json_encode($result));
        return;
    } else if ($_FILES['uploads']['size'] > (intval(ini_get('upload_max_filesize')) * 1024 * 1024)) {
        $result = [
            "status" => "error",
            "message" => "File is too long!!!"
        ];
        print(json_encode($result));
        return;
    } else if ($_FILES['uploads']['name'] > 1) {
        $result = [
            "status" => "error",
            "message" => "Multiple  Files Upload Not Supported!!!"
        ];
        print(json_encode($result));
        return;
    } else if ((in_array($_FILES['uploads']['type'], $allowed)) && ($_FILES['uploads']['error'] === 0)) {
        $name = uniqid('vid-' . date('Ymd') . '-');
        $ext = explode(".", $_FILES['uploads']["name"]);
        if (move_uploaded_file($_FILES['uploads']['tmp_name'], 'uploads/' . $name . '.' . strtolower($ext[1]))) {
            try {
                $file_ext = strtolower($ext[1]);
                $s3_result = $s3->putObject([
                    'Bucket' => $s3_config['s3']['bucket'],
                    'Key' => "{$name}.{$file_ext}",
                    'Body' => fopen('uploads/' . $name . '.' . $file_ext, 'rb'),
                    'ACL' => 'public-read'
                ]);
                $result = [
                    "status" => "success",
                    "message" => array("url" => $s3_result['ObjectURL'], "file_type" => $_FILES['uploads']['type'])
                ];
                print(json_encode($result));
                unlink('uploads/' . $name . '.' . $file_ext);
            } catch (\Aws\S3\Exception\S3Exception $e) {
                $result = [
                    "status" => "error",
                    "message" => $e->getMessage()
                ];
                print(json_encode($result));
            }
        };
    } else {
        $result = [
            "status" => "error",
            "message" => "Unsupported File Format"
        ];
        print(json_encode($result));
    }
});

$app->post('/push/register', function () use ($app) {
    $app->response()->header("Content-Type", "application/json");
    $postData = $app->request->getBody();
    $postData = json_decode($postData, true);
    if (PushUser::where('token', '=', $postData["token"])->exists()) {
        //already registered  -- do nothing
        $result = [
            "status" => "SUCCESS",
            "data" => PushUser::select('user','type','token','update','news','event')->where('token', '=', $postData["token"])->first()
        ];
    } else {
        $pushUser = new PushUser;
        $pushUser->user = $postData["user"];
        $pushUser->type = $postData["type"];
        $pushUser->token = $postData["token"];
        $pushUser->update =  $postData["update"];
        $pushUser->news =  $postData["news"];
        $pushUser->event =  $postData["event"];
        if (($pushUser->save()) > 0) {
            $result = [
                "status" => "SUCCESS",
                "data" => $pushUser
            ];
        }
    }
    echo json_encode($result);
});

$app->post('/update/register', function () use ($app) {
    $app->response()->header("Content-Type", "application/json");
    $postData = $app->request->getBody();
    $postData = json_decode($postData, true);
    if (PushUser::where('user', '=', $postData["user"])->exists()) {
        //already registered  -- do nothing
        $pushUser = PushUser::where('user', '=', $postData["user"])->first();
        $pushUser->update =  $postData["update"];
        $pushUser->news =  $postData["news"];
        $pushUser->event =  $postData["event"];
        if (($pushUser->save()) > 0) {
            $result = [
                "status" => "SUCCESS",
                "data" => $pushUser
            ];
        }
    }
    echo json_encode($result);
});

$app->post('/push', function () use ($app, $sender) {
    //$app->response()->header("Content-Type", "application/json");
    $numberOfRetryAttempts = 1;

    $postData = $app->request->getBody();
    $postData = json_decode($postData, true);
    $pushUsers = PushUser::select('token')->where('type', $postData['type'])->where('update', 1)->get();
    /** @var  $pushIOsUsers */

    $pushIOsUsers = PushUser::select('token')->where('type', 'ios')->where('update', 1)->get();
    $data = [
        "tokens" => json_decode($pushIOsUsers),
        "message" => $postData['message']
    ];
    $result = CallAPI('POST', 'http://ec2-54-200-155-63.us-west-2.compute.amazonaws.com/public/index.php/data',json_encode($data));

    /**end of ios push **/

    $payloadData = array
    (
        'message' => $postData['message'],
        'title' => $postData['title'],
        'subtitle' => '',
        'tickerText' => '',
        'vibrate' => 1,
        'sound' => 'default',
        'largeIcon' => 'icon',
        'smallIcon' => 'icon'
    );
    $collapseKey = "optional";
    $message = new Message($collapseKey, $payloadData);
    try {
        $deviceRegistrationId = array();
        foreach ($pushUsers as $pushUser) {
            array_push($deviceRegistrationId, $pushUser->token);
        }
        print_r($deviceRegistrationId);
        $result = $sender->sendMulti($message, $deviceRegistrationId, $numberOfRetryAttempts);
    } catch (\InvalidArgumentException $e) {
        // $deviceRegistrationId was null
        $result = [
            "status" => "ERROR",
            "data" => "invalid data found"
        ];
        echo json_encode($result);
    } catch (PHP_GCM\InvalidRequestException $e) {
        // server returned HTTP code other than 200 or 500
        $result = [
            "status" => "ERROR",
            "data" => "Invalid request"
        ];
        echo json_encode($result);
    } catch (\Exception $e) {
        // message could not be sent
        $result = [
            "status" => "ERROR",
            "data" => "message could not be sent"
        ];
        echo json_encode($result);
    }
});

/*
 * Dashboard group
 */
$app->group('/dashboard',function() use ($app, $s3, $s3_config){

    //video group`
    $app->group('/video',function() use ($app, $s3, $s3_config){
        $app->post('/change/status',function() use ($app){
            $app->response()->header("Content-Type", "application/json");
            $postData = $app->request->getBody();
            $postData = json_decode($postData, true);
            $video = Post::where('id',$postData['post_id'])->get()->first();
            // "APPROVED" , "PENDING"   -- status
            $video->post_status = $postData['status'];
            if($video->save() > 0){
                $result = [
                    "status" => "success",
                    "data" => json_decode($video)
                ];
            }
            echo json_encode($result);
        });

        $app->get('/delete/:id',function($id) use ($app, $s3, $s3_config){
            $app->response()->header("Content-Type", "application/json");
            $video = Post::where('id',$id)->get()->first();
            if($video){
                if($video->delete() > 0){
                    $comment = PostComment::where('post_id', 'like', $id )->delete();
                    $result = [
                        "status" => "success",
                        "data" => json_decode($video)
                    ];
                }else{
                    $result = [
                        "status" => "error",
                        "message" => "No record(s) to delete."
                    ];
                };
            }else{
                $result = [
                    "status" => "error",
                    "message" => "No record(s) to delete."
                ];
            }

            echo json_encode($result);
        });
        $app->get('/premiumdelete/:id',function($id) use ($app, $s3, $s3_config){
            $app->response()->header("Content-Type", "application/json");
            $video = PremiumPost::where('id',$id)->get()->first();
            if($video){
                if($video->delete() > 0){
                    $comment = PremiumPostComment::where('post_id', 'like', $id )->delete();
                    $result = [
                        "status" => "success",
                        "data" => json_decode($video)
                    ];
                }else{
                    $result = [
                        "status" => "error",
                        "message" => "No record(s) to delete."
                    ];
                };
            }else{
                $result = [
                    "status" => "error",
                    "message" => "No record(s) to delete."
                ];
            }

            echo json_encode($result);
        });
    });

     //comments group
    $app->group('/comments', function () use ($app) {
        $app->get('/all', function () use ($app) {
            $app->response()->header("Content-Type", "application/json");
            $newsChats = Comment::select('id','comment')->get();
            $postComment = PostComment::select('id','comment')->get();
            $premiumPostComment = PremiumPostComment::select('id','comment')->get();
            $result = [
                "status" => "success",
                "newsChats" => json_encode($newsChats),
                "videoChats" => json_encode($postComment),
                "selectedVideoChats" => json_encode($premiumPostComment)
            ];
            echo json_encode($result);
        });
        $app->get('/delete/:type/:id',function($type,$id) use ($app){
            $count = 0;
            switch($type){
                case "News" :
                    $count = Comment::where('id', $id)->get()->first()->delete();
                    break;
                case "Video" :
                    $count = PostComment::where('id', $id)->get()->first()->delete();
                    break;
                case "Selected Video" :
                    $count = PremiumPostComment::where('id', $id)->get()->first()->delete();
                    break;
            }
            if($count > 0){
                $result = [
                    "status" => "success",
                    "data" => $count." rows deleted"
                ];
            }else{
                $result = [
                    "status" => "success",
                    "data" => "Nothign to delete"
                ];
            }
            echo json_encode($result);
        });
    });
    
});
/*DELETE EVENT*/

       $app->get('/events/delete/:id',function($id) use ($app){
            $app->response()->header("Content-Type", "application/json");
            $event = Event::where('id',$id)->get()->first();
            if($event){
                if($event->delete() > 0){
                    $result = [
                        "status" => "success",
                        "data" => json_decode($event)
                    ];
                }else{
                    $result = [
                        "status" => "error",
                        "message" => "No record(s) to delete."
                    ];
                };
            }else{
                $result = [
                    "status" => "error",
                    "message" => "No record(s) to delete."
                ];
            }

            echo json_encode($result);
        });
/* DELETE NEWS*/
$app->get('/news/delete/:id',function($id) use ($app){
			$app->response()->header("Content-Type", "application/json");
			$news = News::where('id',$id)->get()->first();
			if($news){
					if($news->delete() > 0){
						$result = [
							"status" => "success",
							"data" => json_decode($news)
						];
					}else{
							$result = [
								"status" => "error",
								"message" => "No record(s) to delete."
							];
					};
			}else{
				$result = [
					"status" => "error",
					"message" => "No record(s) to delete."
				];
			}

			echo json_encode($result);
	});


$app->run();
