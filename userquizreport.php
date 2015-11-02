<?php

require_once(dirname(__FILE__) . '/../config.php');

$sort         = optional_param('sort', 'fullname', PARAM_ALPHANUM);
$dir          = optional_param('dir', 'ASC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 25, PARAM_INT);
$baseurl 	  = new moodle_url('userquizreport.php', array('sort' => $sort, 'dir' => $dir, 'perpage' => $perpage));
$pixbaseurl   = "$CFG->wwwroot/pix";

$PAGE->set_context(get_system_context());
$PAGE->set_pagelayout('admin');
$PAGE->set_title("User Quiz Reports");
$PAGE->set_heading("User Quiz Report");
$PAGE->set_url($CFG->wwwroot.'/userquizreport.php');

if(isset($_POST['clear']) && $_POST['clear'] == 'Clear')
{
    unset($_POST);
    unset($_SESSION['postfilter']);
}
else if(isset($_POST['filter']) && $_POST['filter'] == 'Search')
{
    $userpost = $_POST['user'];    
    $_SESSION['postfilter'] = $_POST;
    $page = 0;
}
else if(isset($_SESSION['postfilter']) && !empty($_SESSION['postfilter']))
{
    $_POST = $_SESSION['postfilter'];
    $userpost = $_POST['user'];    
}

if(isset($userpost) && !empty($userpost)){    
    $where[] = " u.id = $userpost ";
}

if(!empty($where))
{
	$strwhere = implode(" AND ", $where);
	$strwhere = " AND $strwhere ";
}

$results = $DB->get_records_sql("SELECT
									CONCAT(u.id,'',q.id) AS uniqueId,
									u.id,
									q.id,
									u.firstname,
									u.lastname,
									CONCAT(u.firstname, ' ', u.lastname) AS fullname,
									q.`name` AS quizname,
									gi.gradepass
								FROM
									mdl_user AS u
								LEFT JOIN mdl_quiz_grades AS qg ON qg.userid = u.id
								LEFT JOIN mdl_quiz AS q ON q.id = qg.quiz
								LEFT JOIN mdl_grade_items AS gi ON gi.iteminstance = q.id AND gi.itemname = q.`name` AND gi.itemmodule = 'quiz'
								WHERE
									u.confirmed = 1
								AND u.deleted = 0
								AND u.suspended = 0
								AND u.id > 2
								AND qg.quiz IS NOT NULL
								$strwhere
								ORDER BY
									$sort $dir", array(), $page*$perpage, $perpage);

$countresults = $DB->get_record_sql("SELECT
									COUNT(*) AS totalrecords
								FROM
									mdl_user AS u
								LEFT JOIN mdl_quiz_grades AS qg ON qg.userid = u.id
								LEFT JOIN mdl_quiz AS q ON q.id = qg.quiz
								LEFT JOIN mdl_grade_items AS gi ON gi.iteminstance = q.id AND gi.itemname = q.`name` AND gi.itemmodule = 'quiz'
								WHERE
									u.confirmed = 1
								AND u.deleted = 0
								AND u.suspended = 0
								AND u.id > 2
								AND qg.quiz IS NOT NULL $strwhere");

//$sortnameimg = "";
if($sort == 'fullname'){

    if($dir == 'ASC'){            	
    	$sortnameimg = "<img src='$pixbaseurl/t/sort_asc.png' alt='Ascending' title='Ascending'>";
        $sortnameurl = "$pageurl?sort=fullname&dir=DESC";
    }else if($dir == 'DESC'){
        $sortnameimg = "<img src='$pixbaseurl/t/sort_desc.png' alt='Descending' title='Descending'>";
        $sortnameurl = "$pageurl?sort=fullname&dir=ASC";
    }
}else{
    $sortnameurl = "$pageurl?sort=fullname&dir=ASC";
    $sortnameimg = "<img src='$pixbaseurl/t/sort.png' alt='Descending' title='Descending'>";
}
$nameheading = "<a href='$sortnameurl'>Name $sortnameimg</a></div>";

//$sortquizimg = "";
if($sort == 'quizname'){

    if($dir == 'ASC'){
        $sortquizimg = "<img src='$pixbaseurl/t/sort_asc.png' alt='Ascending' title='Ascending'>";
        $sortquizurl = "$pageurl?sort=quizname&dir=DESC";
    }else if($dir == 'DESC'){
        $sortquizimg = "<img src='$pixbaseurl/t/sort_desc.png' alt='Descending' title='Descending'>";
        $sortquizurl = "$pageurl?sort=quizname&dir=ASC";
    }
}else{
    $sortquizurl = "$pageurl?sort=quizname&dir=ASC";
    $sortquizimg = "<img src='$pixbaseurl/t/sort.png' alt='Descending' title='Descending'>";
}
$quizheading = "<a href='$sortquizurl'>Quiz $sortquizimg</a></div>";

$table = new html_table();
$table->head    = array($nameheading, $quizheading, 'Score');
$table->align   = array("left", "left", "center");
$table->width   = "100%";

if (!$results) {
    $cell = new html_table_cell();
    $cell->text = "No Record Found";
    $cell->colspan = count($table->head);
    $row = new html_table_row();
    $row->cells[] = $cell;
    $table->data = array($row);
} else {
	foreach ($results as $result) {
		$table->data[] = array(ucfirst($result->firstname) . " " . ucfirst($result->lastname), $result->quizname, round($result->gradepass, 2));	
	}	
}

$users = $DB->get_records_sql("SELECT * FROM {user} AS u WHERE u.confirmed = 1 AND u.deleted = 0 AND u.suspended = 0 AND u.id > 2");

echo $OUTPUT->header();
?>
<form method="post" action="">
	<table cellpadding="10">
		<tr>
			<td colspan="3">
				<h4>Filters</h4>
			</td>
		</tr>
		<tr>			
			<td>
				<b>Select User</b>
			</td>
			<td>
				&nbsp;<select name="user">
					<option value="">Please Select User</option>
					<?php foreach($users as $user) { ?>
						<?php if($userpost == $user->id){ ?>
							<option value="<?php echo $user->id;?>" selected="selected"><?php echo ucfirst($user->firstname) . " " . ucfirst($user->lastname);?></option>
						<? }else{ ?>
							<option value="<?php echo $user->id;?>"><?php echo ucfirst($user->firstname) . " " . ucfirst($user->lastname);?></option>
						<? } ?>
					<? } ?>
				</select>
			</td>
			<td>
				<input type="submit" name="filter" value="Search" />
				<input type="submit" name="clear" value="Clear" />
			</td>
		</tr>		
	</table>
</form>
<hr>
<?php
if (!empty($table)) {
    echo html_writer::table($table);    
    echo $OUTPUT->paging_bar($countresults->totalrecords, $page, $perpage, $baseurl);
}

echo $OUTPUT->footer();
?>