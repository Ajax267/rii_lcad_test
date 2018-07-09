<?php

function obter_atividade_rubrica($l_tags)
{
    $consulta = obter_sql();
	//$MySQLi = new MySQLi($MySQL['servidor'], $MySQL['usuario'], $MySQL['senha'], $MySQL['banco']);
    $MySQLi = new MySQLi('localhost', 'master','James@123', 'moodle');
    $MySQLi->set_charset("utf8");
    $resultados = $MySQLi->query($consulta) OR trigger_error($MySQLi->error, E_USER_ERROR);
    $l_atividades = array();
    if($resultados){
		while ($atividade = $resultados->fetch_object()){
            //$nova_atividade <- formatar_atividade($atividade);
            //$nova_atividade->l_rubrica <- obter_rubrica($nova_atividade->id_atividade)
			array_push($l_atividades, $atividade);
		}
		$resultados->close();
    }
    return $l_atividades;
}

function fomatar_atividade($atividade)
{
    $nATV->course = "";
    $nATV->instanceid = "";
    $nATV->idnumber = "";
    $nATV->course_name = "";
    $nATV->idAssign = "";
    $nATV->nameAssign = "";
    $nATV->idnumber = "";
    return null;
}

function obter_sql()
{
    return "SELECT mdl_course.id AS course, mdl_course.shortname AS course_name, mdl_course_modules.id AS instanceid, mdl_assign.id AS idAssign, mdl_assign.name AS nameAssign, mdl_course_modules.idnumber AS idnumber FROM mdl_course INNER JOIN mdl_course_modules ON mdl_course_modules.module = 1 AND mdl_course_modules.course = mdl_course.id INNER JOIN mdl_assign ON mdl_course.id = mdl_assign.course AND mdl_course_modules.instance = mdl_assign.id WHERE NOT(mdl_course_modules.idnumber IS NULL OR mdl_course_modules.idnumber = '') AND mdl_course_modules.idnumber IN ('pessay-02');";
}

function obter_rubrica($id_submissa0)
{

    return null;
}

?>