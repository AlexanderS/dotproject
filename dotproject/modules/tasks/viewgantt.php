<?php /* TASKS $Id: viewgantt.php 5830 2008-11-10 23:32:39Z merlinyoda $ */
if (!defined('DP_BASE_DIR')){
  die('You should not access this file directly.');
}

GLOBAL $min_view, $m, $a, $user_id, $tab, $tasks, $sortByName, $project_id, $gantt_map, $currentGanttImgSource, $filter_task_list, $caller;

$base_url = dPgetConfig('base_url');
$min_view = defVal(@$min_view, false);

$project_id = defVal(@$_GET['project_id'], 0);

// sdate and edate passed as unix time stamps
$sdate = dPgetParam($_POST, 'sdate', 0);
$edate = dPgetParam($_POST, 'edate', 0);

//if set GantChart includes user labels as captions of every GantBar
$showLabels = dPgetParam($_POST, 'showLabels', '0');
$showLabels = (($showLabels != '0') ? '1' : $showLabels);

$showWork = dPgetParam($_POST, 'showWork', '0');
$showWork = (($showWork != '0') ? '1' : $showWork);

$showWork_days = dPgetParam($_POST, 'showWork_days', '0');
$showWork_days = (($showWork_days != '0') ? '1' : $showWork_days);
/////////////////////////////////////////// New variables for use in Gantt formatting are defined here //////////////////////////////////////////////////////////////////////
$showTaskNameOnly = dPgetParam($_REQUEST, 'showTaskNameOnly', '0');
$showTaskNameOnly = (($showTaskNameOnly != '0') ? '1' : $showTaskNameOnly);

$showNoMilestones = dPgetParam($_POST, 'showNoMilestones', '0');
$showNoMilestones = (($showNoMilestones != '0') ? '1' : $showNoMilestones);

$showhgrid = dPgetParam($_REQUEST, 'showhgrid', '0');
$showhgrid = (($showhgrid != '0') ? '1' : $showhgrid);

$addLinksToGantt = dPgetParam($_REQUEST, 'addLinksToGantt', '0');
$addLinksToGantt = (($addLinksToGantt !='0')? '1' : $addLinksToGantt);

$printpdf = dPgetParam($_REQUEST, 'printpdf', '0');
$printpdf = (($printpdf != '0') ? '1' : $printpdf);

$printpdfhr = dPgetParam($_REQUEST, 'printpdfhr', '0');
$printpdfhr = (($printpdfhr != '0') ? '1' : $printpdfhr);

$ganttTaskFilter = intval(dPgetParam($_REQUEST, 'ganttTaskFilter', '0'));

$monospacefont = dPgetParam($_REQUEST, 'monospacefont', '0');
$monospacefont = (($monospacefont != '0')? '1' : $monospacefont);
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

///////////////////set sort by name as default ////////////////////////////////////

$sortByName = dPgetParam($_REQUEST, 'sortByName');

if ($sortByName =='1') {
	$sortByName = dPgetParam($_POST, 'sortByName', '1');
} else {
	$sortByName = dPgetParam($_POST, 'sortByName', '0');
}
$sortByName = (($sortByName != '0') ? '1' : $sortByName);


////////////////////////end mod to show sort by name as default//////////////////
if ($a == 'todo') {
        $AppUI->setState('TaskDayShowArc', dPgetParam($_POST, 'showArcProjs', 0));
	$AppUI->setState('TaskDayShowLow', dPgetParam($_POST, 'showLowTasks', 0));
	$AppUI->setState('TaskDayShowHold', dPgetParam($_POST, 'showHoldProjs', 0));
	$AppUI->setState('TaskDayShowDyn', dPgetParam($_POST, 'showDynTasks', 0));
	$AppUI->setState('TaskDayShowPin', dPgetParam($_POST, 'showPinned', 0));

	$showArcProjs = $AppUI->getState('TaskDayShowArc', 0);
	$showLowTasks = $AppUI->getState('TaskDayShowLow', 0);
	$showHoldProjs = $AppUI->getState('TaskDayShowHold', 0);
	$showDynTasks = $AppUI->getState('TaskDayShowDyn', 0);
	$showPinned = $AppUI->getState('TaskDayShowPin', 0);

} else {
	$showPinned = dPgetParam($_POST, 'showPinned', '0');
	$showPinned = (($showPinned != '0') ? '1' : $showPinned);
	$showArcProjs = dPgetParam($_POST, 'showArcProjs', '0');
	$showArcProjs = (($showArcProjs != '0') ? '1' : $showArcProjs);
	$showHoldProjs = dPgetParam($_POST, 'showHoldProjs', '0');
	$showHoldProjs = (($showHoldProjs != '0') ? '1' : $showHoldProjs);
	$showDynTasks = dPgetParam($_POST, 'showDynTasks', '0');
	$showDynTasks = (($showDynTasks != '0') ? '1' : $showDynTasks);
	$showLowTasks = dPgetParam($_POST, 'showLowTasks', '0');
	$showLowTasks = (($showLowTasks != '0') ? '1' : $showLowTasks);

}


/**
  * prepare the array with the tasks to display in the task filter
  * (for the most part this is code harvested from gantt.php)
  *
  */
$filter_task_list = array();
$q = new DBQuery;
$q->addTable('projects');
$q->addQuery('project_id, project_color_identifier, project_name'
             . ', project_start_date, project_end_date');
$q->addJoin('tasks', 't1', 'projects.project_id = t1.task_project');
$q->addWhere('project_status != 7');
$q->addGroup('project_id');
$q->addOrder('project_name');
//$projects->setAllowedSQL($AppUI->user_id, $q);
$projects = $q->loadHashList('project_id');
$q->clear();

$q->addTable('tasks', 't');
$q->addJoin('projects', 'p', 'p.project_id = t.task_project');
$q->addQuery('t.task_id, task_parent, task_name, task_start_date, task_end_date'
             . ', task_duration, task_duration_type, task_priority, task_percent_complete'
             . ', task_order, task_project, task_milestone, project_name, task_dynamic');

$q->addWhere('project_status != 7 AND task_dynamic = 1');
if ($project_id) {
	$q->addWhere('task_project = ' . $project_id);
}
$task = new CTask;
$task->setAllowedSQL($AppUI->user_id, $q);
$proTasks = $q->loadHashList('task_id');
$q->clear();
$filter_task_list = array ();
$orrarr[] = array('task_id'=>0, 'order_up'=>0, 'order'=>'');
foreach ($proTasks as $row) {
	$projects[$row['task_project']]['tasks'][] = $row;
}
unset($proTasks);
$parents = array();
function showfiltertask(&$a, $level=0) {
	/* Add tasks to the filter task aray */
	global $filter_task_list, $parents;
	$filter_task_list[] = array($a, $level);
	$parents[$a['task_parent']] = true;
}
function findfiltertaskchild(&$tarr, $parent, $level=0) {
	GLOBAL $projects, $filter_task_list;
	$level = $level + 1;
	$n = count($tarr);
	for ($x=0; $x < $n; $x++) {
		if ($tarr[$x]['task_parent'] == $parent && $tarr[$x]['task_parent'] != $tarr[$x]['task_id']){
			showfiltertask($tarr[$x], $level);
			findfiltertaskchild($tarr, $tarr[$x]['task_id'], $level);
		}
	}
}
foreach ($projects as $p) {
	global $parents, $task_id;
	$parents = array();
	$tnums = count($p['tasks']);
	for ($i=0; $i < $tnums; $i++) {
		$t = $p['tasks'][$i];
		if (!(isset($parents[$t['task_parent']]))) {
			$parents[$t['task_parent']] = false;
		}
		if ($t['task_parent'] == $t['task_id']) {
			showfiltertask($t);
			findfiltertaskchild($p['tasks'], $t['task_id']);
		}
	}
	// Check for ophans.
	foreach ($parents as $id => $ok) {
		if (!($ok)) {
			findfiltertaskchild($p['tasks'], $id);
		}
	}
}
/**
 * the results of the above bits are stored in $filter_task_list (array)
 *
 */

// months to scroll
$scroll_date = 1;

$display_option = dPgetParam($_POST, 'display_option', 'all');

// format dates
$df = $AppUI->getPref('SHDATEFORMAT');

if ($display_option == 'custom') {
	// custom dates
	$start_date = ((intval($sdate)) ? new CDate($sdate) : new CDate());
	$end_date = ((intval($edate)) ? new CDate($edate) : new CDate());
} else {
	// month
	$start_date = new CDate();
	$start_date->day = 1;
   	$end_date = new CDate($start_date);
	$end_date->addMonths($scroll_date);
}

// setup the title block
if (!@$min_view) {
	$titleBlock = new CTitleBlock('Gantt Chart', 'applet-48.png', $m, "$m.$a");
	$titleBlock->addCrumb('?m=tasks', 'tasks list');
	$titleBlock->addCrumb(('?m=projects&amp;a=view&amp;project_id=' . $project_id), 'view this project');
	$titleBlock->addCrumb('#" onclick="javascript:toggleLayer(\'displayOptions\');', 'show/hide display options');
	$titleBlock->show();
}
?>
<script language="javascript" type="text/javascript">
// <![CDATA[
var calendarField = "";

function popCalendar(field) {
	calendarField = field;
	idate = eval("document.editFrm." + field + ".value");
	window.open("index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=" + idate,
	            "calwin", "width=250, height=230, scrollbars=no, status=no"); ////chaged height from 220
}
/**
 *	@param string Input date in the format YYYYMMDD
 *	@param string Formatted date
 */
function setCalendar(idate, fdate) {
	fld_date = eval("document.editFrm." + calendarField);
	fld_fdate = eval("document.editFrm.show_" + calendarField);
	fld_date.value = idate;
	fld_fdate.value = fdate;

	document.editFrm.display_option.value="custom";
}
function scrollPrev() {
	f = document.editFrm;
<?php
	$new_start = new CDate($start_date);
	$new_start->day = 1;
	$new_end = new CDate($end_date);
	$new_start->addMonths(-$scroll_date);
	$new_end->addMonths(-$scroll_date);
	echo ('f.sdate.value="' . $new_start->format(FMT_TIMESTAMP_DATE) . '";');
	echo ('f.edate.value="' . $new_end->format(FMT_TIMESTAMP_DATE) . '";');
?>
	document.editFrm.display_option.value = "custom";
	f.submit()
}
function scrollNext() {
	f = document.editFrm;
<?php
	$new_start = new CDate($start_date);
	$new_start->day = 1;
	$new_end = new CDate($end_date);
	$new_start->addMonths($scroll_date);
	$new_end->addMonths($scroll_date);
	echo ('f.sdate.value="' . $new_start->format(FMT_TIMESTAMP_DATE) . '";');
	echo ('f.edate.value="' . $new_end->format(FMT_TIMESTAMP_DATE) . '";');
?>
	document.editFrm.display_option.value = "custom";
	document.editFrm.printpdf.value = "0";
	document.editFrm.printpdfhr.value = "0";
	f.submit()
}
function showThisMonth() {
	document.editFrm.display_option.value = "this_month";
	document.editFrm.printpdf.value = "0";
	document.editFrm.printpdfhr.value = "0";
	document.editFrm.submit();
}
function showFullProject() {
	document.editFrm.display_option.value = "all";
	document.editFrm.printpdf.value = "0";
	document.editFrm.printpdfhr.value = "0";
	document.editFrm.submit();
}
function toggleLayer( whichLayer ) {
	var elem, vis;
	if( document.getElementById ) // this is the way the standards work
		elem = document.getElementById( whichLayer );
	else if( document.all ) // this is the way old msie versions work
		elem = document.all[whichLayer];
	else if( document.layers ) // this is the way nn4 works
		elem = document.layers[whichLayer];
	vis = elem.style;
	// if the style.display value is blank we try to figure it out here
	if(vis.display==''&&elem.offsetWidth!=undefined&&elem.offsetHeight!=undefined)
		vis.display = (elem.offsetWidth!=0&&elem.offsetHeight!=0)?'block':'none';
		vis.display = (vis.display==''||vis.display=='block')?'none':'block';
}
function printPDF() {
	document.editFrm.printpdf.value = "0";
	document.editFrm.printpdfhr.value = "1";
	document.editFrm.submit();
}
function submitIt() {
	document.editFrm.printpdf.value = "0";
	document.editFrm.printpdfhr.value = "0";
	document.editFrm.submit();
}
function doMenu(item) {
	obj=document.getElementById(item);
	col=document.getElementById("x" + item);
	if (obj.style.display=="none") {
		obj.style.display="block";
		col.innerHTML="Hide Additional Gantt Options";
	} else {
		obj.style.display="none";
		col.innerHTML="Show Additional Gantt Options";
	}
}

//]]>
</script>

<?php ////////////////////// New checkboxes with additional formatting go here, this is with the view of displaying the options in an ajax box in the future /////////////////////////// -->
?>
<div id="displayOptions" style="text-align: center;"> <!-- start of div used to show/hide formatting options -->
<br />
<form name="editFrm" method="post" action="?<?php
echo htmlspecialchars('m=' . $m . '&a=' . $a . '&tab=' . $tab . '&project_id=' . $project_id); ?>">
<input type="hidden" name="display_option" value="<?php echo $display_option;?>" />
<input type="hidden" name="printpdf" value="<?php echo $printpdf; ?>" />
<input type="hidden" name="printpdfhr" value="<?php echo $printpdfhr; ?>" />
<input type="hidden" name="caller" value="<?php echo $a; ?>" />
<table border="0" cellpadding="2" cellspacing="1" width="80%" class="tbl" style="margin: 0px auto;">
  <tr><th><?php echo $AppUI->_('Date'); ?></th></tr>
  <tr>
    <td style="text-align: center;">
       <div style="text-align: center; margin: 10px auto;">
         <input type="button" style="width: 160px; margin-right: 20px;" class="button" value="<?php echo $AppUI->_('show this month');?>" onclick='javascript:showThisMonth()' />
         <input type="button" style="width: 160px;" class="button" value="<?php echo $AppUI->_('show full project');?>" onclick='javascript:showFullProject()' />
       </div>
       <div style="text-align: center; margin: 10px auto;">
       <?php if ($display_option != "all") { ?>
           <a href="javascript:scrollPrev()">
	      <img src="./images/prev.gif" width="16" height="16" alt="<?php echo $AppUI->_('previous');?>" border="0" style="margin-right: 10px;" />
	   </a>
       <?php }

       echo $AppUI->_('From');?>:
       <input type="hidden" name="sdate" value="<?php echo $start_date->format(FMT_TIMESTAMP_DATE);?>" />
       <input type="text" class="text" name="show_sdate" value="<?php echo $start_date->format($df);?>" size="12" disabled="disabled" />
       <a href="javascript:popCalendar('sdate')" style="margin-right: 15px;">
	 <img src="./images/calendar.gif" width="24" height="12" alt="" border="0" />
       </a>

	<?php echo $AppUI->_('To');?>:
       <input type="hidden" name="edate" value="<?php echo $end_date->format(FMT_TIMESTAMP_DATE);?>" />
       <input type="text" class="text" name="show_edate" value="<?php echo $end_date->format($df);?>" size="12" disabled="disabled" />
       <a href="javascript:popCalendar('edate')">
         <img src="./images/calendar.gif" width="24" height="12" alt="" border="0" />
       </a>

      <?php if ($display_option != "all") { ?>
        <a href="javascript:scrollNext()">
	   <img src="./images/next.gif" width="16" height="16" alt="<?php echo $AppUI->_('next');?>" border="0" style="margin-left: 10px;" />
	</a>
      <?php } ?>
      </div>
    </td>
  </tr>
</table>
<br />
<?php /*<table border="0" align="center" class="tbl" border="0" cellpadding="2" cellspacing="1" style="min-width:990px">
<tr> <!--  Task selection options plus Print to PDF go in this row -->
	<td align="right"><em>Task Filter:</em></td>
<!--  task filter  -->
	<td align="right">
		<table border="0" cellpadding="4" cellspacing="0">
		<tr><td width="210">
<!--		<label for="ganttTaskFilter"><?php //echo $AppUI->_('Filter:')?></label>&nbsp;-->
		<select name="ganttTaskFilter" id="ganttTaskFilter" class="text" onchange="javascript:submitIt()" size="1">
			<?php
				echo '<option value="0" '. (($ganttTaskFilter == '' OR $ganttTaskFilter == 0) ? ' selected="selected">' : '>') . '&lt;None Selected&gt; </option>';
				echo "\n";
				for ($i =0; $i < count($filter_task_list); $i++) {
					$filter_task_name = $filter_task_list[$i][0]['task_name'];
					$filter_task_level = $filter_task_list[$i][1];
					$filter_task_name = ((strlen($filter_task_name) > 71) ? substr($filter_task_name, 0, (68 - $filter_task_level)) . '...': $filter_task_name);
					for ($ii = 1; $ii <= $filter_task_level; $ii++) {
						$filter_task_name = '&nbsp;&nbsp;'. $filter_task_name ;
								}
					echo ('<option value="' . $filter_task_list[$i][0]['task_id'].'"'
						.(($ganttTaskFilter == $filter_task_list[$i][0]['task_id']) ? ' selected="selected">' : '>')
						. $filter_task_name . '</option>');
					echo "\n";
					$filter_task_name = '';
					$filter_task_level = '';
				}?>
		</select>
		</td>
		<td align="right" valign="top" width="20">
		&nbsp;</td></tr></table>
	</td>
	<td align="right">
		<table border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td align="right">

			&nbsp;</td>
		</tr>
		</table>
	</td>

</tr> */ ?>

<?php /* <tr align="left"> <!--  Additional Gantt FOrmatting options go in this row. (show/hide behaviour) -->
	<th colspan="4" align="left"><em><a style="color: white" href="javascript:doMenu('ganttoptions')" id="xganttoptions">Show Additional Gantt Options</a></em></th>
</tr> */ ?>

<table border="0" cellpadding="2" cellspacing="1" width="80%" class="tbl" style="margin: 0px auto;">
  <tr><th colspan="2"><?php echo $AppUI->_('Options'); ?></th></tr>
  <tr align="left">
    <td colspan="2" align="center" style="padding: 5px;">
	 <!-- sort tasks by name (instead of date) -->
	 <span style="white-space: nowrap;">
	 <input type="checkbox" name="sortByName" id="sortByName" <?php echo (($sortByName == 1) ? 'checked="checked"' : ''); ?> />
         <label for="sortByName"><?php echo $AppUI->_('Sort by Name'); ?></label>
	 </span>

	 <!-- show task names only -->
	 <span style="white-space: nowrap; margin-left: 10px;">
	 <input type="checkbox" name="showTaskNameOnly" id="showTaskNameOnly" <?php echo (($showTaskNameOnly == 1) ? 'checked="checked"' : ''); ?> />
	 <label for="showTaskNameOnly"><?php echo $AppUI->_('Show names only'); ?></label>
	 </span>

	 <!--  use monoSpace Font (recommended when showing task names only) -->
	 <span style="white-space: nowrap; margin-left: 10px;">
	 <input type="checkbox" name="monospacefont" id="monospacefont" <?php echo (($monospacefont == 1) ? 'checked="checked"' : ''); ?> />
	 <label for="monospacefont"><?php echo $AppUI->_('Use MonoSpace Font'); ?></label>
	 </span>

	 <!--  add links to gantt -->
	 <span style="white-space: nowrap; margin-left: 10px;">
	 <input type="checkbox" name="addLinksToGantt" id="addLinksToGantt" <?php echo (($addLinksToGantt == 1) ? 'checked="checked"' : ''); ?> />
	 <label for="addLinksToGantt"><?php echo $AppUI->_('Add links to Gantt'); ?></label>
	 </span>

	 <!-- show no milestones -->
	 <span style="white-space: nowrap; margin-left: 10px;">
	 <input type="checkbox" name="showNoMilestones" id="showNoMilestones" <?php echo (($showNoMilestones == 1) ? 'checked="checked"' : ''); ?> />
	 <label for="showNoMilestones"><?php echo $AppUI->_('Hide Milestones'); ?></label>
	 </span>

	 <!-- show horizontal grid -->
	 <span style="white-space: nowrap; margin-left: 10px;">
	 <input type="checkbox" name="showhgrid" id="showhgrid" <?php echo (($showhgrid == 1) ? 'checked="checked"' : ''); ?> />
	 <label for="showhgrid"><?php echo $AppUI->_('Show horizontal grid'); ?></label>
	 </span>

	 <span style="white-space: nowrap; margin-left: 10px;">
	 <input type="checkbox" name="showLabels" id="showLabels" <?php	echo (($showLabels == 1) ? 'checked="checked"' : ''); ?> />
	 <label for="showLabels"><?php echo $AppUI->_('Show captions'); ?></label>
	 </span>

	 <span style="white-space: nowrap; margin-left: 10px;">
	 <input type="checkbox" name="showWork" id="showWork" <?php echo (($showTaskNameOnly == 1) ? 'disabled="disabled"': ''); echo (($showWork == 1) ? 'checked="checked"' : ''); ?> />
	 <label for="showWork"><?php echo $AppUI->_('Show work instead of duration (Hours)'); ?></label>
	 </span>
    </td>
  </tr>
  <?php if($a == 'todo') { ?>
  <tr align="left">
    <td colspan="2" align="center" style="padding: 5px;">
	 <span style="white-space: nowrap;">
	 <input type="checkbox" name="showPinned" id="showPinned" <?php echo $showPinned ? 'checked="checked"' : ''; ?> />
	 <label for="showPinned"><?php echo $AppUI->_('Pinned Only'); ?></label>
	 </span>

	 <span style="white-space: nowrap; margin-left: 10px;">
	 <input type="checkbox" name="showArcProjs" id="showArcProjs" <?php echo $showArcProjs ? 'checked="checked"' : ''; ?> />
	 <label for="showArcProjs"><?php echo $AppUI->_('Archived Projects'); ?></label>
	 </span>

	 <span style="white-space: nowrap; margin-left: 10px;">
	 <input type="checkbox" name="showHoldProjs" id="showHoldProjs" <?php echo $showHoldProjs ? 'checked="checked"' : ''; ?> />
	 <label for="showHoldProjs"><?php echo $AppUI->_('Projects on Hold'); ?></label>
	 </span>

	 <span style="white-space: nowrap; margin-left: 10px;">
         <input type="checkbox" name="showDynTasks" id="showDynTasks" <?php echo $showDynTasks ? 'checked="checked"' : ''; ?> />
	 <label for="showDynTasks"><?php echo $AppUI->_('Dynamic Tasks'); ?></label>
	 </span>

	 <span style="white-space: nowrap; margin-left: 10px;">
	 <input type="checkbox" name="showLowTasks" id="showLowTasks" <?php echo $showLowTasks ? 'checked="checked"' : ''; ?> />
	 <label for="showLowTasks"><?php echo $AppUI->_('Low Priority Tasks'); ?></label>
	 </span>
     </td>
  </tr>
  <?php } ?>

  <tr>
      <td style="text-align: center; padding: 10px;">
	  <input type="button" style="width: 110px; margin-right: 10px;" class="button" value="<?php echo $AppUI->_('Refresh');?>" onclick='javascript:submitIt()' />
          <input type="button" style="width: 110px; margin-left: 10px;" class="button" value="<?php echo $AppUI->_('Print PDF');?>" onclick='javascript:printPDF()' />
      </td>
  </tr>
</table>

</form>
</div> <!-- end of div used to show/hide formatting options -->
<br />
<br />
<table cellspacing="0" cellpadding="0" border="1" align="center">
<tr>
	<td valign="top" align="center">
<?php
if ($a != 'todo') {
	$q = new DBQuery;
	$q->addTable('tasks');
	$q->addQuery('COUNT(*) AS N');
	$q->addWhere('task_project=' . $project_id);
	$cnt = $q->loadList();
	$q->clear();
} else {
	$cnt[0]['N'] = ((empty($tasks)) ? 0 : 1);
}
///////////////////////////////////new check box variables need to be passed here to gantt.php ////////////////////////////////////////////////////////////////////////////////////////
if ($cnt[0]['N'] > 0) {
/*
    <Script>
    if (navigator.appName == 'Netscape' && document.layers != null) {
        wid = window.innerWidth;
        hit = window.innerHeight;
    }
    if (document.all != null){
        wid = document.body.clientWidth;
        hit = document.body.clientHeight;
    }
    document.write('Height '+hit+', Width '+wid);
    </script>
*/
//	include 'gantt.php';

	$src = ('?m=tasks&a=gantt&suppressHeaders=1&project_id=' . $project_id
	        . (($display_option == 'all') ? ''
	           : ('&start_date=' . $start_date->format('%Y-%m-%d')
	              . '&end_date=' . $end_date->format('%Y-%m-%d')))
	        . "&width=' + ((navigator.appName=='Netscape'"
	        . "?window.innerWidth:document.body.offsetWidth)*0.95) + '"
	        . '&showLabels=' . $showLabels . '&showWork=' . $showWork
	        . '&sortByName=' . $sortByName . '&showTaskNameOnly=' . $showTaskNameOnly
			. '&showhgrid=' . $showhgrid . '&showPinned=' . $showPinned
	        . '&showArcProjs=' . $showArcProjs . '&showHoldProjs=' . $showHoldProjs
	        . '&showDynTasks=' . $showDynTasks . '&showLowTasks=' . $showLowTasks
	        . '&caller=' . $a . '&user_id=' . $user_id
			. '&printpdf=' . $printpdf . '&showNoMilestones=' . $showNoMilestones
			. '&addLinksToGantt=' . $addLinksToGantt . '&ganttTaskFilter=' . $ganttTaskFilter
			. '&monospacefont=' . $monospacefont . '&showWork_days=' . $showWork_days);

	if ($addLinksToGantt == 1) {
		?>
		<iframe width="980px" height="500px" align="middle" src="<?php echo DP_BASE_URL . '/index.php' . htmlspecialchars($src); ?>" title="Please wait while the Gantt Chart is generated. (this might take up to a couple of minutes to complete)">Your current browser does not support frames. As a result this feature is not available.</iframe>
		<?php
	} else {
		?>
		<script language="javascript" type="text/javascript"> document.write('<img alt="Please wait while the Gantt chart is generated... (this might take a minute or two)" src="<?php echo htmlspecialchars($src); ?>" />') </script>
		<?php
	}

	//If we have a problem displaying this we need to display a warning.
	//Put it at the bottom just in case
	if (! dPcheckMem(32*1024*1024)) {
		echo "</td>\n</tr>\n<tr>\n<td>";
		echo '<span style="color: red; font-weight: bold;">' . $AppUI->_('invalid memory config') . '</span>';
		echo "\n";
	}
} else {
	echo $AppUI->_('No tasks to display');
}
?>
	</td>
</tr>
<?php
//POST of all necesary variables to generate gantt in PDF
$_POST['m'] = 'tasks';
$_POST['a'] = 'gantt_pdf';
$_POST['suppressHeaders'] = '1';
$_POST['project_id'] = $project_id;
//if ($display_option == 'all') {
	$_POST['start_date'] = $start_date->format('%Y-%m-%d');
	$_POST['end_date'] = $end_date->format('%Y-%m-%d');
//}
//$_POST['start_date'] = $start_date;
//$_POST['end_date'] = $end_date;
$_POST['display_option'] = $display_option;
$_POST['showLabels']= $showLabels;
$_POST['showWork']= $showWork;
$_POST['sortByName']= $sortByName;
$_POST['showTaskNameOnly']= $showTaskNameOnly;
$_POST['showhgrid']= $showhgrid;
$_POST['showPinned']= $showPinned;
$_POST['showArcProjs']= $showArcProjs;
$_POST['showHoldProjs']= $showHoldProjs;
$_POST['showDynTasks']= $showDynTasks;
$_POST['showLowTasks']= $showLowTasks;
$_POST['caller']= $a;
$_POST['user_id']= $user_id;
$_POST['printpdf']= $printpdf;
$_POST['printpdfhr']= $printpdfhr;
$_POST['showNoMilestones']= $showNoMilestones;
$_POST['addLinksToGantt']= $addLinksToGantt;
$_POST['ganttTaskFilter']= $ganttTaskFilter;
$_POST['monospacefont']= $monospacefont;
$_POST['showWork_days']= $showWork_days;
$_POST['monospacefont']= $monospacefont;
$_POST['showPinned']= $showPinned;
$_POST['showArcProjs']= $showArcProjs;
$_POST['showHoldProjs']= $showHoldProjs;
$_POST['showDynTasks']= $showDynTasks;
$_POST['showLowTasks']= $showLowTasks;

//echo '<pre> $_POST = '; print_r($_POST); echo '</pre>';

if ( $printpdf == 1 || $printpdfhr == 1) {
	include 'gantt_pdf.php';
$_POST['printpdf']= 0; $printpdf = 0;
$_POST['printpdfhr']= 0; $printpdfhr = 0;
}
?>
</table>
<br />
<br />
<table border="0" cellpadding="2" cellspacing="0" width="80%" class="tbl" style="margin: 0px auto;">
  <tr><th colspan="8"><?php echo $AppUI->_('Gantt chart key'); ?></th></tr>
  <tr>
	<td align="left" style="border: 1px solid black; border-right: 0px; padding-left: 10px;"><?php echo $AppUI->_('Dynamic Task')?>&nbsp;</td>
	<td align="center" style="border: 1px solid black; border-right: 0px; border-left: 0px;"><img src="<?php echo DP_BASE_URL;?>/modules/tasks/images/task_dynamic.png" alt=""/></td>
	<td align="left" style="border: 1px solid black; border-right: 0px; padding-left: 10px;"><?php echo $AppUI->_('Task (planned)')?>&nbsp;</td>
	<td align="center" style="border: 1px solid black; border-right: 0px; border-left: 0px;"><img src="<?php echo DP_BASE_URL;?>/modules/tasks/images/task_planned.png" alt=""/></td>
	<td align="left" style="border: 1px solid black; border-right: 0px; padding-left: 10px;"><?php echo $AppUI->_('Task (in proggress)')?>&nbsp;</td>
	<td align="center" style="border: 1px solid black; border-right: 0px; border-left: 0px;"><img src="<?php echo DP_BASE_URL;?>/modules/tasks/images/task_in_progress.png" alt=""/></td>
	<td align="left" style="border: 1px solid black; border-right: 0px; padding-left: 10px;"><?php echo $AppUI->_('Task (completed)')?>&nbsp;</td>
	<td align="center" style="border: 1px solid black; border-left: 0px;"><img src="<?php echo DP_BASE_URL;?>/modules/tasks/images/task_completed.png" alt=""/></td>
</tr>
<?php
if ($showNoMilestones != 1) {
?>
<tr>
	<td align="left" style="border: 1px solid black; border-right: 0px; border-top: 0px; padding-left: 10px;"><?php echo $AppUI->_('Milestone (planned)')?>&nbsp;</td>
	<td align="center" style="border-bottom: 1px solid black;"><img src="<?php echo DP_BASE_URL;?>/modules/tasks/images/milestone_planned.png" alt=""/></td>
	<td align="left" style="border-bottom: 1px solid black; border-left: 1px solid black; padding-left: 10px;"><?php echo $AppUI->_('Milestone (completed)')?>&nbsp;</td>
	<td align="center" style="border-bottom: 1px solid black;"><img src="<?php echo DP_BASE_URL;?>/modules/tasks/images/milestone_completed.png" alt=""/></td>
	<td align="left" style="border-bottom: 1px solid black; border-left: 1px solid black; padding-left: 10px;"><?php echo $AppUI->_('Milestone (in progress)')?>&nbsp;</td>
	<td align="center" style="border-bottom: 1px solid black;"><img src="<?php echo DP_BASE_URL;?>/modules/tasks/images/milestone_in_progress.png" alt=""/></td>
	<td align="left" style="border-bottom: 1px solid black; border-left: 1px solid black; padding-left: 10px;"><?php echo $AppUI->_('Milestone (overdue)')?>&nbsp;</td>
	<td align="center" style="border-bottom: 1px solid black; border-right: 1px solid black;"><img src="<?php echo DP_BASE_URL;?>/modules/tasks/images/milestone_overdue.png" alt=""/></td>
</tr>
<?php
}
?>
</table>
<br />
