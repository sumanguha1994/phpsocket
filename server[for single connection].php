<?php
$address = '127.0.0.1';
$port = 80;

// Create WebSocket.
$server = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);  //create socket
@socket_set_option($server, SOL_SOCKET, SO_REUSEADDR, 1); //set option
@socket_bind($server, $address, $port);                   //address & port binding with socket create
@socket_listen($server);                                  //listening for ack
$client = @socket_accept($server);                        //ack sccept

// Send WebSocket handshake headers.
$request = @socket_read($client, 5000);                   //ack request read & do handshaking
preg_match('#Sec-WebSocket-Key: (.*)\r\n#', $request, $matches);
$key = base64_encode(pack(
    'H*',
    sha1($matches[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')
));
$headers = "HTTP/1.1 101 Switching Protocols\r\n";
$headers .= "Upgrade: websocket\r\n";
$headers .= "Connection: Upgrade\r\n";
$headers .= "Sec-WebSocket-Version: 13\r\n";
$headers .= "Sec-WebSocket-Accept: $key\r\n\r\n";
@socket_write($client, $headers, strlen($headers));       //handshaking done and chnage tcp/ip protocol to 101

// Send messages into WebSocket in a loop.
$count = 1;
while (true) {
    @sleep(1);
    $content = 'Now: ' . $count;
    $response = chr(129) . chr(strlen($content)) . $content;
    @socket_write($client, $response);                    //write permision & send response
    $count++;
}