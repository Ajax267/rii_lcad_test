<?php
function hello($name){
	return "Hello $name\n";
}
//function user_files($array, $field1, $value1,$field2,$value2,$url,$token)
function user_files($array, $field1, $value1,$field2,$value2)
{
	$user_submitions = array();
	foreach ($array as $key => $obj){
		$item = get_object_vars($obj);
   		if ( $item[$field1] === $value1 and $item[$field2] === $value2){
   			//$item['url'] = $url."/webservice/pluginfile.php/".$item['contextid']."/assignsubmission_file/submission_files/".$item['itemid']."/".$item['filename']."?forcedownload=1&token=".$token;
		    $aux= array('index' => $key,'item' => $item );
		    array_push($user_submitions, $aux);
		}
   	}
   	return $user_submitions;
}

//function result_query($dominioMoodle,$tokenMoodle){
function result_query($MySQL){

	$sql = "SELECT mdl_course.id AS course,
				mdl_course_modules.id AS instanceid,
				mdl_grade_grades.userid,
				IFNULL(files_user.contextid, 0) AS contextid,
				IFNULL(files_user.itemid, 0) AS itemid,
				IFNULL(files_user.filename, '') AS filename,
				mdl_grade_grades.rawgrademin,
				mdl_grade_grades.rawgrademax,
				mdl_grade_grades.id AS id_grade_grades,
				mdl_course_modules.idnumber AS idnumber,
				IFNULL(
						(SELECT mdl_grade_grades_professor.finalGRADE
						FROM mdl_grade_grades AS mdl_grade_grades_professor, mdl_user
						WHERE mdl_grade_grades_professor.ID = mdl_grade_grades.id
							AND mdl_grade_grades.usermodified = mdl_user.id
							AND mdl_user.username <> 'soap'),-1) AS notaProfessor,
				mdl_course.shortname AS course_name,
				mdl_assignsubmission_onlinetext.onlinetext AS resposta,
				mdl_course.shortname AS course_name,
				mdl_assignsubmission_onlinetext.onlinetext AS resposta,
				mdl_assign_submission.id AS id_submissao
			FROM mdl_course
			INNER JOIN mdl_course_modules ON mdl_course_modules.module = 1
			AND mdl_course_modules.course = mdl_course.id
			INNER JOIN mdl_assign ON mdl_course.id = mdl_assign.course
			AND mdl_course_modules.instance = mdl_assign.id
			INNER JOIN mdl_assign_submission ON mdl_assign.id = mdl_assign_submission.assignment
			LEFT JOIN mdl_assignsubmission_onlinetext ON mdl_assign_submission.id = mdl_assignsubmission_onlinetext.submission
			LEFT JOIN mdl_modules ON mdl_modules.id = mdl_course_modules.module
			INNER JOIN mdl_grade_items ON mdl_assign.id = mdl_grade_items.iteminstance
			AND mdl_modules.name = mdl_grade_items.itemmodule
			INNER JOIN mdl_grade_grades ON mdl_grade_items.id = mdl_grade_grades.itemid
			AND mdl_assign_submission.userid = mdl_grade_grades.userid
			LEFT JOIN
			(SELECT mdl_files.userid,
				mdl_files.contextid,
				mdl_files.itemid,
				mdl_files.filename,
				mdl_context.instanceid
			FROM mdl_context
			INNER JOIN mdl_files ON mdl_files.contextid = mdl_context.id
			AND mdl_files.component = 'assignsubmission_file'
			AND mdl_files.filesize > 0) AS files_user ON files_user.userid = mdl_grade_grades.userid
			AND files_user.instanceid = mdl_course_modules.id
			WHERE not(mdl_course_modules.idnumber IS NULL
				OR mdl_course_modules.idnumber = '')
			ORDER BY 1,2,3";

	$sqlFeed = "SELECT * FROM mdl_assignfeedback_comments";
	$sqlNames = "SELECT mdl_user.id, mdl_user.firstname, mdl_user.lastname FROM mdl_user";

	$MySQLi = new MySQLi($MySQL['servidor'], $MySQL['usuario'], $MySQL['senha'], $MySQL['banco']);


	// Verifica se ocorreu um erro e exibe a mensagem de erro
	if (mysqli_connect_errno())
		trigger_error(mysqli_connect_error(), E_USER_ERROR);


	// Fim do codigo de configuracao DB

	// Executa a consulta OU mostra uma mensagem de erro
	$MySQLi->set_charset("utf8");
	$result_data = $MySQLi->query($sql) OR trigger_error($MySQLi->error, E_USER_ERROR);
	$result_names =  $MySQLi->query($sqlNames) OR trigger_error($MySQLi->error, E_USER_ERROR);
	$result_feedbacks = $MySQLi->query($sqlFeed) OR trigger_error($MySQLi->error, E_USER_ERROR);


	if($result_data){
		// Cycle through results
		while ($anexo = $result_data->fetch_object()){
			$anexos[] = $anexo;
		}
		// Free result set
		$result_data->close();
		}

		if($result_names){
		while ($name = $result_names->fetch_object()){
			$names[] = $name;
		}
		// Free result set
		$result_names->close();
		}

		if($result_feedbacks){
		while ($feedback = $result_feedbacks->fetch_object()){
			$feedbacks[] = $feedback;
		}
		// Free result set
		$result_feedbacks->close();
		}



		header('Content-type: application/json');
		$MySQLi->close();

		//return json_encode(array('anexos' => $anexos, 'feedbacks' => $feedbacks));
		return json_encode(array('anexos' => $anexos,'users' => $names,'feedbacks' => $feedbacks));
		//return "OK";

}

function atualizaQuestoes($sql,$questoesJson)
{
	$input = json_decode($questoesJson);
 	// para cada questao

	for ($i = 0; $i < count($input); $i++)
	{
		atualizaQuestao($sql,$input[$i]->id_grade_grades, $input[$i]->nota, 0, $input[$i]->feedback);
	}
	return "OK";
}

function atualizaQuestao ($MySQL,$id, $nota, $professor, $feedback)
{
	$MySQLi = new MySQLi($MySQL['servidor'], $MySQL['usuario'], $MySQL['senha'], $MySQL['banco']);

	if ($nota >= 0)
	{
		if(is_null($feedback)){
			echo "$id sem feedback\n";
			$query = "UPDATE mdl_grade_grades SET RAWGRADE = '".$MySQLi->real_escape_string($nota)."'
			,finalgrade = '".$MySQLi->real_escape_string($nota)."'
			,timemodified = unix_timestamp(now())
			,usermodified = (select id from mdl_user where username = 'wsmoodle')
			WHERE ID = '".$MySQLi->real_escape_string($id)."'
			AND (usermodified not in (SELECT mdl_role_assignments.userid FROM mdl_role_assignments, mdl_context, mdl_course, mdl_grade_items WHERE mdl_context.contextlevel = 50
									AND mdl_role_assignments.contextid = mdl_context.id
									AND mdl_context.instanceid = mdl_course.id
									AND mdl_course.id = mdl_grade_items.courseid
									AND mdl_role_assignments.roleid in (3,4)
									AND mdl_grade_items.id = mdl_grade_grades.itemid)
				OR rawgrade is null)";

		}else{
			echo "$id com feedback\n";
			$query = "UPDATE mdl_grade_grades SET RAWGRADE = '".$MySQLi->real_escape_string($nota)."'
				,finalgrade = '".$MySQLi->real_escape_string($nota)."'
				,feedback = '".$MySQLi->real_escape_string($feedback)."'
				,feedbackformat = 1
				,timemodified = unix_timestamp(now())
				,usermodified = (select id from mdl_user where username = 'wsmoodle')
				WHERE ID = '".$MySQLi->real_escape_string($id)."'
				AND (usermodified not in (SELECT mdl_role_assignments.userid FROM mdl_role_assignments, mdl_context, mdl_course, mdl_grade_items WHERE mdl_context.contextlevel = 50
										AND mdl_role_assignments.contextid = mdl_context.id
										AND mdl_context.instanceid = mdl_course.id
										AND mdl_course.id = mdl_grade_items.courseid
										AND mdl_role_assignments.roleid in (3,4)
										AND mdl_grade_items.id = mdl_grade_grades.itemid)
					OR rawgrade is null)";

		}


	}else{
		if(!is_null($feedback)){
			echo "$id Feedback sem nota\n";
			$query = "UPDATE mdl_grade_grades SET feedback = '".$MySQLi->real_escape_string($feedback)."',
			feedbackformat = 1,
			timemodified = unix_timestamp(now()),
        	usermodified = (select id from mdl_user where username = 'wsmoodle')WHERE ID = '".$MySQLi->real_escape_string($id)."'              AND (usermodified not in (SELECT mdl_role_assignments.userid FROM mdl_role_assignments, mdl_context, mdl_course, mdl_grade_items WHERE mdl_context.contextlevel = 50 AND mdl_role_assignments.contextid = mdl_context.id AND mdl_context.instanceid = mdl_course.id  AND mdl_course.id = mdl_grade_items.courseid AND mdl_role_assignments.roleid in (3,4) AND mdl_grade_items.id = mdl_grade_grades.itemid)  OR rawgrade is null)";
		}

	}

	if( $MySQLi->query($query) )
	{

		//Fazer um select na tabela mdl_assign_grades, se existir registro, fazer um update, senão fazer um insert
		$sql = "SELECT mdl_assign_grades.id
		FROM mdl_grade_grades, mdl_grade_items, mdl_assign_grades
		where mdl_grade_grades.id = '".$MySQLi->real_escape_string($id)."'
		and mdl_grade_grades.itemid = mdl_grade_items.id
		and mdl_grade_items.iteminstance = mdl_assign_grades.assignment
		and mdl_grade_grades.userid = mdl_assign_grades.userid
		and mdl_grade_items.itemmodule = 'assign'";

		$resultado = $MySQLi->query($sql);
		$Count = $resultado->fetch_row();

		$resultado->close();

		$id_assign_grades = $Count[0];
		if($id_assign_grades > 0)
		{
			if ($nota >= 0)
			{
				$query = "UPDATE mdl_assign_grades SET timemodified = unix_timestamp(now())
				,grader = (select id from mdl_user where username = 'wsmoodle')
				,grade = '".$MySQLi->real_escape_string($nota)."'
				WHERE ID = ".$id_assign_grades;
			}
			else
			{
				$query = "UPDATE mdl_assign_grades SET timemodified = unix_timestamp(now()
				,grader = (select id from mdl_user where username = 'wsmoodle')
				WHERE ID = ".$id_assign_grades;
			}

			$MySQLi->query($query);
			$query = "UPDATE mdl_assignfeedback_comments
			SET commenttext = '".$MySQLi->real_escape_string($feedback)."'
			WHERE grade = ".$id_assign_grades;

			$MySQLi->query($query);
		}
		else
		{
			if ($nota >= 0)
			{
				$query = "INSERT INTO mdl_assign_grades (assignment, userid, timecreated, timemodified, grader, grade, attemptnumber)
				VALUES (
						(SELECT mdl_grade_items.iteminstance
						FROM  mdl_grade_grades, mdl_grade_items
						where mdl_grade_grades.id = '".$MySQLi->real_escape_string($id)."'
						and mdl_grade_grades.itemid = mdl_grade_items.id
						and mdl_grade_items.itemmodule = 'assign'),
						(SELECT userid FROM mdl_grade_grades WHERE ID = '".$MySQLi->real_escape_string($id)."'),
						unix_timestamp(now()),
						unix_timestamp(now()),
						(select id from mdl_user where username = 'wsmoodle'),
						'".$MySQLi->real_escape_string($nota)."',
						0)";
			}
			else
			{
				$query = "INSERT INTO mdl_assign_grades (assignment, userid, timecreated, timemodified, grader, attemptnumber)
				VALUES (
						(SELECT mdl_grade_items.iteminstance
						FROM  mdl_grade_grades, mdl_grade_items
						where mdl_grade_grades.id = '".$MySQLi->real_escape_string($id)."'
						and mdl_grade_grades.itemid = mdl_grade_items.id
						and mdl_grade_items.itemmodule = 'assign'),
						(SELECT userid FROM mdl_grade_grades WHERE ID = '".$MySQLi->real_escape_string($id)."'),
						unix_timestamp(now()),
						unix_timestamp(now()),
						(select id from mdl_user where username = 'wsmoodle'),
						0)";
			}
			$MySQLi->query($query);
			$sql = "SELECT mdl_assign_grades.id
			FROM mdl_grade_grades, mdl_grade_items, mdl_assign_grades
			where mdl_grade_grades.id = '".$MySQLi->real_escape_string($id)."'
			and mdl_grade_grades.itemid = mdl_grade_items.id
			and mdl_grade_items.iteminstance = mdl_assign_grades.assignment
			and mdl_grade_grades.userid = mdl_assign_grades.userid
			and mdl_grade_items.itemmodule = 'assign'";

			$resultado = $MySQLi->query($sql);
			$Count = $resultado->fetch_row();
			$resultado->close();
			$id_assign_grades = $Count[0];

			$query = "INSERT INTO mdl_assignfeedback_comments (commenttext, assignment, grade, commentformat)
			VALUES (
					'".$MySQLi->real_escape_string($feedback)."',
					(SELECT mdl_grade_items.iteminstance
					FROM  mdl_grade_grades, mdl_grade_items
					where mdl_grade_grades.id = '".$MySQLi->real_escape_string($id)."'
					and mdl_grade_grades.itemid = mdl_grade_items.id
					and mdl_grade_items.itemmodule = 'assign'),".$id_assign_grades.",1)";

			$MySQLi->query($query);
		}
		return "Nota Atualizada.";
	}
	else
	{
		return "Database Error: Unable to update record.";
	}
}


function obterRubric($MySQL, $user)
{
	$sql = "SELECT 
		mdl_assign_grades.assignment AS id_atividade,
		mdl_assign_grades.userid AS id_aluno,
		mdl_gradingform_rubric_criteria.description AS desc_criterio,
		mdl_gradingform_rubric_levels.score AS nota_criterio,
		mdl_gradingform_rubric_levels.definition AS decricao_select
	from 
		mdl_assign 
		INNER JOIN mdl_assign_submission ON mdl_assign_submission.assignment = mdl_assign.id
		INNER JOIN mdl_assignsubmission_onlinetext on mdl_assignsubmission_onlinetext.submission = mdl_assign_submission.id

		INNER JOIN  mdl_assign_grades on mdl_assign_grades.assignment = mdl_assign_submission.assignment
		INNER JOIN mdl_grading_instances on mdl_assign_grades.id = mdl_grading_instances.itemid
		INNER JOIN mdl_gradingform_rubric_fillings  on mdl_grading_instances.id = mdl_gradingform_rubric_fillings.instanceid
		INNER JOIN mdl_gradingform_rubric_levels  on mdl_gradingform_rubric_levels.id = mdl_gradingform_rubric_fillings.levelid
		INNER JOIN mdl_gradingform_rubric_criteria  on mdl_gradingform_rubric_fillings.criterionid = mdl_gradingform_rubric_criteria.id 
	where 
	mdl_assign.course = ".$use['course'].
	"AND mdl_assign_submission.id = ".$use['id_submissao'].
	"AND mdl_assign_submission.status ='submitted'
	AND mdl_grading_instances.status = 0";

	$MySQLi = new MySQLi($MySQL['servidor'], $MySQL['usuario'], $MySQL['senha'], $MySQL['banco']);
	if (mysqli_connect_errno())
		trigger_error(mysqli_connect_error(), E_USER_ERROR);
	$MySQLi->set_charset("utf8");

	$result_data = $MySQLi->query($sql) OR trigger_error($MySQLi->error, E_USER_ERROR);
	if($result_data){
		while ($rubric = $result_data->fetch_object()){
			$rubrics[] = $rubric;
		}
		$result_data->close();
	}

	header('Content-type: application/json');
	$MySQLi->close();

	return json_encode(array('rubrics' => $rubrics));
}

?>
