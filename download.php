<?php
include 'lib/Client.php';
header('Content-type: application/json');
$args = getArgv($argv);
$client = new Client($args['conf']);

if($args['rubric']){
	$client->setRubric();
}

#print_r($client->getInfo());
$curl = curl_init();
curl_setopt_array($curl, array(
		CURLOPT_URL => $client->getWSurl(),
		CURLOPT_POST => 1,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_POSTFIELDS => array(
			'action' => 'anexos',
			'data' => serialize($client->getInfo())
		)
));
$result = curl_exec($curl);
curl_close($curl);
$result = json_decode($result);

$names = $result->{'users'};
$data = $result->{'anexos'};

$start = microtime();
$array = $client->normalize_array($data,$client->getURL(),$client->getToken());
$end = microtime();

printf("Tempo de organização: %e s\n",($end-$start)/100000);

if($names){
	$array = $client->assingName($names,$array);
}

$start = time();
$client->mount_directories($array,$args['path']);
$end = time();
printf("Tempo de download: %e s\n",($end-$start));

$fp = fopen($args['path'].'moodle.json','w');
fwrite($fp,json_encode($array,JSON_PRETTY_PRINT));
fclose($fp);
?>
