<?php
include 'lib/Client.php';

header('Content-type: application/json');


$args = getArgv($argv);

$client = new Client($args['conf']);
print_r($args);

if($args['file']){
	$json_grades = $client->read_grades_file($args['file']);
}else if($args['path']){
	$json_grades = 	$client->read_grades_path($args['path']);
}
print_r($json_grades);
echo "\n";



$curl = curl_init();
curl_setopt_array($curl, array(
		CURLOPT_URL => $client->getWSurl(),
		CURLOPT_POST => 1,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_POSTFIELDS => array(
			'action' => 'upload',
			'grades' => serialize($json_grades),
			'info' =>serialize($client->getInfo())
		)
));
$result = curl_exec($curl);
curl_close($curl);

print_r($result);
exit;

/*
$result = $client_connection->atualizaQuestoes($json_grades);


if (is_soap_fault($result)) {
	echo "ERROR";
}else{
	echo $result."\n";
}
*/
?>
