<?php
function send_filter(string $arg=''): void
{
	global $U;
	print_start('filter');
	print_css('manfilter.css');
	echo '<h2>'._('Filter Management')."</h2><i>$arg</i>";
	
	// Add filter categories section

	
	// Add filter statistics
	$filters = get_filters();
	$total_filters = count($filters);
	$regex_filters = 0;
	$kick_filters = 0;
	$pm_allowed = 0;
	
	foreach($filters as $filter) {
		if($filter['regex'] == 1) $regex_filters++;
		if($filter['kick'] == 1) $kick_filters++;
		if($filter['allowinpm'] == 1) $pm_allowed++;
	}
	
	echo '<div class="filter-stats">';
	echo '<h3>'._('Filter Statistics').'</h3>';
	echo '<table class="stats-table">';
	echo '<tr><td>'._('Total Filters:').'</td><td>'.$total_filters.'</td></tr>';
	echo '<tr><td>'._('Regex Filters:').'</td><td>'.$regex_filters.'</td></tr>';
	echo '<tr><td>'._('Kick Filters:').'</td><td>'.$kick_filters.'</td></tr>';
	echo '<tr><td>'._('PM Allowed Filters:').'</td><td>'.$pm_allowed.'</td></tr>';
	echo '</table>';
	echo '</div>';
	
	
	// Main filter table
	echo '<a name="text-filters"></a>';
	echo '<h3>'._('Filter Management').'</h3>';
	echo '<table class="filter-table">';
	thr();
	echo '<tr><th><table><tr>';
	echo '<td>'._('Filter ID:').'</td>';
	echo '<td>'._('Match').'</td>';
	echo '<td>'._('Replace').'</td>';
	echo '<td>'._('Allow in PM').'</td>';
	echo '<td>'._('Regex').'</td>';
	echo '<td>'._('Kick').'</td>';
	echo '<td>'._('Case sensitive').'</td>';
	echo '<td>'._('Actions').'</td>';
	echo '</tr></table></th></tr>';
	
	// Group filters by type for better organization
	$text_filters = [];
	$regex_filters = [];
	$kick_filters = [];
	
	foreach($filters as $filter) {
		if($filter['kick'] == 1) {
			$kick_filters[] = $filter;
		} elseif($filter['regex'] == 1) {
			$regex_filters[] = $filter;
		} else {
			$text_filters[] = $filter;
		}
	}
	
	// Display text filters
	foreach($text_filters as $filter){
		display_filter_row($filter, $U);
	}
	
	echo '</table>';
	
	// Regex filters section
	echo '<a name="regex-filters"></a>';
	echo '<h3>'._('Regex Filters').'</h3>';
	echo '<table class="filter-table">';
	thr();
	echo '<tr><th><table><tr>';
	echo '<td>'._('Filter ID:').'</td>';
	echo '<td>'._('Match').'</td>';
	echo '<td>'._('Replace').'</td>';
	echo '<td>'._('Allow in PM').'</td>';
	echo '<td>'._('Regex').'</td>';
	echo '<td>'._('Kick').'</td>';
	echo '<td>'._('Case sensitive').'</td>';
	echo '<td>'._('Actions').'</td>';
	echo '</tr></table></th></tr>';
	
	foreach($regex_filters as $filter){
		display_filter_row($filter, $U);
	}
	
	echo '</table>';
	
	// Kick filters section
	echo '<a name="kick-filters"></a>';
	echo '<h3>'._('Kick Filters').'</h3>';
	echo '<table class="filter-table">';
	thr();
	echo '<tr><th><table><tr>';
	echo '<td>'._('Filter ID:').'</td>';
	echo '<td>'._('Match').'</td>';
	echo '<td>'._('Replace').'</td>';
	echo '<td>'._('Allow in PM').'</td>';
	echo '<td>'._('Regex').'</td>';
	echo '<td>'._('Kick').'</td>';
	echo '<td>'._('Case sensitive').'</td>';
	echo '<td>'._('Actions').'</td>';
	echo '</tr></table></th></tr>';
	
	foreach($kick_filters as $filter){
		display_filter_row($filter, $U);
	}
	
	echo '</table>';
	
	// Add new filter section
	echo '<div class="new-filter-section">';
	echo '<h3>'._('Add New Filter').'</h3>';
	echo form('admin', 'filter').hidden('id', '+');
	echo '<table class="new-filter-table"><tr>';
	echo '<td>'._('Match:').'</td>';
	echo '<td><input type="text" name="match" value="" size="20" style="'.$U['style'].'"></td>';
	echo '</tr><tr>';
	echo '<td>'._('Replace:').'</td>';
	echo '<td><input type="text" name="replace" value="" size="20" style="'.$U['style'].'"></td>';
	echo '</tr><tr>';
	echo '<td>'._('Options:').'</td>';
	echo '<td>';
	echo '<label><input type="checkbox" name="allowinpm" id="allowinpm" value="1">'._('Allow in PM').'</label><br>';
	echo '<label><input type="checkbox" name="regex" id="regex" value="1">'._('Regex').'</label><br>';
	echo '<label><input type="checkbox" name="kick" id="kick" value="1">'._('Kick').'</label><br>';
	echo '<label><input type="checkbox" name="cs" id="cs" value="1">'._('Case sensitive').'</label>';
	echo '</td>';
	echo '</tr><tr>';
	echo '<td colspan="2" class="filtersubmit">'.submit(_('Add New Filter')).'</td>';
	echo '</tr></table>';
	echo '</form>';
	echo '</div>';
	echo '<br>';
	form('admin', 'filter').submit(_('Reload Page')).'</form>';
	print_end();
}

// Helper function to display a filter row
function display_filter_row($filter, $U) {
	$check = ($filter['allowinpm'] == 1) ? ' checked' : '';
	$checked = ($filter['regex'] == 1) ? ' checked' : '';
	$checkedk = ($filter['kick'] == 1) ? ' checked' : '';
	$checkedcs = ($filter['cs'] == 1) ? ' checked' : '';
	
	if($filter['regex'] != 1) {
		$filter['match'] = preg_replace('/(\\\\(.))/u', "$2", $filter['match']);
	}
	
	echo '<tr><td>';
	echo form('admin', 'filter').hidden('id', $filter['id']);
	echo '<table><tr><td>'._('Filter')." $filter[id]:</td>";
	echo '<td><input type="text" name="match" value="'.$filter['match'].'" size="20" style="'.$U['style'].'"></td>';
	echo '<td><input type="text" name="replace" value="'.htmlspecialchars($filter['replace']).'" size="20" style="'.$U['style'].'"></td>';
	echo '<td><label><input type="checkbox" name="allowinpm" value="1"'.$check.'>'._('Allow in PM').'</label></td>';
	echo '<td><label><input type="checkbox" name="regex" value="1"'.$checked.'>'._('Regex').'</label></td>';
	echo '<td><label><input type="checkbox" name="kick" value="1"'.$checkedk.'>'._('Kick').'</label></td>';
	echo '<td><label><input type="checkbox" name="cs" value="1"'.$checkedcs.'>'._('Case sensitive').'</label></td>';
	echo '<td class="filtersubmit">'.submit(_('Update')).'</td></tr></table></form>';
}