<?php
$address = '127.0.0.1';
$port = 80;
$null = NULL;
$read = NULL;
$except = NULL;
$server = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP); 
@socket_set_option($server, SOL_SOCKET, SO_REUSEADDR, 1);
@socket_set_option($server, SOL_SOCKET, SO_BROADCAST, 1);
@socket_bind($server, $address, $port);
@socket_listen($server);  //socket_listen ($sock , 10) it means that if 10 connections are already waiting to be processed, then the 11th connection request shall be rejected

$clients = array($server);
$connection = 1;
while (true) {
    $changed = $clients;
    @socket_select($changed, $read, $except, 10, 0);
    if (in_array($server, $changed)) {
        $newSocket = @socket_accept($server);
        $header = @socket_read($newSocket, 5000);
        array_push($clients, $newSocket);
        if (!empty($header)){
            // Send WebSocket handshake headers.
            preg_match('#Sec-WebSocket-Key: (.*)\r\n#', $header, $matches);
            $key = base64_encode(pack(
                'H*',
                sha1($matches[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')
            ));
            $headers = "HTTP/1.1 101 Switching Protocols\r\n";
            $headers .= "Upgrade: websocket\r\n";
            $headers .= "Connection: Upgrade\r\n";
            $headers .= "Sec-WebSocket-Version: 13\r\n";
            $headers .= "Sec-WebSocket-Accept: $key\r\n\r\n";
            @socket_write($newSocket, $headers, strlen($headers)); 
        }
        //send msg to client
            @socket_getpeername($newSocket, $address);
            $response = mask(json_encode(array('type' => 'client', 'message' => $address . ' connected', 'data' => 'you no '.$connection)));
            //send success msg
            @socket_write($newSocket, $response, strlen($response)); //single connection data send
            echo "Client $address : $port with connection no: ".$connection." is now connected to us.\n\n";
        //received msg from client
            while($rd = @socket_read($newSocket, 5000)){
                printf("Message from client:%s\n", unmask($rd));
            }
    }
    $connection++;
}

function mask($text) {
    $b1 = 0x80 | (0x1 & 0x0f);
    $length = strlen($text);

    if ($length <= 125)
        $header = pack('CC', $b1, $length);
    elseif ($length > 125 && $length < 65536)
        $header = pack('CCn', $b1, 126, $length);
    elseif ($length >= 65536)
        $header = pack('CCNN', $b1, 127, $length);
    return $header . $text;
}

function unmask($text) {
    $length = ord($text[1]) & 127;
    if ($length == 126) {
        $masks = substr($text, 4, 4);
        $data = substr($text, 8);
    } elseif ($length == 127) {
        $masks = substr($text, 10, 4);
        $data = substr($text, 14);
    } else {
        $masks = substr($text, 2, 4);
        $data = substr($text, 6);
    }
    $text = "";
    for ($i = 0; $i < strlen($data); ++$i) {
        $text .= $data[$i] ^ $masks[$i % 4];
    }
    return $text;
}
//send to all
function send_message($msg) {
    global $clients;
    foreach ($clients as $changed_socket) {
        @socket_write($changed_socket, $msg, strlen($msg));
    }
    return true;
}
@socket_shutdown($server, 2);
@socket_close($server);
//server start by -----   php -q server.php
