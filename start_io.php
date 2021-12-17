<?php
use Workerman\Worker;
use Workerman\WebServer;
use Workerman\Autoloader;
use PHPSocketIO\SocketIO;

// composer autoload
require_once join(DIRECTORY_SEPARATOR, array(__DIR__, "vendor", "autoload.php"));

$io = new SocketIO(2020);
$io->on('connection', function($socket){
	log2stderr($socket, "new client connected");

    $socket->addedUser = false;
    // when the client emits 'new message', this listens and executes
    $socket->on('new message', function ($data)use($socket){
		log2stderr($socket, "new message: [$data]");

		// we tell the client to execute 'new message'
        $socket->broadcast->emit('new message', array(
            'username'=> $socket->username,
            'message'=> $data
        ));
    });

    // when the client emits 'add user', this listens and executes
    $socket->on('add user', function ($username) use($socket){
		if ($socket->addedUser) {
			log2stderr($socket, "user already joined: [$username]");
			return;
		} else {
			log2stderr($socket, "user joined: [$username]");

			global $usernames, $numUsers;
			// we store the username in the socket session for this client
			$socket->username = $username;
			++$numUsers;
			$socket->addedUser = true;
			$socket->emit('login', array( 
				'numUsers' => $numUsers
			));
			// echo globally (all clients) that a person has connected
			$socket->broadcast->emit('user joined', array(
				'username' => $socket->username,
				'numUsers' => $numUsers
			));
		}
    });

    // when the client emits 'typing', we broadcast it to others
    $socket->on('typing', function () use($socket) {
		//log2stderr($socket, "typing");
		
        $socket->broadcast->emit('typing', array(
            'username' => $socket->username
        ));
    });

    // when the client emits 'stop typing', we broadcast it to others
    $socket->on('stop typing', function () use($socket) {
		//log2stderr($socket, "stopped typing");

		$socket->broadcast->emit('stop typing', array(
            'username' => $socket->username
        ));
    });

    // when the user disconnects.. perform this
    $socket->on('disconnect', function () use($socket) {
		log2stderr($socket, "disconnected");

		global $usernames, $numUsers;
        if($socket->addedUser) {
            --$numUsers;

           // echo globally that this client has left
           $socket->broadcast->emit('user left', array(
               'username' => $socket->username,
               'numUsers' => $numUsers
            ));
        }
   });

});

if (!defined('GLOBAL_START')) {
    Worker::runAll();
}


function log2stderr($socket, $message, $channel = "error_log") {
    $prefix = "SOCKET_NULL ";
    if ($socket) {
        $client_id = $socket->client->id;
        $room_id = $socket->rooms[$client_id];
        $prefix = "room[$room_id] client[$client_id] ";
        if (isset($socket->username)) {
            $prefix .= "user[$socket->username] ";
        }
    }
    

    $out = $prefix . $message . "\n";
	if ($channel = "stderr") {
		// https://devcenter.heroku.com/articles/deploying-php
		file_put_contents("php://stderr", $out);
	} else {
		// https://www.php.net/manual/en/function.error-log.php
		error_log($out, 1);
	}
}

$error_log_file = ini_get("error_log");
if ($error_log_file) {
    echo "error_log[$error_log_file]";
}	
