<?php /* TASKS $Id$ */
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

$AppUI->savePlace();

// retrieve any state parameters
$user_id = $AppUI->user_id;
if (getPermission('admin', 'view')) { // Only sysadmins are able to change users
	if (dPgetParam($_POST, 'user_id', 0) != 0) { // this means that 
		$user_id = dPgetParam($_POST, 'user_id', 0);
		$AppUI->setState('user_id', $_POST['user_id']);
	} else if ($AppUI->getState('user_id')) {
		$user_id = $AppUI->getState('user_id');
	} else {
		$AppUI->setState('user_id', $user_id);
	}
}

if (isset($_POST['f'])) {
	$AppUI->setState('TaskIdxFilter', $_POST['f']);
}
$f = $AppUI->getState('TaskIdxFilter') ? $AppUI->getState('TaskIdxFilter') : 'myunfinished';

$company_prefix = "company_";
if (isset($_POST['department'])) {
	$AppUI->setState('ProjIdxDepartment', $_POST['department']);
}
$department = (($AppUI->getState('ProjIdxDepartment') !== NULL) 
               ? $AppUI->getState('ProjIdxDepartment') 
               : ($company_prefix . $AppUI->user_company));

//if $department contains the $company_prefix string that it's requesting a company
// and not a department.  So, clear the $department variable, and populate the $company_id variable.
$company_id = '0';
if (!(mb_strpos($department, $company_prefix)===false)) {
	$company_id = mb_substr($department,mb_strlen($company_prefix));
	$AppUI->setState('ProjIdxCompany', $company_id);
	unset($department);
}

if (isset($_GET['project_id'])) {
	$AppUI->setState('TaskIdxProject', $_GET['project_id']);
}
$project_id = $AppUI->getState('TaskIdxProject') ? $AppUI->getState('TaskIdxProject') : 0;

// get CCompany() to filter tasks by company
require_once($AppUI->getModuleClass('companies'));
$obj = new CCompany();
$companies = $obj->getAllowedRecords($AppUI->user_id, 'company_id,company_name', 'company_name');
$filters2 = arrayMerge(array('all' => $AppUI->_('All Companies', UI_OUTPUT_RAW)), $companies);

// setup the title block
$titleBlock = new CTitleBlock('Tasks', 'applet-48.png', $m, "$m.$a");

// patch 2.12.04 text to search entry box
if (isset($_POST['searchtext'])) {
	$AppUI->setState('searchtext', $_POST['searchtext']);
}


$search_text = $AppUI->getState('searchtext') ? $AppUI->getState('searchtext'):'';
$search_text = dPformSafe($search_text);

$titleBlock->addCell('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $AppUI->_('Search') . ':');
$titleBlock->addCell(('<input type="text" class="text" SIZE="20" name="searchtext"' 
                      . ' onChange="document.searchfilter.submit();" value="' . $search_text 
                      . '"title="' . $AppUI->_('Search in name and description fields') 
                      . '"/><!--<input type="submit" class="button" value=">" title="' 
                      . $AppUI->_('Search in name and description fields') . '"/>-->'), '',
                     '<form action="?m=tasks" method="post" id="searchfilter">', '</form>');
// Let's see if this user has admin privileges
if (getPermission('admin', 'view')) {
	$titleBlock->addCell();
	$titleBlock->addCell($AppUI->_('User') . ':');
	
	$q  = new DBQuery;
	$q->addTable('users','u');
	$q->addTable('contacts','con');
	$q->addQuery('user_id');
	$q->addQuery("CONCAT(contact_last_name, ', ', contact_first_name, ' (', user_username, ')')" . ' AS label');
	$q->addOrder('contact_last_name');
	$q->addWhere('u.user_contact = con.contact_id');
	$user_list = $q->loadHashList();
	$titleBlock->addCell(arraySelect($user_list, 'user_id', 
	                                 ('size="1" class="text"' 
	                                  . ' onChange="document.userIdForm.submit();"'), 
	                                 $user_id, false, true), '',
	                     '<form action="?m=tasks" method="post" name="userIdForm">','</form>');
}

$titleBlock->addCell();
$titleBlock->addCell($AppUI->_('Company') . '/' . $AppUI->_('Department') . ':');

//get list of all departments, filtered by the list of permitted companies.
$q  = new DBQuery;
$q->addTable('companies', 'c');
$q->addQuery('c.company_id, c.company_name, dep.*');
$q->addJoin('departments', 'dep', 'c.company_id = dep.dept_company');
$q->addOrder('c.company_name, dep.dept_parent, dep.dept_name');
$rows = $q->loadList();	

//display the select list
$cBuffer = '<select name="department" onChange="document.companyFilter.submit()" class="text">';
$cBuffer .= ('<option value="' . $company_prefix . '0" style="font-weight:bold;">' . $AppUI->_('All') 
             . '</option>'."\n");
$company = '';
foreach ($rows as $row) {
	if ($row['dept_parent'] == 0) {
		if ($company != $row['company_id']) {
			$cBuffer .= ('<option value="' . $AppUI->___($company_prefix . $row['company_id']) 
			             . '" style="font-weight:bold;"' 
			             . (($company_id == $row['company_id']) ? 'selected="selected"' : '') 
			             . '>' . $AppUI->___($row['company_name']) . '</option>' . "\n");
			$company = $row['company_id'];
		}
		
		if ($row['dept_parent'] != null) {
			showchilddept($row);
			findchilddept($rows, $row['dept_id']);
		}
	}
}
$cBuffer .= '</select>';

$titleBlock->addCell(('<form action="?m=tasks" method="post" name="companyFilter">' . "\n" 
                      . $cBuffer . "\n" .  '</form>' . "\n"));

$titleBlock->addCell();
if ($canEdit && $project_id) {
	$titleBlock->addCell(('<input type="submit" class="button" value="' . $AppUI->_('new task') 
	                      . '">'), '', 
						 ('<form action="?m=tasks&a=addedit&task_project=' . $project_id 
	                      . '" method="post">'), '</form>');
}

$titleBlock->show();

if (dPgetParam($_GET, 'inactive', '') == 'toggle')
	$AppUI->setState('inactive', $AppUI->getState('inactive') == -1 ? 0 : -1);
$in = $AppUI->getState('inactive') == -1 ? '' : 'in';

// use a new title block (a new row) to prevent from oversized sites
$titleBlock = new CTitleBlock('', 'shim.gif');
$titleBlock->showhelp = false;
$titleBlock->addCell('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $AppUI->_('Task Filter') . ':');
$titleBlock->addCell(arraySelect($filters, 'f', 
                                 'size=1 class=text onChange="document.taskFilter.submit();"', 
                                 $f, true), '',
                     '<form action="?m=tasks" method="post" name="taskFilter">', '</form>');
$titleBlock->addCell();

$titleBlock->addCrumb('?m=tasks&a=todo&user_id=' . $user_id, 'my todo');
if (dPgetParam($_GET, 'pinned') == 1) {
	$titleBlock->addCrumb('?m=tasks', 'all tasks');
} else {
	$titleBlock->addCrumb('?m=tasks&pinned=1', 'my pinned tasks');
}
$titleBlock->addCrumb('?m=tasks&inactive=toggle', 'show '.$in.'active tasks');
$titleBlock->addCrumb('?m=tasks&a=tasksperuser', 'tasks per user');
$titleBlock->addCrumb('?m=projects&a=reports', 'reports');

$titleBlock->show();

// include the re-usable sub view
$min_view = false;
include(DP_BASE_DIR.'/modules/tasks/tasks.php');
