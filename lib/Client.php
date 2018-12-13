<?php

function findUser($names,$id){
	$tam = count($names);
	foreach ($names as $key) {
		if($id == $key->{'id'}){
			return $key;
		}
	}

}

function getArgv($_argv){
	$args = [];
	for ($i=0; $i < count($_argv); $i++) {
		if ($_argv[$i] == "-f") {
			$i++;
			$args['file'] = $_argv[$i];
		}else if($_argv[$i] == "-d"){
			$i++;
			$args['path'] = $_argv[$i];
		}else if($_argv[$i] == "--conf"){
			$i++;
			$args['conf'] = $_argv[$i];
		}else if($_argv[$i] == "-r"){
			$args['rubric'] = true;
		}
	}
	return $args;

}

class Client
{

	public function __construct($file){
		$this->readConfig($file);
		$this->Rubric = false;
	}

	public $info = array();
	//Baixa as submissões dos alunos
	public function download_remote_file($source, $save_to){

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $source);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSLVERSION,3);
		$data = curl_exec ($ch);
		$error = curl_error($ch);
		curl_close ($ch);
		$destination = $save_to;
		$file = fopen($destination, "w+");
		fputs($file, $data);
		fclose($file);
	}

	//Normaliza o vetor do resultado da busca
	public function normalize_array($anexos,$dominioMoodle,$tokenMoodle){
		
		
		$anexos = $this->filterTags($anexos);
		$courses = array();	//Vetor de cursos
		$tasks = array();	//Vetor de tarefas
		$users = array();	//Vetor de alunos
		$moodle = array();	//Vetor final


		//Verifico e separar as informacoes
		foreach ($anexos as $key) {
		 	foreach($key as $index => $values){
				if(strcasecmp($index,"course") == 0 and !in_array($values,$courses)){
					array_push($courses,$values);
				}elseif(strcasecmp($index,"instanceid") == 0 and !in_array($values,$tasks)){
	        array_push($tasks,$values);
	      }elseif(strcasecmp($index,"userid") == 0 and !in_array($values,$users)){
	      	array_push($users,$values);
	      }
			}
		}

		//Filtro de curso, atividade e aluno
		$aux_couser = array();
		foreach ($courses as $course) {
			$course_aux = array();
			foreach ($tasks as $task) {
				$task_aux = array();
				foreach ($anexos as $obj) {
				$aux = get_object_vars($obj);
					if(strcasecmp($aux['course'],$course) == 0 and
					 	strcasecmp($aux['instanceid'],$task) == 0 and
					  !in_array($aux['instanceid'],$course_aux) and
						!in_array($aux['userid'],$task_aux)){
						array_push($task_aux, $aux['userid']);
					}
				}
					if (!is_null($task_aux) and count($task_aux) > 0) {
						$course_aux[$task] = $task_aux;
				}
			}

			if (!is_null($course_aux) and count($course_aux) > 0) {
				$aux_couser[$course] = $course_aux;
			}

		}

		$moodle = array();
		$courses = $aux_couser;
		unset($aux_couser);
		unset($aux_task);
		unset($aux_user);
		foreach ($courses as $course => $instances) {
			$aux_couser = array();
			foreach ($instances as $instanceid => $users) {
				$aux_task = array();
				foreach ($users as $user) {
					$aux_user = array();
					$aux_temp = array('array' => $anexos,
						'course' => $course,
						'instanceid' => $instanceid,
						'userid' => $user,
						'url' => $dominioMoodle,
						'token' => $tokenMoodle);

					//Procuro se o usuario fez a atividade do curso

					$user_result = $this->user_files($aux_temp);
					if (!is_null($user_result) and count($user_result) > 0) {
						foreach ($user_result as $x) {
							array_push($aux_user,$x['item']);
							unset($anexos[$x['index']]);
						}
					}
					if (count($aux_user) > 0) {
						$aux_task[$user] = $aux_user;
					}
				}
				if (count($aux_task) > 0) {
					$aux_couser[$instanceid] = $aux_task;
				}
			}
			if (count($aux_couser) > 0) {
				$moodle[$course] = $aux_couser;
			}
		}
		return json_encode(array('moodle'=>$moodle));
	}

	//Monta o diretório de cada submissão
	public function mount_directories($array_moodle,$client_path){
		$json_moodle = $array_moodle;
		foreach ($json_moodle as $course) {
			foreach ($course as $task) {
				foreach ($task as $user) {
					$array_info = array();
					foreach ($user as $submssions) {
						$user_info = $submssions[0];
						$user_info = get_object_vars($user_info);
						$path_notasTreino = $client_path.$user_info['course_name']."-".$user_info['idnumber']."-".$user_info['instanceid']."/notastreino.csv";
						$temp_content = $user_info['userid']."-".$user_info['id_grade_grades'].";".number_format(floatval($user_info['notaProfessor']),2)."\n";
						$intervalo = number_format(floatval($user_info['rawgrademin']),2)."-".number_format(floatval($user_info['rawgrademax']),2);
						array_push($array_info, $temp_content);
						foreach ($submssions as $obj) {
							$item = get_object_vars($obj);
							$path = $client_path.$item['course_name']."-".$item['idnumber']."-".$item['instanceid']."/".$item['userid']."-".$item['id_grade_grades'];
							$file = $path."/".$item['filename'];
							if ( !file_exists($path)) {
								mkdir($path,0777,true);
							}
							if (!strlen($item['resposta']) == 0) {
								$file_answer = fopen("$path/resposta.txt", "w");
								fwrite($file_answer, print_r($item['resposta'],true));
								fclose($file_answer);
							}
							if(!strlen($item['filename']) == 0){
								$item['url'] = str_replace(" ","%20",$item['url']);
								$this->download_remote_file($item['url'], $file);
							}
							if(!strlen($item['name']) == 0){
								$file_name = fopen("$path/name.info", "w");
								fwrite($file_name, print_r($item['name'],true));
								fclose($file_name);
							}
							if($this->ehRubric()){
								$curl = curl_init();
								curl_setopt_array($curl, array(
										CURLOPT_URL => $this->getWSurl(),
										CURLOPT_POST => 1,
										CURLOPT_RETURNTRANSFER => 1,
										CURLOPT_POSTFIELDS => array(
											'action' => 'rubric',
											'userinfo' => serialize(array('course' => $item['course'], 'id_submissao' => $item['id_submissao'], "userid" => $item['userid'])),
											'info' => serialize($this->getInfo())
										)
								));
								$result = curl_exec($curl);
								curl_close($curl);
								$result = json_decode($result);
								$file_name = fopen("$path/rubric.csv", "w");
								foreach($result->rubrics as $rubrica){
									$data[0] = $rubrica->rubric_id;
									$data[1] = $rubrica->nota_criterio;
									fputcsv($file_name, $data,";");
								}
								fclose($file_name);
							}
						}
					}
					$file_notasTreino = fopen($path_notasTreino, "w");
					foreach ($array_info as $info) {
						fwrite($file_notasTreino, $info);
					}
					fclose($file_notasTreino);
					$path_intervalonota = $client_path.$user_info['course_name']."-".$user_info['idnumber']."-".$user_info['instanceid']."/intervalonotas.csv";

					$file_intervalonota = fopen($path_intervalonota, "w");
					fwrite($file_intervalonota, $intervalo);
					fclose($file_intervalonota);
				}
			}
		}
	}

	//Procura a submissão certa
	public function user_files($array){
		$user_submitions = array();
		foreach ($array['array'] as $key => $obj){
			$item = get_object_vars($obj);
			if ( strcmp($item['userid'],$array['userid']) == 0 and strcmp($item['instanceid'],$array['instanceid']) == 0 and strcmp($item['userid'],$array['userid']) == 0){
				if (strlen($item['filename']) > 0) {
					$item['url'] = $array['url']."/webservice/pluginfile.php/".$item['contextid']."/assignsubmission_file/submission_files/".$item['itemid']."/".$item['filename']."?forcedownload=1&token=".$array['token'];
				}
			    $aux= array('index' => $key,'item' => $item );
					if(strlen($item['resposta']) > 0 or strlen($item['filename']) > 0){
							array_push($user_submitions, $aux);
					}
			}
	   	}
	   	return $user_submitions;
	}

	//Upload das notas pro servidor
	public function read_grades_path($path){
		$found_files = glob($path.'*/notastreino.csv');
		$grades_array = [];
		foreach ($found_files as $path_file) {

			$file = fopen($path_file, "r");
			while(! feof($file)){
				$aux = [];
				$line = fgets($file);
				$line = str_replace("\n","",$line);
				if(!strlen($line) == 0){
					$line = explode(";", $line);
					if(!array_key_exists(2,$line)){
						$line[2] = " ";
					}
					$grade = explode("-", $line[0]);

					if($line[2] == ' ' || $line[2] == ''){
						$aux = array('id_grade_grades' => $grade[1],'nota' => $line[1],'feedback' => NULL);
					}else{
						$aux = array('id_grade_grades' => $grade[1],'nota' => $line[1],'feedback' => $line[2]);
					}

					array_push($grades_array,$aux);
				}
  			}
			fclose($file);
		}
		return json_encode($grades_array);
	}

	public function read_grades_file($pathfile){
		$file = fopen($pathfile, "r");
		$grades_array = [];
		while(! feof($file)){

			$line = fgets($file);
			$line = str_replace("\n","",$line);
			if(!strlen($line) == 0){
				$line = explode(";", $line);
				if(!array_key_exists(2,$line)){
					$line[2] = " ";
				}
				$grade = explode("-", $line[0]);

				if($line[2] == ' ' || $line[2] == ''){
					$aux = array('id_grade_grades' => $grade[1],'nota' => (float)$line[1],'feedback' => NULL);
				}else{
					$aux = array('id_grade_grades' => $grade[1],'nota' => (float)$line[1],'feedback' => $line[2]);
				}

				array_push($grades_array,$aux);
			}
		}
		fclose($file);
		return json_encode($grades_array);
	}

	//Associar os nomes com as submissoes
	public function assingName($names,$moodle){
		$moodle = json_decode($moodle);
		foreach ($moodle as $courses) {
			foreach ($courses as $couser => $instances) {
				foreach ($instances as $instancesid => $users) {
					foreach ($users as $user => $userInstance) {
						foreach ($userInstance as $key) {
							$id = findUser($names, $key->{'userid'});
							$key->{'name'} = $id->{'firstname'}." ". $id->{'lastname'};
						}
					}
				}

			}
		}
		return $moodle;
	}

	//Ler o arquivo de configurações
	private function readConfig($confPath){
		$fileConf = fopen($confPath,"r") or die ("Erro ao abrir arquivo de configurações");
		while(!feof($fileConf)){
			$data  = fgets($fileConf);
			if(feof($fileConf)){
				break;
			}
			$data = explode("=",$data,2);
			$this->info[$data[0]] = str_replace("\n","",$data[1]);
		}
		
		fclose($fileConf);
	}

	//Filtrar os tipos de atividades
	public function filterTags($anexos){
		$this->info['tags'] = trim($this->info['tags']);		
		$aux = [];
		if(empty($this->info['tags'])){
			$aux = $anexos;			
		}else{
			$this->info['tags'] = explode(";",$this->info['tags']);
			foreach ($anexos as $key) {
				foreach($key as $index => $values){
					if(strcasecmp($index,"idnumber") == 0){
						foreach ($this->info['tags'] as $id) {						
							if(strcasecmp($id,"NULL") == 0){
								if(strlen($values) == 0){
									array_push($aux,$key);
								}
							}else{
								if(strpos($values,$id) !== false){
									array_push($aux,$key);
							   }
							}
							
						}
					}  
   
			   }
		   }
		}
		
		return $aux;
	}

	public function ehRubric()
	{
		return $this->Rubric;
	}


	public function setRubric(){
		echo "Rubrica sera baixada!";
		$this->Rubric = true;
	}


	public function getInfo(){
		return array(
			'banco' => $this->info['banco'],
			'servidor'  => $this->info['servidor'],
			'senha' => $this->info['senha'],
			'usuario' => $this->info['usuario'],
			'tags' => $this->info['tags']
		);
	}

	public function getWSurl(){
		return $this->info["ws-url"];
	}

	public function getURL(){
		return $this->info["url"];
	}
	public function getToken(){
		return $this->info["token"];
	}

	public function assinFeedback($feedbacks,$moodle){
		$moodle = json_decode($moodle);
		foreach ($moodle as $courses) {
			foreach ($courses as $couser => $instances) {
				foreach ($instances as $instancesid => $users) {
					foreach ($users as $user => $userInstance) {
						foreach ($userInstance as $key) {
							$id = findUser($names, $key->{'userid'});
							$key->{'name'} = $id->{'firstname'}." ". $id->{'lastname'};
						}
					}
				}

			}
		}
		return $moodle;
	}

}


?>
