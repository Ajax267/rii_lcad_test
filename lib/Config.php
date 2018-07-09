<?php

class Config{
    public $info = array();

    public function __construct($args){
        $argsv <- getArgv($args);
        readConfig($argsv['file']);
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
            }
        }
        return $args;
    }
}
?>