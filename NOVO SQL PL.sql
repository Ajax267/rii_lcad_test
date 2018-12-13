SELECT
    mdl_gradingform_rubric_criteria.id as rubric_id,
	mdl_gradingform_rubric_levels.score AS nota_criterio
	
FROM 
	mdl_assign 
	INNER JOIN mdl_assign_submission ON mdl_assign_submission.assignment = mdl_assign.id
	INNER JOIN mdl_assignsubmission_onlinetext on mdl_assignsubmission_onlinetext.submission = mdl_assign_submission.id
	INNER JOIN  mdl_assign_grades on mdl_assign_grades.assignment = mdl_assign_submission.assignment
	INNER JOIN mdl_grading_instances on mdl_assign_grades.id = mdl_grading_instances.itemid
	INNER JOIN mdl_gradingform_rubric_fillings  on mdl_grading_instances.id = mdl_gradingform_rubric_fillings.instanceid
	INNER JOIN mdl_gradingform_rubric_levels  on mdl_gradingform_rubric_levels.id = mdl_gradingform_rubric_fillings.levelid
	INNER JOIN mdl_gradingform_rubric_criteria  on mdl_gradingform_rubric_fillings.criterionid = mdl_gradingform_rubric_criteria.id 
WHERE 
	mdl_assign.course = 2
	AND mdl_assign_submission.id = 63
	AND mdl_assign_grades.userid = 65-- userid
	AND mdl_assign_submission.status ='submitted'
    AND mdl_gradingform_rubric_fillings.instanceid IN (
		SELECT
			max(mdl_gradingform_rubric_fillings.instanceid) as seletor1
		FROM 
			mdl_assign 
			INNER JOIN mdl_assign_submission ON mdl_assign_submission.assignment = mdl_assign.id
			INNER JOIN mdl_assignsubmission_onlinetext on mdl_assignsubmission_onlinetext.submission = mdl_assign_submission.id
			INNER JOIN  mdl_assign_grades on mdl_assign_grades.assignment = mdl_assign_submission.assignment
			INNER JOIN mdl_grading_instances on mdl_assign_grades.id = mdl_grading_instances.itemid
			INNER JOIN mdl_gradingform_rubric_fillings  on mdl_grading_instances.id = mdl_gradingform_rubric_fillings.instanceid
			INNER JOIN mdl_gradingform_rubric_levels  on mdl_gradingform_rubric_levels.id = mdl_gradingform_rubric_fillings.levelid
			INNER JOIN mdl_gradingform_rubric_criteria  on mdl_gradingform_rubric_fillings.criterionid = mdl_gradingform_rubric_criteria.id 
		WHERE 
			mdl_assign.course = 2
			AND mdl_assign_submission.id = 63
			AND mdl_assign_grades.userid = 65-- userid
			AND mdl_assign_submission.status ='submitted'
    )
GROUP BY
	mdl_assign_grades.assignment,
	mdl_assign_grades.userid,
	mdl_gradingform_rubric_levels.score,
	mdl_gradingform_rubric_criteria.id
order by mdl_gradingform_rubric_criteria.id
