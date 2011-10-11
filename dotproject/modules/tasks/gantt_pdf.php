<?php /* TASKS $Id: gantt.php 5798 2008-08-19 13:41:21Z merlinyoda $ */
if (!defined('DP_BASE_DIR')){
	die('You should not access this file directly.');
}
//echo $AppUI->_('Please wait while your file is retrieved from the server...');

/*
 * gantt_pdf.php - by P. Ferreira
 * TASKS $Id: gantt_pdf.php xxxx 2008-12-17 pferreira $
 */

/*
 *  First clear up the temp dir of pdf files for current user
 */
//"./files/temp/gantt" . $filedate . ".png"
foreach (glob((dPgetConfig('overlay_dir') == '' ? DP_BASE_DIR : dPgetConfig('overlay_dir')) . '/files/temp/*' . $AppUI->user_first_name . '_' . $AppUI->user_last_name . '.pdf') as $gpdffilename) {
//   echo "$filename size " . filesize($filename) . "\n";
//	if( @filemtime($filename) < (time() - 3600 ))
//	echo '<pre> Filename: ' . $filename . ' size: ' . filesize($filename) . '</pre>';
   unlink($gpdffilename);
}


$root_dir = dPgetConfig('root_dir');
include ($AppUI->getLibraryClass('jpgraph/src/jpgraph'));
include ($AppUI->getLibraryClass('jpgraph/src/jpgraph_gantt'));
require_once ('Date.php');
require_once ($AppUI->getModuleClass('projects'));
require_once ($AppUI->getModuleClass('tasks'));

global $caller, $locale_char_set;
global $user_id, $dPconfig, $sortByName, $project_id;
global $locale_char_set, $AppUI, $show_days, $dPconfig;
global $gantt_arr, $start_date_min, $end_date_max, $day_diff;
global $gtask_sliced, $projects, $printpdfhr, $showNoMilestones;
//global $gantt_map, $currentGanttImgSource, $currentImageMap; don't need these vars as they are used to hot gantt charts
//echo '<pre> POST = '; print_r($_POST); echo '</pre>';

$filedate=date('YmdHis'); // this is used to make all gantt images used in gantt's with links distinct
$project_id = dPgetParam($_REQUEST, 'project_id', 0);
$f = dPgetParam($_REQUEST, 'f', 0);
$showLabels = dPgetParam($_REQUEST, 'showLabels', 0);
$showLabels = (($showLabels != '0') ? '1': $showLabels);
$showWork = dPgetParam($_REQUEST, 'showWork', 0);
$showWork = (($showWork != '0' ) ? '1': $showWork);
$sortByName = dPgetParam($_REQUEST, 'sortByName', 0);
$sortByName = (($sortByName != '0') ? '1': $sortByName);
$ganttTaskFilter = dPgetParam($_REQUEST, 'ganttTaskFilter', 0);
$showPinned = dPgetParam( $_REQUEST, 'showPinned', false );
$showPinned = (($showPinned != '0') ? '1': $showPinned);
$showArcProjs = dPgetParam( $_REQUEST, 'showArcProjs', false );
$showArcProjs = (($showArcProjs != '0') ? '1': $showArcProjs);
$showHoldProjs = dPgetParam( $_REQUEST, 'showHoldProjs', false );
$showHoldProjs = (($showHoldProjs != '0') ? '1': $showHoldProjs);
$showDynTasks = dPgetParam( $_REQUEST, 'showDynTasks', false );
$showDynTasks = (($showDynTasks != '0') ? '1': $showDynTasks);
$showLowTasks = dPgetParam( $_REQUEST, 'showLowTasks', true);
$showLowTasks = (($showLowTasks != '0') ? '1': $showLowTasks);
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];
$sdate = dPgetParam( $_REQUEST, 'sdate', false);
$edate = dPgetParam( $_REQUEST, 'edate', false);
$display_option = dPgetParam($_REQUEST, 'display_option', false);
// Get the state of formatting variables here /////////////////////////////////////////////////////
$showTaskNameOnly = dPgetParam($_REQUEST, 'showTaskNameOnly', 0);
$showTaskNameOnly = (($showTaskNameOnly != '0') ? '1': $showTaskNameOnly);
$showNoMilestones = dPgetParam($_REQUEST, 'showNoMilestones', 0);
$showNoMilestones= (($showNoMilestones!= '0') ? '1': $showNoMilestones);
$showhgrid = dPgetParam($_REQUEST, 'showhgrid', 0);
$showhgrid = (($showhgrid != '0') ? '1': $showhgrid);
$printpdf = dPgetParam($_REQUEST, 'printpdf', 0);
$printpdf = (($printpdf != '0') ? '1': $printpdf);
$printpdfhr = dPgetParam($_REQUEST, 'printpdfhr', 0);
$printpdfhr = (($printpdfhr != '0') ? '1': $printpdfhr);
$monospacefont = dPgetParam($_POST, 'monospacefont');
$monospacefont = (($monospacefont != '0') ? '1': $monospacefont);
$user_id = dPgetParam($_POST, 'user_id', 0);

$df = $AppUI->getPref('SHDATEFORMAT');

//if ($_POST['printpdf'] != '1') {die('Print to PDF was called incorrectly');}
//$start_date = new CDate($sdate);
//$start_date = $start_date->format('%Y-%m-%d %H:%M:%S');
//$end_date = new CDate($edate);
//$end_date = $end_date->format('%Y-%m-%d %H:%M:%S');
// get the preferred date format

//echo '<pre> $project_id ='.$project_id;echo '</pre>';
//echo '<pre> $f = '.$f;echo '</pre>';
//echo '<pre> $showLabels = '.$showLabels;echo '</pre>';
//echo '<pre> $showWork = '.$showWork;echo '</pre>';
//echo '<pre> $sortByName = '.$sortByName;echo '</pre>';
//echo '<pre> $ganttTaskFilter = '.$ganttTaskFilter;echo '</pre>';
//echo '<pre> $showPinned = '.$showPinned;echo '</pre>';
//echo '<pre> $showArcProjs = '.$showArcProjs;echo '</pre>';
//echo '<pre> $showHoldProjs = '.$showHoldProjs;echo '</pre>';
//echo '<pre> $showDynTasks = '.$showDynTasks ;echo '</pre>';
//echo '<pre> $showLowTasks = '.$showLowTasks;echo '</pre>';
//echo '<pre> $showTaskNameOnly = '.$showTaskNameOnly;echo '</pre>';
//echo '<pre> $showNoMilestones = '.$showNoMilestones;echo '</pre>';
//echo '<pre> $showhgrid = '.$showhgrid;echo '</pre>';
//echo '<pre> $printpdf = '.$printpdf ;echo '</pre>';
//echo '<pre> $printpdfhr = '.$printpdfhr ;echo '</pre>';
//echo '<pre> $monospacefont = '.$monospacefont;echo '</pre>';
//echo '<pre> $start_date = '.$start_date;echo '</pre>';
//echo '<pre> $end_date = '.$end_date;echo '</pre>';
//echo '<pre> $sdate = '.$sdate ;echo '</pre>';
//echo '<pre> $edate = '.$edate;echo '</pre>';
//echo '<pre> $display_option ='.$display_option;echo '</pre>';
//echo '<pre> $df = '.$df.'</pre>';
//echo '<pre> $AppUI = '; print_r($AppUI);echo '</pre>';
//echo '<pre> $user_id = ' . $user_id . '</pre>';
//die;




/**
 * utility functions for the preparation of task data
 *
 * @todo some of these functions are not needed, need to trim this down
 *
 */
	/*
	* 	Convert string char (ref : Vbulletin #3987)
	*/
	function strJpGraph($text) {
		global $locale_char_set;
		if ( $locale_char_set=='utf-8' && function_exists("utf8_decode") ) {
			return utf8_decode($text);
		} else {
			return $text;
		}
	}
	// PYS : utf_8 decoding as suggested in Vbulletin #3987
	function strEzPdf($text) {
		global $locale_char_set;
//		if ( $locale_char_set=='utf-8' && function_exists("utf8_decode") ) {
//			return utf8_decode($text);
		if (function_exists('iconv') && function_exists('mb_detect_encoding')) {
			$text = iconv(mb_detect_encoding($text." "), 'latin1', $text);
			return $text;
		} else {
			return $text;
		}
	}

//	/*
//	* 	put_task_array function
//	* 		use task data to generate $start_min and $end_max date
//	* 		put task data in gantt_array
//	*/
//	function put_task_array( $arr, $level=0 ) {
//		global $gantt_task, $start_date_min, $end_date_max;
//	/*
//	*	Set task_start_date if not set  and set start_date_min
//	*/
//		if($arr['task_start_date'] == '0000-00-00 00:00:00') {
//			$arr['task_start_date'] = date('Y-m-d H:i:s');
//		}
//		$tsd = new CDate($arr['task_start_date']);
//		if ($tsd->before(new CDate($start_date_min))) {
//			$start_date_min = $arr['task_start_date'];
//		}
//	/*
//	*	Calculate task_end_date if not set and set end_date_min
//	*/
//		if($arr['task_end_date'] == '0000-00-00 00:00:00') {
//			if($arr['task_duration']) {
//				$arr['task_end_date'] = db_unix2dateTime ( db_dateTime2unix( $row['task_start_date'] ) + SECONDS_PER_DAY * convert2days( $row['task_duration'], $row['task_duration_type'] ) );
//			} else {
//				$arr['task_end_date'] = '';
//			}
//		}
//		$ted = new CDate($arr['task_end_date']);
//		if ($ted->after(new CDate($end_date_max))) {
//			$end_date_max = $arr['task_end_date'];
//		}
//	/*
//	* 	Put task data in gantt_task array
//	*/
//		$gantt_task[] = array($arr, $level);
//	}
//	/*
//	* 	findChildTask recursive function
//	* 		find children task of $parent and call put_task_array for storing
//	* 		param	$tarr = project task array
//	* 		param	$parent = parent task_id
//	* 		param	$level = nesting level
//	*/
//	function findChildTask( &$tarr, $parent, $level=0 ) {
//		$level++;
//		$n = count( $tarr );
//		for ($i=0; $i < $n; $i++) {
//			if($tarr[$i]['task_parent'] == $parent && $tarr[$i]['task_parent'] != $tarr[$i]['task_id'])	{
//				put_task_array( $tarr[$i], $level );
//				findChildTask( $tarr, $tarr[$i]['task_id'], $level);
//			}
//	    }
//	}


	function showgtask(&$a, $level=0) {
		/* Add tasks to gantt chart */
		global $gantt_arr, $parents;
		$gantt_arr[] = array($a, $level);
		$parents[$a['task_parent']] = true;
	}

	function findgchild(&$tarr, $parent, $level=0) {
		$level = $level + 1;
		$n = count($tarr);
		for ($x=0; $x < $n; $x++) {
			if ($tarr[$x]['task_parent'] == $parent
			    && $tarr[$x]['task_parent'] != $tarr[$x]['task_id']){
				showgtask($tarr[$x], $level);
				findgchild($tarr, $tarr[$x]['task_id'], $level);
			}
		}
	}



/*
	* 	smart_slice : recursive function used to slice the task array whlie
	* 	minimizing the potential number of task dependencies between two sub_arrays
	* 	Each sub_array is LENGTH elements long maximum
	* 	It is shorter if
	* 		- either a dynamic task is between indices LENGTH-3 and LENGTH-1 : in this
	* 		  case, the milestone is EXCLUDED from the lower sub_array
	* 		- or a milestone a MILESTONE is between indices LENGTH-2 and LENGTH-1 : in
	* 		  this case the milestone is INCLUDED in the lower sub_array
	*/
	function smart_slice( $arr, $showNoMilestones, $printpdfhr, $length ) {
		global $gtask_sliced, $showNoMilestones, $day_diff ;
//		echo '<pre> $day_diff = ' . $day_diff . '</pre>';

//		if ($showNoMilestones == '1' && $printpdfhr == '1' && $day_diff > 240) {
//			$l = 27;
//		} else if (($showNoMilestones == '1' && $printpdfhr == '1' && $day_diff < 240) ||
//					($showNoMilestones == '0' && $printpdfhr == '1' && $day_diff > 240)) {
//			$l = 26;
//		} else if ($showNoMilestones == '1' && $printpdfhr == '0') {
//			$l = 24;
//		} else {
//			$l = 23;
//		}
//		DEFINE ('LENGHT', $l);
//		echo '<pre> LENGTH = ' . LENGHT . '</pre>';
//		die;

//		if ($showNoMilestones == '1') {
//			DEFINE ( 'LENGTH', (($printpdfhr == '1') ? 27 : 23)); //24 );
//		} elseif ($showNoMilestones == '0') {
//			DEFINE ( 'LENGTH', (($printpdfhr == '1') ? 26 : 22)); //23 );
//		}

//		if ($day_diff < 240 && LENGTH > 24) {
//			DEFINE ('LENGHT', 24);
//		} elseif ($day_diff < 240 && LENGTH < 24) {
//			DEFINE ('LENGHT', 18);
//		}
//		echo '<pre> LENGTH = ' . LENGHT . '</pre>';
//		die;
		DEFINE ("LENGTH", $length);

		if ( count($arr) > LENGTH ) {
			$found = 0 ;
			for ( $i = LENGTH-3 ; $i<LENGTH ; $i++ ) {
				if ( $arr[$i][0]['task_dynamic'] != 0 ) {
					$found = $i ;
				}
			}
			if ( !$found ) {
				for ( $i = LENGTH-1 ; $i > LENGTH-3 ; $i-- ) {
					if ( $arr[$i][0]['task_milestone'] != 0 ) {
						$found = $i ;
					}
				}
				if ( !$found ) {
					if ( $arr[LENGTH][0]['task_milestone'] == 0 ) {
						 $cut = LENGTH ;						// No specific task => standard cut
					} else {
						$cut = LENGTH - 1 ;					// No orphan milestone
					}
				} else {
					$cut = $found + 1 ;						// include found milestone in lower level array
				}
			} else {
				$cut = $found ;									//include found dynamic task in higher level array
			}
			$gtask_sliced[] = array_slice( $arr, 0, $cut );
//			echo '<pre> smart_slice($arr, $cut) $cut =' . $cut . '</pre>';
			$task_sliced[] = smart_slice( array_slice( $arr, $cut ), $showNoMilestones, $printpdfhr, $day_diff );
		} else {
			$gtask_sliced[] = $arr ;
		}
		return $gtask_sliced ;
	}

	/**
	 *
	 * 	END OF UTILITY FUNCTIONS
	 *
	 */
$gantt_arr = array();
$parents = array();
$projects = array();
$gtask_sliced = array();
$gts = array();

/**
 * Here goes the code to generate the gantt.png files that later get put onto the pdf
 *
 */

$project = new CProject;
if ($project_id > 0) {
	$criticalTasks = $project->getCriticalTasks($project_id);
	$project->load($project_id);
}

// pull valid projects and their percent complete information

$q = new DBQuery;
$q->addTable('projects');
$q->addQuery('project_id, project_color_identifier, project_name'
             . ', project_start_date, project_end_date');
$q->addJoin('tasks', 't1', 'projects.project_id = t1.task_project');
$q->addWhere('project_status != 7');
$q->addGroup('project_id');
$q->addOrder('project_name');
$project->setAllowedSQL($AppUI->user_id, $q);
$projects = $q->loadHashList('project_id');
$q->clear();

$caller = defVal(@$_REQUEST['a'], null);

/**
 * if task filtering has been requested create the list of task_ids
 * which will be used to filter the query
 */
if ($ganttTaskFilter > 0) {
$task_child_search = new CTask();
$task_child_search->peek($ganttTaskFilter);
$childrenlist = $task_child_search->getDeepChildren();

$where .= ' t.task_id IN (' . $ganttTaskFilter . ', ' . implode(', ', $childrenlist) . ')';
}


// gantt is called now by the todo page, too. There is a different filter approach in todo
// so we have to tweak a little bit, also we do not have a special project available

if ($caller == 'todo') {
// 	$user_id = defVal( @$_REQUEST['user_id'], 0 );

	$projects[$project_id]['project_name'] = ($AppUI->_('Todo for') . ' ' . dPgetUsernameFromID($user_id));
	$projects[$project_id]['project_color_identifier'] = 'ff6000';

	$q->addTable('tasks', 't');
	$q->innerJoin('projects', 'p', 'p.project_id = t.task_project');
	$q->innerJoin('user_tasks', 'ut', 'ut.task_id = t.task_id AND ut.user_id = ' . $user_id);
	$q->leftJoin('user_task_pin', 'tp', 'tp.task_id = t.task_id AND tp.user_id = ' . $user_id);

	$q->addQuery('t.*, p.project_name, p.project_id, p.project_color_identifier, tp.task_pinned');

	$q->addWhere('(t.task_percent_complete < 100 OR t.task_percent_complete IS NULL)');
	$q->addWhere('t.task_status = 0');
	if (!$showArcProjs) {
		$q->addWhere('project_status <> 7');
	}
	if (!$showLowTasks) {
		$q->addWhere('task_priority >= 0');
	}
	if (!$showHoldProjs) {
		$q->addWhere('project_status != 4');
	}
	if (!$showDynTasks) {
		$q->addWhere('task_dynamic != 1');
	}
	if ($showPinned) {
		$q->addWhere('task_pinned = 1');
	}

	if ($ganttTaskFilter) {
		$q->addWhere($where);
	}

	$q->addGroup('t.task_id');
	$q->addOrder((($sortByName) ? 't.task_name, ' : '') . 't.task_end_date, t.task_priority DESC');

//	echo '<pre> query  for todo= '; print_r($q); echo '</pre>'; die;
} else {
	// pull tasks
	$q->addTable('tasks', 't');
	$q->addJoin('projects', 'p', 'p.project_id = t.task_project');

	$q->addQuery('t.task_id, task_parent, task_name, task_start_date, task_end_date'
	             . ', task_duration, task_duration_type, task_priority, task_percent_complete'
	             . ', task_order, task_project, task_milestone, project_name, task_dynamic');

	// don't add milestones if box is checked//////////////////////////////////////////////////////////
	if ($showNoMilestones == '1'){
		$q->addWhere('task_milestone != 1');
	}
	$q->addWhere('project_status != 7');
	if ($project_id) {
		$q->addWhere('task_project = ' . $project_id);
	}
	if ($f != 'myinact') {
		$q->addWhere('task_status > -1');
	}

	if ($ganttTaskFilter) {
		$q->addWhere($where);
	}
	switch ($f) {
		case 'all':
			break;
		case 'myproj':
			$q->addWhere('project_owner = ' . $AppUI->user_id);
			break;
		case 'mycomp':
			$q->addWhere('project_company = ' . $AppUI->user_company);
			break;
		case 'myinact':
			$q->innerJoin('user_tasks', 'ut', 'ut.task_id = t.task_id');
			$q->addWhere('ut.user_id = '.$AppUI->user_id);
			break;
		default:
			$q->innerJoin('user_tasks', 'ut', 'ut.task_id = t.task_id');
			$q->addWhere('ut.user_id = '.$AppUI->user_id);
			break;
	}

	$q->addOrder('p.project_id, ' . (($sortByName) ? 't.task_name, ' : '') . 't.task_start_date');
//		echo '<pre> query  = '; print_r($q); echo '</pre>';
}


// get any specifically denied tasks
$task = new CTask;
$task->setAllowedSQL($AppUI->user_id, $q);
$proTasks = $q->loadHashList('task_id');
//echo '<pre>';
//print_r($proTasks);
//echo '</pre>';
//
//array_multisort($proTasks[]['task_dynamic'], SORT_NUMERIC, SORT_DESC);
//
//echo '<pre>';
//print_r($proTasks);
//echo '</pre>';
//
//die;


$q->clear();

$orrarr[] = array('task_id'=>0, 'order_up'=>0, 'order'=>'');
$end_max = '0000-00-00 00:00:00';
$start_min = date('Y-m-d H:i:s');

//pull the tasks into an array
$criticalTasks = $project->getCriticalTasks($project_id);
$actual_end_date = new CDate($criticalTasks[0]['task_end_date']);
$p_end_date = (($actual_end_date->after($project->project_end_date)) ? $criticalTasks[0]['task_end_date'] : $project->project_end_date);

foreach ($proTasks as $row) {
	// calculate or set blank task_end_date if unset
	if ($row['task_end_date'] == '0000-00-00 00:00:00') {
		if ($row['task_duration'] && $row['task_start_date'] != '0000-00-00 00:00:00') {
			$start_date_unix_time = (db_dateTime2unix($row['task_start_date']) + SECONDS_PER_DAY * convert2days($row['task_duration'], $row['task_duration_type']));
			$row['task_end_date'] = substr(db_unix2dateTime($start_date_unix_time), 1, -1);
		} else {
			$row['task_end_date'] = $p_end_date;
		}
	}

	if ($row['task_start_date'] == '0000-00-00 00:00:00') {
		$row['task_start_date'] = $project->project_start_date; //date('Y-m-d H:i:s');
	}

	$tsd = new CDate($row['task_start_date']);
	if ($tsd->before(new CDate($start_min))) {
		$start_min = $row['task_start_date'];
	}

	$ted = new CDate($row['task_end_date']);
	if ($ted->after(new CDate($end_max))) {
		$end_max = $row['task_end_date'];
	}
	if ($ted->after(new CDate($projects[$row['task_project']]['project_end_date'])) || $projects[$row['task_project']]['project_end_date'] == '') {
		$projects[$row['task_project']]['project_end_date'] = $row['task_end_date'];
	}

	$projects[$row['task_project']]['tasks'][] = $row;
}
//echo '<pre>';
//print_r($projects);
//echo '</pre>';
//die;
unset($proTasks);
foreach ($projects as $p) {
	global $parents, $task_id;
	$parents = array();
	$tnums = 0;
	if (isset($p['tasks'])) {
		$tnums = count($p['tasks']);
	}
	count($p['tasks']);
//	$task_id = $t['task_id'];

	for ($i=0; $i < $tnums; $i++) {
		$t = $p['tasks'][$i];
		if (!(isset($parents[$t['task_parent']]))) {
			$parents[$t['task_parent']] = false;
		}
		if ($t['task_parent'] == $t['task_id']) {
			showgtask($t);
			findgchild($p['tasks'], $t['task_id']);
		}
//	echo '<pre> gantt_arr = '; print_r($gantt_arr); echo '</pre>';
//	echo '<pre> parents = '; print_r($parents); echo '</pre>';
	}
	// Check for ophans.
	foreach ($parents as $id => $ok) {
		if (!($ok)) {
			findgchild($p['tasks'], $id);
		}
	}
}


//consider critical (concerning end date) tasks as well
if ($caller != 'todo') {
	$start_min = $projects[$project_id]['project_start_date'];
	$end_max = (($projects[$project_id]['project_end_date'] > $criticalTasks[0]['task_end_date']) ? $projects[$project_id]['project_end_date'] : $criticalTasks[0]['task_end_date']);
}

//$start_date_g = dPgetParam($_REQUEST, 'start_date', $start_min);
//$end_date_g = dPgetParam($_REQUEST, 'end_date', $end_max);
if ($display_option != 'all'){
	$start_date_g = ((isset($start_date))? $start_date : $start_min);
	$end_date_g = ((isset($end_date))? $end_date : $end_max);
}else{
	$start_date_g = $start_min;
	$end_date_g = $end_max;
}
//echo $start_date_g.'>'.$start_min;
//echo $end_date_g.'>'.$end_max;
//die;

$count = 0;

$min_d_start = new CDate($start_date_g);
$max_d_end = new CDate($end_date_g);
$day_diff = $max_d_end->dateDiff($min_d_start);
//echo '<pre> $day_diff = ' . $day_diff . '</pre>';
//die;

if ($printpdfhr == '1') { $length = 25; } else { $length = 23; }
if ($showNoMilestones == '1') { $length = $length + 1; }
if ($day_diff < 90) { $length = $length - 2; } else if ($day_diff >=90 && $day_diff < 1096) {$length = $length;} else {$length = $length + 1;}

//
//		if ($showNoMilestones == '1' && $printpdfhr == '1' && $day_diff > 240) {
//			$length = 27;
//		} else if (($showNoMilestones == '1' && $printpdfhr == '1' && $day_diff < 240) ||
//					($showNoMilestones == '0' && $printpdfhr == '1' && $day_diff > 240)) {
//			$length = 26;
//		} else if ($showNoMilestones == '1' && $printpdfhr == '0') {
//			$length = 24;
//		} else {
//			$lenght = 23;
//		}
//		DEFINE ('LENGHT', $l);
//		echo '<pre> LENGTH = ' . LENGHT . '</pre>';
//		die;
//		if ($showNoMilestones == '1') {
//			DEFINE ( 'LENGTH', (($printpdfhr == '1') ? 27 : 23)); //24 );
//		} elseif ($showNoMilestones == '0') {
//			DEFINE ( 'LENGTH', (($printpdfhr == '1') ? 26 : 22)); //23 );
//		}
//		if ($day_diff < 240 && LENGTH > 24) {
//			DEFINE ('LENGHT', 24);
//		} elseif ($day_diff < 240 && LENGTH < 24) {
//			DEFINE ('LENGHT', 18);
//		}
//		echo '<pre> LENGTH = ' . $length . '</pre>';
//		die;




/*
* 	Prepare Gantt_chart loop
*/
$gtask_sliced = array() ;
$gtask_sliced = smart_slice( $gantt_arr, $showNoMilestones, $printpdfhr, $length );
$page = 0 ;					// Numbering of output files
$outpfiles = array();		// array of output files to be returned to caller
$taskcount = 0 ;
// Create task_index array
$ctflag = false ;
if ( count( $gtask_sliced ) > 1 ) {
	for ( $i = 0; $i < count($gantt_arr); $i++ ) {
		$task_index[$gantt_arr[$i][0]['task_id']] = $i+1 ;
	}
	$ctflag = true ;
}
//print "<pre> gantt_arr = ";
//print_r ($gantt_arr);
//print "</pre>";
//print "<pre> gtask_sliced= ";
//print_r ($gtask_sliced);
//print "</pre>";
//die;

//////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////

// Gantt chart loop
foreach ( $gtask_sliced as $gts ) {
//print "<pre> gts = ";
//print_r ($gts);
//print "</pre>";
//die;

//$graph = new GanttGraph(765);
$graph = new GanttGraph((($printpdfhr == '1') ? 1530 : 765)); //double size to improve resolution
//$graph = new GanttGraph($width);
//$graph->img->SetAntiAliasing(TRUE);
$graph->ShowHeaders(GANTT_HYEAR | GANTT_HMONTH | GANTT_HDAY | GANTT_HWEEK);
$graph->SetFrame(false);
$graph->SetBox(true, array(0,0,0), 2);
$graph->SetMargin(0, 0, 15, (($printpdfhr == '1') ? 40: 20));
$graph->scale->week->SetStyle(WEEKSTYLE_FIRSTDAY);
//$graph->scale->day->SetStyle(DAYSTYLE_SHORTDATE2);

//Check whether to show horizontal grid or not ////////////////////////////////////////////////////
if ($showhgrid == '1') {
	$graph->hgrid->Show ();
	$graph->hgrid->SetRowFillColor ('darkblue@0.95');
}

$pLocale = setlocale(LC_TIME, 0); // get current locale for LC_TIME
$res = @setlocale(LC_TIME, $AppUI->user_lang[2]);
if ($res) { // Setting locale doesn't fail
  $graph->scale->SetDateLocale($AppUI->user_lang[2]);
}
setlocale(LC_TIME, $pLocale);

//if ($start_date_g && $end_date_g) {
//	$graph->SetDateRange($start_date, $end_date);
//}
if ($monospacefont) {
	$graph->scale->actinfo->SetFont(FF_DEJAVUMONO, FS_NORMAL, (($printpdfhr == '1') ? 10 : 8));//8); //specify the use of VeraMono
} else {
	$graph->scale->actinfo->SetFont(FF_DEJAVU, FS_NORMAL, (($printpdfhr == '1') ? 10 : 8)); //8);
}

//$graph->scale-> SetTableTitleBackground( 'white');
//$graph->scale-> tableTitle-> Show(true);
$graph->scale->actinfo->vgrid->SetColor('gray');
$graph->scale->actinfo->SetColor('darkgray');
// Show Task names only filtering /////////////////////////////////////////////////////////////////
if ($showTaskNameOnly == '1'){
	if ($caller == 'todo') {
		$graph->scale->actinfo->SetColTitles(array(strJpGraph($AppUI->_('Task name', UI_OUTPUT_RAW))), array((($printpdfhr == '1') ? '600' : '300'))); //300));
	} else {
		$graph->scale->actinfo->SetColTitles(array(strJpGraph($AppUI->_('Task name', UI_OUTPUT_RAW))), array((($printpdfhr == '1') ? '600' : '300'))); //300));
	}
} else {
	if ($caller == 'todo') {
		$graph->scale->actinfo->SetColTitles(array(	strJpGraph($AppUI->_('Task name', UI_OUTPUT_RAW)),
													$AppUI->_('Project name', UI_OUTPUT_RAW),
													(($showWork == '1') ? $AppUI->_('Work', UI_OUTPUT_RAW) : $AppUI->_('Dur.', UI_OUTPUT_RAW)),
													$AppUI->_('Start', UI_OUTPUT_RAW),
													$AppUI->_('Finish', UI_OUTPUT_RAW)),
											array((($printpdfhr == '1') ? '300, 60, 80, 80, 80' : '150, 30, 40, 40, 40'))); //array(150, 30, 40, 40, 40));
	} else {
		$graph->scale->actinfo->SetColTitles(array(	strJpGraph($AppUI->_('Task name', UI_OUTPUT_RAW)),
													(($showWork == '1') ? $AppUI->_('Work', UI_OUTPUT_RAW) : $AppUI->_('Dur.', UI_OUTPUT_RAW)),
													$AppUI->_('Start', UI_OUTPUT_RAW),
													$AppUI->_('Finish', UI_OUTPUT_RAW)),
											array((($printpdfhr == '1') ? '360, 80, 80, 80' : '180, 40, 40, 40'))); //array(360, 80, 80, 80)); //array(180, 40, 40, 40));
	}
}
//if ($showTaskNameOnly == '1'){
//	if ($caller == 'todo') {
//		$graph->scale->actinfo->SetColTitles(array($AppUI->_('Task name', UI_OUTPUT_RAW)), array(300));
//	} else {
//		$graph->scale->actinfo->SetColTitles(array($AppUI->_('Task name', UI_OUTPUT_RAW) ), array(300));
//	}
//} else {
//	if ($caller == 'todo') {
//		$graph->scale->actinfo->SetColTitles(array(	$AppUI->_('Task name', UI_OUTPUT_RAW),
//													$AppUI->_('Project name', UI_OUTPUT_RAW),
//													(($showWork == '1') ? $AppUI->_('Work', UI_OUTPUT_RAW) : $AppUI->_('Dur.', UI_OUTPUT_RAW)),
//													$AppUI->_('Start', UI_OUTPUT_RAW),
//													$AppUI->_('Finish', UI_OUTPUT_RAW)),
//											array(180, 50, 60, 60, 60));
//	} else {
//		$graph->scale->actinfo->SetColTitles(array(	$AppUI->_('Task name', UI_OUTPUT_RAW),
//													(($showWork == '1') ? $AppUI->_('Work', UI_OUTPUT_RAW) : $AppUI->_('Dur.', UI_OUTPUT_RAW)),
//													$AppUI->_('Start', UI_OUTPUT_RAW),
//													$AppUI->_('Finish', UI_OUTPUT_RAW)),
//											array(230, 60, 60, 60));
//	}
//}
//$graph->scale->tableTitle->Set($projects[$project_id]['project_name']);

// Use TTF font if it exists
// try commenting out the following two lines if gantt charts do not display
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// when showing task names only, show project name as title of the Gantt chart ////////////////////
/* Removed title from Gantt Image in pdf

if ($showTaskNameOnly == '1') {
	if ($monospacefont) {
		$graph->title->SetFont(FF_DEJAVUMONO, FS_BOLD, 12); //specify the use of VeraMono
	} else {
		$graph->title->SetFont(FF_DEJAVU, FS_BOLD, 12);
	}
	$graph->title->Set($projects[$project_id]['project_name']);
} else {
	$graph->title->Set($projects[$project_id]['project_name']);
	if ($monospacefont) {
		$graph->scale->tableTitle->SetFont(FF_DEJAVUMONO, FS_BOLD, 10); //specify the use of VeraMono
	} else {
		$graph->scale->tableTitle->SetFont(FF_DEJAVU, FS_BOLD, 10);
	}
	$graph->scale->SetTableTitleBackground('#'.$projects[$project_id]['project_color_identifier']);
	$graph->scale->tableTitle->Show(true);
}
*/
//-----------------------------------------
// nice Gantt image
// if diff(end_date,start_date) > 90 days it shows only
//week number
// if diff(end_date,start_date) > 240 days it shows only
//month number
//-----------------------------------------
if ($start_date_g && $end_date_g){			//need to use start_date_g so that the date is set outside the loop, otherwise it fails seting range on second pass
	$min_d_start = new CDate($start_date_g);
	$max_d_end = new CDate($end_date_g);
	$graph->SetDateRange($start_date_g, $end_date_g);
} else {
	// find out DateRange from gant_arr
	$d_start = new CDate();
	$d_end = new CDate();
	for($i = 0; $i < count(@$gantt_arr); $i++){
		$a = $gts[$i][0];
		$start = substr($a['task_start_date'], 0, 10);
		$end = substr($a['task_end_date'], 0, 10);

		$d_start->Date($start);
		$d_end->Date($end);

		if ($i == 0){
			$min_d_start = $d_start;
			$max_d_end = $d_end;
		} else {
			if (CDate::compare($min_d_start,$d_start) > 0) {
				$min_d_start = $d_start;
			}
			if (CDate::compare($max_d_end,$d_end) < 0){
				$max_d_end = $d_end;
			}
		}
	}
}

// check day_diff and modify Headers
//echo '<pre> $max_d_end = ' . $max_d_end . ' $min_d_start = ' . $min_d_start . '</pre>';
//die;
$day_diff = $max_d_end->dateDiff($min_d_start);
//echo $day_diff; die;
// new scale display for gantt ////////////////////////////////////////////////////////////////////
if ($day_diff > 1096) {
	//more than 3 years, show only the year scale
	$graph->ShowHeaders(GANTT_HYEAR);
	$graph->scale->year->grid->Show ();
	$graph->scale->year->grid->SetStyle (solid); //longdashed);
	$graph->scale->year->grid->SetColor ('lightgray');
} else if ($day_diff > 480) {
	//more than 480 days show only the firstletter of the month
	$graph->ShowHeaders(GANTT_HYEAR | GANTT_HMONTH);
	$graph->scale->month->SetStyle(MONTHSTYLE_FIRSTLETTER);
	$graph->scale->month->grid->Show ();
	$graph->scale->month->grid->SetStyle (solid); //longdashed);
	$graph->scale->month->grid->SetColor ('lightgray');
} else if($day_diff > 240) {
	//more than 240 days and less than 481 show the month short name eg: Jan
	$graph->ShowHeaders(GANTT_HYEAR | GANTT_HMONTH);
	$graph->scale->month->SetStyle(MONTHSTYLE_SHORTNAME);
	$graph->scale->month->grid->Show ();
	$graph->scale->month->grid->SetStyle (solid); //longdashed);
	$graph->scale->month->grid->SetColor ('lightgray');
} else if ($day_diff > 90) {
	//more than 90 days and less of 241
	$graph->ShowHeaders(GANTT_HYEAR | GANTT_HMONTH | GANTT_HWEEK);
	$graph->scale->week->SetStyle(WEEKSTYLE_WNBR);
}
	if ($monospacefont) {
		$graph->scale->year->SetFont(FF_DEJAVUMONO, FS_NORMAL, (($printpdfhr == '1') ? 10 : 7)); //14); //7); //specify the use of VeraMono
		$graph->scale->month->SetFont(FF_DEJAVUMONO, FS_NORMAL, (($printpdfhr == '1') ? 10 : 7)); //14); //7); //specify the use of VeraMono
		$graph->scale->week->SetFont(FF_DEJAVUMONO, FS_NORMAL, (($printpdfhr == '1') ? 10 : 7)); //14); //7); //specify the use of VeraMono
	} else {
		$graph->scale->year->SetFont(FF_DEJAVU, FS_NORMAL, (($printpdfhr == '1') ? 10 : 7)); //14); //7);
		$graph->scale->month->SetFont(FF_DEJAVU, FS_NORMAL, (($printpdfhr == '1') ? 10 : 7)); //14); //7);
		$graph->scale->week->SetFont(FF_DEJAVU, FS_NORMAL, (($printpdfhr == '1') ? 10 : 7)); //14); //7);

	}

	reset($projects);
	//$p = &$projects[$project_id];
//	foreach ($projects as $p) {
//		global $parents, $task_id;
//		$parents = array();
//		$tnums = count($p['tasks']);
////		$task_id = $t['task_id'];
//
//		for ($i=0; $i < $tnums; $i++) {
//			$t = $p['tasks'][$i];
//			if (!(isset($parents[$t['task_parent']]))) {
//				$parents[$t['task_parent']] = false;
//			}
//			if ($t['task_parent'] == $t['task_id']) {
//				showgtask($t);
//				findgchild($p['tasks'], $t['task_id']);
//			}
//		}
//		// Check for ophans.
//		foreach ($parents as $id => $ok) {
//			if (!($ok)) {
//				findgchild($p['tasks'], $id);
//			}
//		}
//	}

	$hide_task_groups = false;

// removed hide groups function

//
//echo '<pre> gts = ';
//print_r($gts);
//echo '</pre>';
//die;



$row = 0;
for($i = 0; $i < count(@$gts); $i ++) {
	$a = $gts[$i][0];
	$level = $gts[$i][1];
	if ($hide_task_groups) {
		$level = 0;
	}
	$name = $a['task_name'];
//	if ($locale_char_set=='utf-8' && function_exists('utf8_decode')) {
//		$name = utf8_decode($name);
//	}
// check again for show task names only and make the task name string longer //////////////////////
	$name = (str_repeat('  ', $level) . $name);

	if ($showTaskNameOnly == '1') {
			$name = ((strlen($name) > 50) ? (substr($name, 0, 47) . '...') : $name);
		} else {
			$name = ((strlen($name) > 33) ? (substr($name, 0, 30) . '...') : $name);
		}
//	if ($showTaskNameOnly == '1') {
//			$name = ((strlen($name) > 60) ? (substr($name, 0, 57) . '...') : $name);
//		} else {
//			$name = ((strlen($name) > 37) ? (substr($name, 0, 34) . '...') : $name);
//		}
//
	if (function_exists('iconv') && function_exists('mb_detect_encoding')) {
		$name = iconv(mb_detect_encoding($name." "), 'UTF-8', $name);
	}

	if ($caller == 'todo') {
		$pname = $a['project_name'];
		if (function_exists('iconv') && function_exists('mb_detect_encoding')) {
			$pname = iconv(mb_detect_encoding($pname." "), 'UTF-8', $pname);
		}
		$pname = ((strlen($pname) > 14)
			          ? (substr($pname, 0, 5) . '...' . substr($pname, -5, 5)) : $pname);

//		if ($locale_char_set=='utf-8') {
//			if (function_exists('mb_substr')) {
//				$pname = ((mb_strlen($pname) > 14
//				          ? (mb_substr($pname, 0, 5) . '...' . mb_substr($pname, -5, 5)) : $pname));
//			}  else if (function_exists('utf8_decode')) {
//				$pname = utf8_decode($pname);
//			}
//		} else {
//			$pname = ((strlen($pname) > 14)
//			          ? (substr($pname, 0, 5) . '...' . substr($pname, -5, 5)) : $pname);
//		}
	}
	//using new jpGraph determines using Date object instead of string
//echo '<pre> iteration i = '.$i.' #################################################################################</pre>';
	$start_date = new CDate($a['task_start_date']);
//echo '<pre>'; print_r ($start_date); echo '</pre>';
	$end_date = new CDate($a['task_end_date']);
//echo '<pre>'; print_r ($end_date); echo '</pre>';
	//
	$start = $start_date->getDate();
	$end = $end_date->getDate();

	$progress = $a['task_percent_complete'] + 0;

	if ($progress > 100) {
		$progress = 100;
	} else if ($progress < 0) {
		$progress = 0;
	}

	$flags	= (($a['task_milestone']) ? 'm' : '');

	$cap = '';
	if (!$start || $start == '0000-00-00'){
		$start = ((!$end) ? date('Y-m-d') : $end);
		$cap .= '(no start date)';
	}
	if (!$end) {
		$end = $start;
		$cap .= ' (no end date)';
	}

	$caption = '';
	if ($showLabels == '1') {
		$q->addTable('user_tasks', 'ut');
		$q->innerJoin('users', 'u', 'u.user_id = ut.user_id');
		$q->addQuery('ut.task_id, u.user_username, ut.perc_assignment');
		$q->addWhere('ut.task_id = ' . $a['task_id']);
		$res = $q->loadList();
		foreach ($res as $rw) {
			switch ($rw['perc_assignment']) {
				case 100:
					$caption .= ($rw['user_username'] . ';');
					break;
				default:
					$caption .= ($rw['user_username'] . '[' . $rw['perc_assignment'] . '%];');
					break;
			}
		}
		$q->clear();
		$caption = substr($caption, 0, (strlen($caption) - 1));
//		if (function_exists('utf8_decode')){
//			$caption = utf8_decode($caption);
		if (function_exists('iconv') && function_exists('mb_detect_encoding')) {
			$caption = iconv(mb_detect_encoding($caption." "), 'UTF-8', $caption);
		}
//		}
	}

	if ($flags == 'm') {
	// if hide milestones is ticked this bit is not processed//////////////////////////////////////////
		if ($showNoMilestones != '1') {
			$s = date('m/d/Y');
			$start_date_mile = new CDate($start_date);
			$start_date_mile->setTime(0);
			$s = $start_date_mile->format($df);
			$start_mile = $start_date_mile->getDate();
			$today_date = date('m/d/Y');
			$today_date_stamp = strtotime($today_date);
			$mile_date = $start_date->format("%m/%d/%Y");
			$mile_date_stamp = strtotime($mile_date);
	// honour the choice to show task names only///////////////////////////////////////////////////
			if ($showTaskNameOnly == '1') {
				if ($caller == 'todo'){
					$milestone_label_array = array($name);
				} else {
						$milestone_label_array = array($name);
				}
				$bar = new MileStone($row++, $milestone_label_array, $start_mile, $s);
				if ($monospacefont) {
					$bar->title->SetFont(FF_DEJAVUMONO, FS_NORMAL, (($printpdfhr == '1') ? 12 : 8)); //16); //8); //specify the use of VeraMono
				} else {
					$bar->title->SetFont(FF_DEJAVU, FS_NORMAL, (($printpdfhr == '1') ? 12 : 8)); //16); //8);
				}
			} else {
				if ($caller == 'todo') {
					$milestone_label_array = array($name, $pname, '', $s, $s);
				} else {
					$milestone_label_array = array($name, '', $s, $s);
				}
				$bar = new MileStone($row++, $milestone_label_array, $start_mile, $s);
				if ($monospacefont) {
					$bar->title->SetFont(FF_DEJAVUMONO, FS_NORMAL, (($printpdfhr == '1') ? 12 : 8)); //16); //8); //specify the use of VeraMono
				} else {
					$bar->title->SetFont(FF_DEJAVU, FS_NORMAL, (($printpdfhr == '1') ? 12 : 8)); //16); //8);
				}
			}
			//caption of milestone should be date
			if ($showLabels == '1') {
				//$caption = $start->format($df);
				$caption .= ( $caption != "" ? "\n" : "" ) . $start_date->format($df);
			}
			///////////////////////////////////////////////////////////////////////////////////////
			//set color for milestone according to progress
			//red for 'not started' #990000
			//yellow for 'in progress' #FF9900
			//green for 'achieved' #006600
			// blue for 'planned' #0000FF
			$bar->mark->SetType(MARK_DIAMOND);
			$bar->mark->SetWidth(10);
			if ($a['task_percent_complete'] == 100)  {
				$bar->title->SetColor('#006600');
				$bar->mark->SetColor('#006600');
				$bar->mark->SetFillColor('#006600');
			} else {
				if (strtotime($mile_date) < strtotime($today_date)) {
					$bar->title->SetColor('#990000');
					$bar->mark->SetColor('#990000');
					$bar->mark->SetFillColor('#990000');
				} else{
					if ($a['task_percent_complete'] == 0)  {
						$bar->title->SetColor('#0000FF');
						$bar->mark->SetColor('#0000FF');
						$bar->mark->SetFillColor('#0000FF');
					} else {
						$bar->title->SetColor('#FF9900');
						$bar->mark->SetColor('#FF9900');
						$bar->mark->SetFillColor('#FF9900');
					}
				}
			}
		}	//this closes the code that is not processed if hide milestones is checked ///////////////
	} else {
		$type = $a['task_duration_type'];
		$dur = $a['task_duration'];
		if ($type == 24) {
			$dur *= $dPconfig['daily_working_hours'];
		}
		if ($showWork=='1') {
			$work_hours = 0;
			$q->addTable('tasks', 't');
			$q->addJoin('user_tasks', 'u', 't.task_id = u.task_id');
			$q->addQuery('ROUND(SUM(t.task_duration*u.perc_assignment/100),2) AS wh');
			$q->addWhere('t.task_duration_type = 24');
			$q->addWhere('t.task_id = '.$a['task_id']);

			$wh = $q->loadResult();
			$work_hours = $wh * $dPconfig['daily_working_hours'];
			$q->clear();

			$q->addTable('tasks', 't');
			$q->addJoin('user_tasks', 'u', 't.task_id = u.task_id');
			$q->addQuery('ROUND(SUM(t.task_duration*u.perc_assignment/100),2) AS wh');
			$q->addWhere('t.task_duration_type = 1');
			$q->addWhere('t.task_id = '.$a['task_id']);

			$wh2 = $q->loadResult();
			$work_hours += $wh2;
			$q->clear();
			//due to the round above, we don't want to print decimals unless they really exist
			$dur = $work_hours;
		}
		$dur .= ' h';
		$enddate = new CDate($end);
		$startdate = new CDate($start);
		// here will check again if showNameOnly is checked and only add task name to the array ///
		if ($showTaskNameOnly == '1') {
			if ($caller == 'todo') {
				$bar_label_array = array($name);
			} else {
				$bar_label_array = array($name);
			}
			$bar = new GanttBar($row++, $bar_label_array, $start, $end, $cap, ($a['task_dynamic'] == 1 ? 0.1 : 0.6));
			$bar->progress->Set(min(($progress/100),1));
			// make the font a little bigger if showing task names only
			if ($monospacefont) {
				$bar->title->SetFont(FF_DEJAVUMONO, FS_NORMAL, (($printpdfhr == '1') ? 12 : 8)); //16); //8); //specify the use of VeraMono
			} else {
				$bar->title->SetFont(FF_DEJAVU, FS_NORMAL, (($printpdfhr == '1') ? 12 : 8)); //16); //8);
			}
		} else {
			if ($caller == 'todo') {
				$bar_label_array = array($name, $pname, $dur, $startdate->format($df), $enddate->format($df));
			} else {
				$bar_label_array = array($name, $dur, $startdate->format($df), $enddate->format($df));
			}
			$bar = new GanttBar($row++, $bar_label_array, $start, $end, $cap, ($a['task_dynamic'] == 1 ? 0.1 : 0.6));
			$bar->progress->Set(min(($progress/100),1));
			if ($monospacefont) {
				$bar->title->SetFont(FF_DEJAVUMONO, FS_NORMAL, (($printpdfhr == '1') ? 12 : 8)); //16); //8); //specify the use of VeraMono
			} else {
				$bar->title->SetFont(FF_DEJAVU, FS_NORMAL, (($printpdfhr == '1') ? 12 : 8)); //16); // 8);
			}
		}
		// make the font a little bigger if showing task names only////////////////////////////////
		if ($a['task_dynamic'] == 1){
			if ($showTaskNameOnly == '1'){
				if ($monospacefont) {
					$bar->title->SetFont(FF_DEJAVUMONO,FS_BOLD, (($printpdfhr == '1') ? 12 : 8)); //16); //8); //specify the use of VeraMono
				} else {
					$bar->title->SetFont(FF_DEJAVU,FS_BOLD, (($printpdfhr == '1') ? 12 : 8)); //16); //8);
				}
			} else {
				if ($monospacefont) {
					$bar->title->SetFont(FF_DEJAVUMONO,FS_BOLD, (($printpdfhr == '1') ? 12 : 8)); //16); //8); //specify the use of VeraMono
				} else {
					$bar->title->SetFont(FF_DEJAVU,FS_BOLD, (($printpdfhr == '1') ? 12 : 8)); //16); //8);
				}
			}
			$bar->rightMark->Show();
			$bar->rightMark->SetType(MARK_RIGHTTRIANGLE);
			$bar->rightMark->SetWidth(3);
			$bar->rightMark->SetColor('black');
			$bar->rightMark->SetFillColor('black');

			$bar->leftMark->Show();
			$bar->leftMark->SetType(MARK_LEFTTRIANGLE);
			$bar->leftMark->SetWidth(3);
			$bar->leftMark->SetColor('black');
			$bar->leftMark->SetFillColor('black');

			$bar->SetPattern(BAND_SOLID,'black');
		}
	}
	//add hyperlinks to the tasks and bars
//	if ($addLinksToGantt == 1){
//		$bar->title->SetCSIMTarget ('./index.php?m=tasks&a=view&task_id=' . $a['task_id'] . '" target="_blank"', 'Click here to see details');
//		$bar->SetCSIMTarget ('./index.php?m=tasks&a=view&task_id=' . $a['task_id'] . '" target="_blank"' , 'Click here to see details');
//	}


	//adding captions
	$bar->caption = new TextProperty($caption);
	$bar->caption->Align('left','center');
	if ($monospacefont) {
		$bar->caption->SetFont(FF_DEJAVUMONO, FS_NORMAL, (($printpdfhr == '1') ? 12 : 7)); //14); //7); //specify the use of VeraMono
	} else {
		$bar->caption->SetFont(FF_DEJAVU, FS_NORMAL, (($printpdfhr == '1') ? 12 : 7)); //14); //7);
	}

/*	// show tasks which are both finished and past in (dark)gray
	if ($progress >= 100 && $end_date->isPast() && get_class($bar) == 'ganttbar') {
		$bar->caption->SetColor('gray');
		$bar->title->SetColor('gray');
		$bar->setColor('gray');
		$bar->SetFillColor('gray');
		$bar->SetPattern(BAND_SOLID,'gray');
		$bar->progress->SetFillColor('gray');
		$bar->progress->SetPattern(BAND_SOLID,'gray',98);
	}
*/


	$q->addTable('task_dependencies');
	$q->addQuery('dependencies_task_id');
	$q->addWhere('dependencies_req_task_id=' . $a['task_id']);
	$query = $q->loadList();

	foreach ($query as $dep) {
		// find row num of dependencies
		for ($d = 0; $d < count($gts); $d++) {
			if ($gts[$d][0]['task_id'] == $dep['dependencies_task_id']) {
			  $bar->SetConstrain($d, CONSTRAIN_ENDSTART);
			}
		}
	}
	unset($query);
	$q->clear();
	$graph->Add($bar);
}
unset($gts);
$today = date('y-m-d');
$vline = new GanttVLine($today, $AppUI->_('Today', UI_OUTPUT_RAW));
if ($monospacefont) {
	$vline->title->SetFont(FF_DEJAVUMONO, FS_BOLD, (($printpdfhr == '1') ? 12 : 8)); //16); //8); //specify the use of VeraMono
} else {
	$vline->title->SetFont(FF_DEJAVU, FS_BOLD, (($printpdfhr == '1') ? 12 : 8)); //16); //8);
}
$graph->Add($vline);
$filename = (dPgetConfig('overlay_dir') == '' ? DP_BASE_DIR : dPgetConfig('overlay_dir')) . "/files/temp/GanttPDF".$AppUI->user_id . sprintf( "%2u", $page) . ".png" ;
// Prepare Gantt image and store in $filename
$graph->Stroke( $filename );
$outpfiles[] = $filename ;
$page++ ;
}

//Override of some variables, not very tidy but necessary when importing code from other sources...
$skip_page = 0;
$do_report = 1;
$show_task = 1;
$show_assignee = 1;
$show_gantt = 1;
	if ($showTaskNameOnly == '1'){
	$show_gantt_taskdetails = 0;
	} else {
	$show_gantt_taskdetails = 1;
	}
	$ganttfile = $outpfiles;
	// Initialize PDF document
		$font_dir = dPgetConfig( 'root_dir' )."/lib/ezpdf/fonts";
		$temp_dir = (dPgetConfig('overlay_dir') == '' ? DP_BASE_DIR : dPgetConfig('overlay_dir')) . "/files/temp";
		$base_url  = dPgetConfig( 'base_url' );
		require( $AppUI->getLibraryClass( 'ezpdf/class.ezpdf' ) );
		$pdf = new Cezpdf($paper='A4',$orientation='landscape');
		$pdf->ezSetCmMargins( 2, 1.5, 1.4, 1.4 ); //(top, bottom, left, right)
//		$pdf->ezSetCmMargins( 3.25, 2, 1, 1 );
/*
* 		Define page header to be displayed on top of each page
*/
		$pdf->saveState();
		if ( $skip_page ) $pdf->ezNewPage();
		$skip_page++;
		$page_header = $pdf->openObject();
			$pdf->selectFont( "$font_dir/Helvetica-Bold.afm" );
			$ypos= $pdf->ez['pageHeight'] - ( 30 + $pdf->getFontHeight(12) );
			$doc_title = strEzPdf( $AppUI->_($projects[$project_id]['project_name'], UI_OUTPUT_RAW));
			$pwidth=$pdf->ez['pageWidth'];
			$xpos= round( ($pwidth - $pdf->getTextWidth( 12, $doc_title ))/2, 2 );
			$pdf->addText( $xpos, $ypos, 12, $doc_title) ;
			$pdf->selectFont( "$font_dir/Helvetica.afm" );
			$date = new CDate();
			$xpos = round( $pwidth - $pdf->getTextWidth( 10, $date->format($df)) - $pdf->ez['rightMargin'] , 2);
			$doc_date = strEzPdf($date->format( $df ));
			$pdf->addText( $xpos, $ypos, 10, $doc_date );
//			$xpos = $pdf->ez['leftMargin'];
//			$pdf-> addText($xpos, $ypos, 10, $AppUI->_($AppUI->user_first_name.' '.$AppUI->user_last_name, UI_OUTPUT_RAW));

			//TODO: Add the legend to the Gantt on the bottom of each page
//			print "<pre> AppUI "; print_r($AppUI); print "</pre>"; //die;
//			$ypos -= round ( 1.5*$pdf->getFontHeight(12) , 2 ) ;
//			$pdf->ezSetY( $ypos );

//
//			$pdf->addText($xpos, $ypos, 10, $AppUI->_('Dynamic Task', UI_OUTPUT_RAW));
//			$pdf->ezImage(DP_BASE_DIR . '/modules/tasks/images/task_dynamic.png', 0, 58, 'none', 'center');
//			$pdf->addText($xpos, $ypos, 10, $AppUI->_('Task (planned)', UI_OUTPUT_RAW));
//			$pdf->ezImage(DP_BASE_DIR . '/modules/tasks/images/task_planned.png', 0, 58, 'none', 'center');
//			$pdf->addText($xpos, $ypos, 10, $AppUI->_('Task (in progress)', UI_OUTPUT_RAW));
//			$pdf->ezImage(DP_BASE_DIR . '/modules/tasks/images/task_in_progress.png', 0, 58, 'none', 'center');
//			$pdf->addText($xpos, $ypos, 10, $AppUI->_('Task (completed)', UI_OUTPUT_RAW));
//			$pdf->ezImage(DP_BASE_DIR . '/modules/tasks/images/task_completed.png', 0, 58, 'none', 'center');

			$pdf->closeObject($page_header);
			$pdf->addObject($page_header, 'all');
			$gpdfkey=DP_BASE_DIR. '/modules/tasks/images/ganttpdf_key.png';
			$gpdfkeyNM=DP_BASE_DIR. '/modules/tasks/images/ganttpdf_keyNM.png';

//			$gantt_key = array (
//				'1' => 1,
//			'2' => 2,
//			'3' => 3,
//			'4' => 4,
//			'5' => 5,
//			'6' => 6,
//			'7' => 7,
//			'8'  => 8,
//
//				'1' => 'Task (planned): ',
//				'2' => $pdf->ezImage(DP_BASE_DIR . '/modules/tasks/images/milestone_completed.png', 0, 12, 'none', 'center'),
//				'3' => 'Task (in progress) : ',
//				'4' => $pdf->ezImage(DP_BASE_DIR . '/modules/tasks/images/milestone_in_progress.png', 0, 12, 'none', 'center'),
//				'5' => 'Task (completed)',
//				'6' => $pdf->ezImage(DP_BASE_DIR . '/modules/tasks/images/milestone_in_progress.png', 0, 12, 'none', 'center'),
//				'7' => 'Dynamic Task',
//				'8' => $pdf->ezImage(DP_BASE_DIR . '/modules/tasks/images/milestone_in_progress.png', 0, 12, 'none', 'center'),
//			);
//
//			$gantt_key_milestone = array (
//				'1' => 'Task (planned): ',
//				'1' => $pdf->ezImage(DP_BASE_DIR . '/modules/tasks/images/milestone_completed.png', 0, 12, 'none', 'center'),
//				'1' => 'Task (in progress) : ',
//				'1' => $pdf->ezImage(DP_BASE_DIR . '/modules/tasks/images/milestone_in_progress.png', 0, 12, 'none', 'center'),
//				'1' => 'Task (completed)',
//				'1' => $pdf->ezImage(DP_BASE_DIR . '/modules/tasks/images/milestone_in_progress.png', 0, 12, 'none', 'center'),
//				'1' => 'Dynamic Task',
//				'1' => $pdf->ezImage(DP_BASE_DIR . '/modules/tasks/images/milestone_in_progress.png', 0, 12, 'none', 'center'),
//
//				'1' => 'Milestone(planned): ',
//				'2' => $pdf->ezImage(DP_BASE_DIR . '/modules/tasks/images/milestone_completed.png', 0, 12, 'none', 'center'),
//				'3' => 'Milestone (completed) : ',
//				'4' => $pdf->ezImage(DP_BASE_DIR . '/modules/tasks/images/milestone_in_progress.png', 0, 12, 'none', 'center'),
//				'5' => 'Milestone (in progress)',
//				'6' => $pdf->ezImage(DP_BASE_DIR . '/modules/tasks/images/milestone_in_progress.png', 0, 12, 'none', 'center'),
//				'7' => 'Milestone (overdue)',
//				'8' => $pdf->ezImage(DP_BASE_DIR . '/modules/tasks/images/milestone_in_progress.png', 0, 12, 'none', 'center'),
//
//			);
//			$gantt_key_options = array (
//				'showLines' => 1, // 1 show borders, 0: no borders, 2: show borders and lines between rows
//				'showHeadings' => 0, // 1: show, 0: hide
//				'shaded' => 0, // 0: no shading, 1: alternate line shading, 2: both sets are shaded
////				'shadeCol' => (0.8,0.8,0.8), //(r,g,b) define the color of shading
////				'shadeCol2' => (0.7,0.7,0.7), //(r,g,b) define the color of shading of the second set (used when shaded = 2)
//				'fontsize' => 10,
////				'textCol' => (r,g,b), // font colour
//				'titleFontSize' => 12,
//				'rowGap' => 2, //the space between the text and the lines at each row
//				'colGap' => 5 //the space between the text and the column lines in each column
////				'lineCol' => (r,g,b), //colour of the lines, default black
////				'xPos' =>
//
//			);

			$pdf->ezStartPageNumbers( 802 , 30 , 10 ,'left','Page {PAGENUM} of {TOTALPAGENUM}') ;
			for ($i=0; $i < count($ganttfile); $i++) {
					$gf = $ganttfile[$i];
//					print "<pre>" . $gpdfkey . "</pre>";
//					print "<pre>" . $gf . "</pre>";
//					die;
					$pdf->ezColumnsStart(array('num' =>1, 'gap' =>0));
					$pdf->ezImage( $gf, 0, 765, 'width', 'left'); // No pad, width = 800px, resize = 'none' (will go to next page if image height > remaining page space)
					if ($showNoMilestones == '1') {
						$pdf->ezImage( $gpdfkeyNM, 0, 765, 'width', 'left');
					} else {
					$pdf->ezImage( $gpdfkey, 0, 765, 'width', 'left');
					}
					$pdf->ezColumnsStop();
//					$pdf->ezSetY(10);
//					$pdf->selectFont( "$font_dir/Helvetica.afm" );
//					$pdf->ezTable($gantt_key, $gantt_key_milestone, 'Gantt Chart Key');
			}

//			Might bring some of this functionality back

			// Create project_header for the current project
//			$project_header=$pdf->openObject();
//				$pdf->selectFont( "$font_dir/Helvetica-Bold.afm" );
//				$xpos = round(( $pwidth - $pdf->getTextWidth(15, $pname) )/2);
//				$pdf->addText( $xpos, $ypos, 15, strEzPdf( $pname ));
//				$pdf->ezSetY( $ypos - 2*$pdf->getFontHeight(15) );
//				$pdf->closeObject($project_header);
//				$pdf->addObject($project_header, 'all');

//				$pdf->selectFont( "$font_dir/Helvetica.afm" );
//				$pdf->ezText( "" );
//				$xpos=round( $pdf->ez['leftMargin'], 2 );

//				print $gf;
//				die;
				//$pdf->ezImage( $gf, 0, 757, 'width'); // No pad, width = 750, resize = 'width' (will go to next page if image height > remaining page space)
//				$gpdfkey=DP_BASE_DIR. '/modules/tasks/images/gantt_pdf_key.png';
//				print "<pre>" . $gpdfkey . "</pre>";
//				print "<pre>" . $gf . "</pre>";

//				die;
//				$pdf->ezImage( $gpdfkey, 0, 853, 'none', 'center');
//				foreach ( $ganttfile as $gf )


// End of project display
//			$pdf->stopObject($project_header);
		// Create document body and pdf temp file
		$pdf->stopObject($page_header);
                $gpdffile = $temp_dir . '/' . preg_replace('/[^a-z0-9äöüß()\\[\\]-]/iu', '_', $projects[$project_id]['project_name']) . '.pdf';
		if ($fp = fopen( $gpdffile, 'wb' )) {
			fwrite( $fp, $pdf->ezOutput() );
			fclose( $fp );
			?>
<!--	This is a bit of javascript that was used to open a new window with to display the pdf-->
<!--	it has been removed because of problems with popup blockers. -->
<!--	Instead the user gets a prompt to download/open the file -->
<!--			<script language="javascript" type="text/javascript">-->
<!--			//<![CDATA[-->
<!--				var ganttPdfView;-->
<!--				params  = 'width='+screen.width;-->
<!--				params += ', height='+screen.height;-->
<!--				params += ', top=0, left=0'-->
<!--				params += ', fullscreen=yes';-->
<!--				ganttPdfView = window.open('<?php // echo $temp_dir.'/GanttChart' . $AppUI->user_id . '.pdf'; ?>', 'Gantt to PDF', params);-->
<!--				ganttPdfView.focus();-->
<!--				ganttPdf.window.close();-->
<!--			//]]>-->
<!--			</script>-->
			<?php

		} else {
			//TODO: create error handler for permission problems

			echo "Could not open file to save PDF.  ";
			if (!is_writable( $temp_dir ))
				echo "The files/temp directory is not writable.  Check your file system permissions.";
		}

		$_POST['printpdf'] = '0';
		$printpdf = '0';
		foreach (glob((dPgetConfig('overlay_dir') == '' ? DP_BASE_DIR : dPgetConfig('overlay_dir')) . '/files/temp/GanttPDF' . $AppUI->user_id .'*') as $gpdfpic) {
		   unlink($gpdfpic);
		}

		// check that file exists and is readable
		if (file_exists($gpdffile) && is_readable($gpdffile)) {

			// get the file size and send the http headers
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename='.basename($gpdffile));
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . filesize($gpdffile));
			ob_clean();
			flush();
			readfile($gpdffile);
			exit;
		}
	$_POST['printpdf']= 0;
	$printpdf = 0;
	$_POST['printpdfhr']= 0;
	$printpdfhr = 0;
?>
