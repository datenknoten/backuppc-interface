<?php

require "vendor/autoload.php";

use React\SocketClient\TcpConnector;

function generateHash($seed,$count,$message) {
    $secret = "mXY5r5hAdci08y64RD8RriknnY66Ryo5";
    $parts = explode(",",$seed);
    $message_hash = $seed . $count . $secret . $message; 

    $hash = str_replace("=","",base64_encode(md5($message_hash,true)));
    return $hash;
}
$loop = React\EventLoop\Factory::create();

$tcpConnector = new TcpConnector($loop);


$i = 0;

$status_text = "";

$tcpConnector->create('192.168.122.1', 31211)->then(function (React\Stream\Stream $stream) use (&$status_text) {
    $command_count = 0;
    $stream->on('data',function($data) use (&$command_count,$stream,&$status_text){
        // var_dump($data);
        // var_dump($command_count);
        if ($command_count == 0) {
            $mesg = "status hosts jobs info server";
            $hash = generateHash($data,$command_count,$mesg);
            $message = sprintf("%s %s\n",$hash,$mesg);
            $stream->write($message);
            $command_count++;
        } else {
            $status_text .= $data;
            if (substr($status_text,strlen($status_text)-1,1) == "\n") {
                $stream->end();
            }
        }
    });
});

$loop->run();

$status_text = str_replace(['{','}','(',')','%','undef','*::FH'],['[',']','[',']','$','null','null'],$status_text);

eval($status_text);

header('Content-Type','application/json');

echo json_encode([
    'status' => $Status,
    'info' => $Info,
    'jobs' => $Jobs,
    'queue' => $Queue,
    'server' => $Server
]);