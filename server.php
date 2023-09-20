<?php

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('rpc_queue', false, false, false, false);

function write_to_file($n)
{
    file_put_contents('text.txt', $n, FILE_APPEND | LOCK_EX);

    return "succesfully write in file\n";
}

echo " Awaiting RPC requests\n";
$callback = function ($req) {
    $content = $req->body;
    echo "Message received\n";

    $msg = new AMQPMessage(
        write_to_file($content),
        array('correlation_id' => $req->get('correlation_id'))
    );

    $req->getChannel()->basic_publish(
        $msg,
        '',
        $req->get('reply_to')
    );
    $req->ack();
    echo "Answer sent\n";
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume('rpc_queue', '', false, false, false, false, $callback);

while ($channel->is_open()) {
    $channel->wait();
}

$channel->close();
$connection->close();