<?php

namespace MatviiB\Notifier;

class SocketServer
{
    /**
     * Host for socket channel.
     *
     * @var
     */
    public $host;

    /**
     * Port for socket channel.
     *
     * @var
     */
    public $port;

    /**
     * Settings constructor.
     */
    public function __construct()
    {
        $this->host = config('notifier.host');
        $this->port = config('notifier.port');
    }

    /**
     * Init socket server
     *
     * @return resource
     * @throws \Exception
     */
    public function init()
    {
        $socket = stream_socket_server("tcp://" . $this->host . ":" . $this->port, $err_no, $err_str);

        if (!$socket) {
            throw new \Exception('Socket server error', ['error' => "$err_str ($err_no)"]);
        }

        return $socket;
    }

    /**
     * Get headers message with given $data.
     *
     * @param $data
     * @param $routes
     * @param $users
     * @return string
     */
    public function getMessage($data, $routes = false, $users = false)
    {
        $request = "GET / HTTP/1.1\r\n" .
            "Host: " . $this->host . ":" . $this->port ."\r\n" .
            "Connection: Upgrade\r\n" .
            "Pragma: no-cache\r\n" .
            "Cache-Control: no-cache\r\n" .
            "Sec-WebSocket-Key: " . $this->getCode() . "\r\n" .
            "Socket-pass: ". config('notifier.socket_pass') . "\r\n" .
            "Payload: " . json_encode(['data' => $data]);

        if (isset($routes) && is_array($routes) && count($routes)) {
            $request .= "\r\n" . "Routes: " . json_encode($routes);
        }

        if (isset($users) && is_array($users) && count($users)) {
            $request .= "\r\n" . "Users: " . json_encode($users);
        }

        return $request;
    }

    /**
     * Accept (Upgrade) socket connection request.
     *
     * @param $sec_web_socket_key
     * @return string
     */
    public function accept($sec_web_socket_key)
    {
        $sec_web_socket_accept = $this->getCode($sec_web_socket_key);

        return "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Accept:" . $sec_web_socket_accept . "\r\n\r\n";
    }

    /**
     * Get code for validate socket connection.
     *
     * @param $sec_web_socket_key
     * @return string
     */
    protected function getCode($sec_web_socket_key = false)
    {
        if ($sec_web_socket_key) {
            return $this->hash($sec_web_socket_key);
        }

        return $this->hash(str_random(16));
    }

    /**
     * Generate hash for socket key accepting.
     *
     * @param $key
     * @return string
     */
    private function hash($key)
    {
        $const_hash = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

        return base64_encode(pack('H*', sha1($key . $const_hash)));
    }


    /**
     * Handshake - connection validate
     *
     * @param $connect
     * @return array|bool
     */
    public function handshake($connect)
    {
        $info = array();

        $line = fgets($connect);
        $header = explode(' ', $line);

        $info['method'] = $header[0];
        $info['uri'] = $header[1];

        while ($line = rtrim(fgets($connect))) {
            if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
                $info[$matches[1]] = $matches[2];
            } else {
                break;
            }
        }

        $address = explode(':', stream_socket_get_name($connect, true));

        $info['ip'] = $address[0];
        $info['port'] = $address[1];

        if (empty($info['Sec-WebSocket-Key'])) {
            return false;
        }

        fwrite($connect, $this->accept($info['Sec-WebSocket-Key']));

        return $info;
    }

    /**
     * Encode socket message
     *
     * @param $payload
     * @param string $type
     * @param bool $masked
     * @return string
     */
    public function encode($payload, $type = 'text', $masked = false)
    {
        $frameHead = array();
        $payloadLength = strlen($payload);

        switch ($type) {
            case 'text':
                // first byte indicates FIN, Text-Frame (10000001):
                $frameHead[0] = 129;
                break;

            case 'close':
                // first byte indicates FIN, Close Frame(10001000):
                $frameHead[0] = 136;
                break;

            case 'ping':
                // first byte indicates FIN, Ping frame (10001001):
                $frameHead[0] = 137;
                break;

            case 'pong':
                // first byte indicates FIN, Pong frame (10001010):
                $frameHead[0] = 138;
                break;
        }

        // set mask and payload length (using 1, 3 or 9 bytes)
        if ($payloadLength > 65535) {
            $payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 255 : 127;
            for ($i = 0; $i < 8; $i++) {
                $frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
            }
            // most significant bit MUST be 0
            if ($frameHead[2] > 127) {
                return array('type' => '', 'payload' => '', 'error' => 'frame too large (1004)');
            }
        } elseif ($payloadLength > 125) {
            $payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 254 : 126;
            $frameHead[2] = bindec($payloadLengthBin[0]);
            $frameHead[3] = bindec($payloadLengthBin[1]);
        } else {
            $frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
        }

        // convert frame-head to string:
        foreach (array_keys($frameHead) as $i) {
            $frameHead[$i] = chr($frameHead[$i]);
        }
        if ($masked === true) {
            // generate a random mask:
            $mask = array();
            for ($i = 0; $i < 4; $i++) {
                $mask[$i] = chr(rand(0, 255));
            }

            $frameHead = array_merge($frameHead, $mask);
        }
        $frame = implode('', $frameHead);

        // append payload to frame:
        for ($i = 0; $i < $payloadLength; $i++) {
            $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
        }

        return $frame;
    }

    /**
     * Decode socket message
     *
     * @param $data
     * @return array|bool
     */
    public function decode($data)
    {
        $unmaskedPayload = '';
        $decodedData = array();

        // estimate frame type:
        $firstByteBinary = sprintf('%08b', ord($data[0]));
        $secondByteBinary = sprintf('%08b', ord($data[1]));
        $opcode = bindec(substr($firstByteBinary, 4, 4));
        $isMasked = ($secondByteBinary[0] == '1') ? true : false;
        $payloadLength = ord($data[1]) & 127;

        // unmasked frame is received:
        if (!$isMasked) {
            return array('type' => '', 'payload' => '', 'error' => 'protocol error (1002)');
        }

        switch ($opcode) {
            // text frame:
            case 1:
                $decodedData['type'] = 'text';
                break;

            case 2:
                $decodedData['type'] = 'binary';
                break;

            // connection close frame:
            case 8:
                $decodedData['type'] = 'close';
                break;

            // ping frame:
            case 9:
                $decodedData['type'] = 'ping';
                break;

            // pong frame:
            case 10:
                $decodedData['type'] = 'pong';
                break;

            default:
                return array('type' => '', 'payload' => '', 'error' => 'unknown opcode (1003)');
        }

        if ($payloadLength === 126) {
            $mask = substr($data, 4, 4);
            $payloadOffset = 8;
            $dataLength = bindec(sprintf('%08b', ord($data[2])) . sprintf('%08b', ord($data[3]))) + $payloadOffset;
        } elseif ($payloadLength === 127) {
            $mask = substr($data, 10, 4);
            $payloadOffset = 14;
            $tmp = '';
            for ($i = 0; $i < 8; $i++) {
                $tmp .= sprintf('%08b', ord($data[$i + 2]));
            }
            $dataLength = bindec($tmp) + $payloadOffset;
            unset($tmp);
        } else {
            $mask = substr($data, 2, 4);
            $payloadOffset = 6;
            $dataLength = $payloadLength + $payloadOffset;
        }

        /**
         * We have to check for large frames here. socket_recv cuts at 1024 bytes
         * so if websocket-frame is > 1024 bytes we have to wait until whole
         * data is transferd.
         */
        if (strlen($data) < $dataLength) {
            return false;
        }

        if ($isMasked) {
            for ($i = $payloadOffset; $i < $dataLength; $i++) {
                $j = $i - $payloadOffset;
                if (isset($data[$i])) {
                    $unmaskedPayload .= $data[$i] ^ $mask[$j % 4];
                }
            }
            $decodedData['payload'] = $unmaskedPayload;
        } else {
            $payloadOffset = $payloadOffset - 4;
            $decodedData['payload'] = substr($data, $payloadOffset);
        }

        return $decodedData;
    }

    /**
     * Action when new connection established
     *
     * @param $connect
     * @param bool $info
     * @return void
     */
    public function onOpen($connect, $info = false)
    {
//        echo "open" . PHP_EOL;
    }

    /**
     * Action when connection was closed
     *
     * @param $connect
     * @return void
     */
    public function onClose($connect)
    {
//        echo "close" . PHP_EOL;
    }

    /**
     * Action when message recieved
     *
     * @param $connect
     * @param $data
     * @return void
     */
    public function onMessage($connect, $data)
    {
//        echo $this->decode($data)['payload'] . PHP_EOL;
    }
}