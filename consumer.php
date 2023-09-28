<?php

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

// Configurações do RabbitMQ
$host = 'rabbitmq';
$port = 5672;
$user = 'guest';
$pass = 'guest';
$queueName = 'hello';

$connection = new AMQPStreamConnection($host, $port, $user, $pass);
$channel = $connection->channel();
$channel->queue_declare($queueName, false, true, false, false);

echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

$callback = function ($msg) {
    echo " [x] Host: " . getenv('HOSTNAME') . ". Received msg: ", $msg->body, "\n";

    try {
        $data = json_decode($msg->body, true);

        $cloud = $data['cloud'];
        $id_laudo = $data['id_laudo'];
        $path_temp = $data['path_temp'];
        $tipoExame = $data['tipoExame'];
        $id_clinica = $data['id_clinica'];
        $idPaciente = $data['idPaciente'];
        $iaExame = $data['iaExame'];
        $edit = $data['edit'];
        $token = $data['token'];
        $sistemaFila = $data['sistemaFila'];

        $client = new Client();
        $res = $client->post($sistemaFila, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => $token,
            ],
            'json' => [
                'cloud' => $cloud,
                'id_laudo' => $id_laudo,
                'path_temp' => $path_temp,
                'tipoExame' => $tipoExame,
                'id_clinica' => $id_clinica,
                'idPaciente' => $idPaciente,
                'iaExame' => $iaExame,
                'edit' => $edit,
            ],
        ]);

        $result = json_decode($res->getBody()->getContents() ?? [], true);

        echo " [x] Success: ", "\n";
        echo " [x] Data: " . $res->getBody()->getContents(), "\n";
    } catch (RequestException $e) {
        echo " [x] Request Error: " . $e->getMessage(), "\n";
    } catch (\Exception $e) {
        echo " [x] Error: " . $e->getMessage(), "\n";
    }

    echo " [x] Done", "\n";

    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume($queueName, '', false, false, false, false, $callback);

while (count($channel->callbacks)) {
    $channel->wait();
}
