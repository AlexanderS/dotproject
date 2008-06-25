<?php
/**
 * Dynamic list generates its data on demand using a
 * Query and a set of filter objects.
 * 
 * @package dotproject
 * @subpackage system
 * @version 3.0 alpha
 */
class DP_List_Dynamic implements Countable, Iterator, ArrayAccess, SplObserver, DP_View_List_DataSource {
	/**
	 * @var Zend_Db_Select $query Holds the query to execute
	 * 
	 * May be replaced in future with table gateway object (Zend_Db_Table)
	 */
	protected $query;
	/**
	 * @var Zend_Db_Select $cq Query to find the full non limited count of rows.
	 */
	protected $cq;
	/**
	 * @var Array $columns Associative array column_name=>column_title (descriptive)
	 */
	protected $columns;
	
	/**
	 * @var Array $filters Array of DP_Filter objects.
	 */
	protected $filters;
	/**
	 * @var DP_Query_Sort $sorter The sort object holding the sort rules.
	 */
	protected $sorter;
	/**
	 * @var DP_View_Pager $pager The pager control indicating the current page.
	 */
	protected $pager;
	
	/**
	 * @var Array $object_list Array of rows generated by the query.
	 */
	protected $object_list;	
	/**
	 * @var Bool $needs_refresh If any modifier changes the list will need to be refreshed.
	 */
	protected $needs_refresh;
	/**
	 * @var Integer $object_iter_idx The index of the object iterator
	 */
	protected $object_iter_idx;
	
	public function __construct() {
		$this->object_list = Array();
		$this->needs_refresh = true;
		$this->columns = Array();
		
		$this->object_iter_idx = 0;
		
		$this->filters = Array();
		$this->sorter = Array();
	}

	/**
	 * Refresh the query results
	 * 
	 * Runs the stored query and sets the iscurrent flag to true,
	 * also takes care of any observed pager views.
	 */
	public function refresh() {
		$db = DP_Config::getDB();
		$stmt = $db->query($this->query);
		Zend_Debug::dump((string)$this->query);
		$result = $stmt->fetchAll();
		$this->object_list = $result;
		$this->needs_refresh = false;
		
		if ($this->pager instanceof DP_Pager) {
			$this->pager->setTotalItems($this->count());
		}
	}
	
	/**
	 * Add a modifier object. Eg. pager, keyword filter, sort order.
	 */
	public function addModifier($subject) {

		if ($subject instanceof DP_Pager) {
			// Add new pager
			$this->pager = $subject;
			$this->needs_refresh = true;

		} elseif ($subject instanceof DP_Query_Sort) {
			// Add new sorter
			$this->sorter = $subject;
			$this->needs_refresh = true;

		}  elseif ($subject instanceof DP_Filter) {
			// Add new filter			
			$filter_exists = false;
			foreach ($this->filters as $filter) {
				if ($filter->id() == $subject->id()) {
					$filter_exists = true;
				}
			}
			
			if ($filter_exists == false) {
				$this->filters[] = $subject;
				$this->needs_refresh = true;
			}
		}
		
		// We will receive updates from any modifier object
		$subject->attach($this);
	}
	
	/**
	 * Get the object list as an array.
	 * 
	 * @return array of rows, indexed.
	 */
	public function getArray() {
		if ($this->needs_refresh == false) {
			return $this->object_list;
		} else {
			return false;
		}
	}
	
	/**
	 * Get the current query
	 * 
	 * @return DP_Query query
	 */
	public function getQuery() {
		return $this->query;
	}
	
	/**
	 * Add a column to show.
	 * 
	 * @param string $col_name Database name of the column
	 * @param string $col_title Descriptive name of the column
	 */
	public function addColumn($col_name, $col_title) {
		$this->columns[$col_name] = $col_title;
	}
	
	/**
	 * Set column names and descriptions.
	 * 
	 * @param $columns array Associative array of column_name=>column_title
	 */
	public function setColumns($columns) {
		$this->columns = $columns;
	}
	
	/**
	 * Get column names and descriptions.
	 * 
	 * @return array Associative array of column_name=>column_title
	 */
	public function getColumns() {
		return $this->columns;
	}
	// From Countable
	
	/**
	 * Get the total number of records regardless of the pager.
	 * 
	 * @return Integer number of records
	 */
	public function count() {
		if ($this->cq != null) {

			$db = DP_Config::getDB();
			$stmt = $db->query($this->cq);
			$record_count = $stmt->fetchColumn(0);

			return $record_count;
		} else {
			return 0;
		}
	}
	
	/**
	 * Get the number of records returned for this page.
	 */
	public function pageItemCount() {
		if ($this->needs_refresh == false) {
			return count($this->object_list);
		} else {
			return null;
		}
	}

	// From Iterator
	
	/**
	 * Return the current element.
	 */
	public function current() {
		if ($this->needs_refresh == false) {
			return $this->object_list[$this->object_iter_idx];
		}
	}
	
	/**
	 * Return the key of the current element.
	 */
	public function key() {
		if ($this->needs_refresh == false) {
			return $this->object_iter_idx;
		}
	}
	
	/**
	 * Move forward to next element.
	 */
	public function next() {
		$this->object_iter_idx++;
	}
	
	/**
	 * Rewind the Iterator to the first element.
	 */
	public function rewind() {
		$this->object_iter_idx = 0;
	}
	
	/**
	 * Check if there is a current element after calls to rewind() or next().
	 */
	public function valid() {
		if ($this->needs_refresh == false) {
			if ($this->object_iter_idx < count($this->object_list)) {
				return true;
			} else {
				return false;
			}
		}
	}	
	
	// From ArrayAccess
	
	public function offsetExists($offset) {
		if ($this->needs_refresh == false && array_key_exists($offset, $this->object_list)) {
			return true;
		} else {
			return false;
		}
	}
	
	public function offsetGet($offset) {
		if ($this->needs_refresh == false) {
			return $this->object_list[$offset];
		} else {
			return false;
		}
	}
	
	public function offsetSet($offset, $value) {
		// we dont implement setting, read only values.
		return false;
	}
	
	public function offsetUnset($offset) {
		// we dont implement unset method either.
		return false;
	}
	
	// From DP_View_DataSource Interface
	
	
	// TODO - consider merging View_DataSource and View_Notification_Interface for notification of client rendering
	public function clientWillRender() {
		
		foreach($this->filters as $filter) {
			if ($filter instanceof DP_Filter) {
				foreach($filter->filters as $rule) {
					switch($rule['filter_type']) {
						case DP_Filter::VALUE_EQUAL:
							
							$this->query->where($rule['filter_field']." = ".$rule['field_value']);
							$this->cq->where($rule['filter_field']." = ".$rule['field_value']);

							break;
						case DP_Filter::VALUE_SUBSTR:
							$this->query->where($rule['filter_field']." LIKE '%".$rule['field_value']."%'");
							$this->cq->where($rule['filter_field']." LIKE '%".$rule['field_value']."%'");
							break;
					}
				}
			}
		}
		
		if ($this->sorter instanceof DP_Query_Sort) {
			foreach ($this->sorter as $field => $sort_rule) {
				switch($sort_rule) {
					case DP_Query_Sort::SORT_DESCENDING:
						$this->query->order($field.' DESC');
						break;
					case DP_Query_Sort::SORT_ASCENDING:
						$this->query->order($field.' ASC');
						break;
				}
			}
		}

		if ($this->pager instanceof DP_Pager) {
			$items_page = $this->pager->itemsPerPage();
			$page_num = $this->pager->page();
			$item_start = ($page_num - 1) * $items_page;
			
			$this->query->limit($items_page, $item_start);
		}

		try {
			$this->refresh();
		} catch (Exception $e) {
			echo $e->getMessage();
			die();
		}
	}
	
	// From SplObserver
	
	/**
	 * Called when a SplSubjects state has changed.
	 * 
	 * @param Object $subject The object that has changed.
	 */
	public function update(SplSubject $subject) {
		
		// If the list filters have changed, reset page to 1.
		if ($subject instanceof DP_Filter && $this->pager instanceof DP_Pager) {
			$this->pager->setPage(1);
		}
	}
}
?>