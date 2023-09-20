<?php
require_once ('vendor/autoload.php');
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;


 
$connection = new AMQPStreamConnection('localhost', 5672, 'guest','guest');
$channel = $connection->channel();
$response = null;
list($callback_queue, ,) = $channel->queue_declare("", false, false, true, false);

function call($n, $_channel, $_callback_queue, $_corr_id)
{
    global $response;
    
    $msg = new AMQPMessage(
         $n,
        array(
            'correlation_id' => $_corr_id,
            'reply_to' => $_callback_queue
        )
    );
    $_channel->basic_publish($msg, '', 'rpc_queue');
    echo " Message sent\n";

    while (!$response) {
        $_channel->wait();
    }
    return $response;
}


$corr_id = uniqid();
$resp = function ($rep)
    {
        global $corr_id, $response;
        if ($rep->get('correlation_id') == $corr_id) {
         $response = $rep->body;
    
        }
    };


$channel->basic_consume($callback_queue, '',false, true, false, false, $resp);



$content = file_get_contents($argv[1]);
$send = call($content, $channel, $callback_queue, $corr_id);
echo $send;
?>