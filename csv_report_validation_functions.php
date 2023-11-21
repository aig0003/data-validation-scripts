<?php

class Log
{
	protected $result;
	protected $log;
	protected $warnings;

	public function __construct(bool $result=true, string $log='', string $warnings='')
	{
		$this->result = $result;
		$this->log = $log;
		$this->warnings = $warnings;
	}

	public function get_result()
	{
		return $this->result;
	}

	public function get_log(): ?string
	{
		return $this->log;
	}

	public function get_warnings(): ?string
	{
		return $this->warnings;
	}

	public function set_result(bool $result)
	{
		$this->result = $result;
	}

	public function set_log(string $log)
	{
		$this->log = $log;
	}

	public function set_warnings(string $warnings)
	{
		$this->warnings = $warnings;
	}

	public function append_log(string $log)
	{
		$this->log .= $log;
	}
}

/**
 * $file_name:
 * 		(string) file name of the desired CSV file in current working directory
 * 
 * return
 * 		Associative array of the rows & columns of the given CSV file, 
 * 		each row is represented by an associative array with column names as 
 * 		keys mapped to the corresponding value.
 */
function extract_csv_file_data(string $file_name)
{
	$file = file($file_name);

	if(!$file)
	{
		die("Unabled to load CSV file: '".$file_name."'\n");
	} 
	else
	{
		echo "Extracting CSV data from '".$file_name."'\n";
	}

	$rows = array_map('str_getcsv', $file);
	$header = array_shift($rows);
	$csv_data = array();

	foreach ($rows as $row)
	{
		$csv_data[] = array_combine($header, $row);
	}

	return $csv_data;
}

/* =============================================================================

DATA TESTING FUNCTIONS

============================================================================= */

/**
 * "Scans given csv data for missing values of given required keys"
 * 
 * $csv_data:
 * 		Associative array respresenting the rows/columns of the given CSV file,
 * 		each row is represented by an associative array with column names as 
 * 		keys mapped to the corresponding value.
 * $req_fields: 
 * 		Array with names of required column values
 *
 * return
 * 		Log object detailing results
 */
function is_no_required_values_missing($csv_data, $req_fields)
{
	$logs = new Log();

	$i = 2; // index begins at 2 to compensate for header row of CSV data

	foreach($csv_data as $row)
	{
		foreach($req_fields as $req_field)
		{
			if(!(bool) strlen($row[$req_field]))
			{
				$logs->set_result(false);
				$logs->append_log("  • (Row #".$i.") '".$req_field."' column: Empty Value.\n");
			}
		}
		$i++;
	}
	return $logs;
}

/**
 * "Scans given csv data for any unnecessary whitespacing"
 * 
 * $csv_data:
 * 		Associative array respresenting the rows/columns of the given CSV file,
 * 		each row is represented by an associative array with column names as 
 * 		keys mapped to the corresponding value.
 * 
 * return
 * 		Log object detailing results
 */
function is_no_superfluous_whitespace($csv_data)
{
	$logs = new Log();
	$i = 2;

	foreach($csv_data as $row)
	{
		foreach($row as $field => $col)
		{
			if( ctype_space($col) ) // true if value contains only whitespaces
			{
				$logs->set_result(false);
				$logs->append_log("  • (Row #".$i.") '".$field."' column: Value contains only whitespace.\n");
			}
			elseif( strlen($col) != strlen(trim($col)) ) // true if any values contain trailing/leading whitespace
			{
				$logs->set_result(false);
				$logs->append_log("  • (Row #".$i.") '".$field."' column: Trailing/leading whitespace.\n");
			}
		}
		$i++;
	}

	return $logs;
}

/**
 * "Scans given csv data for any rows which possess duplicates of a particular 
 * comparison value against instances of a base value.
 * (e.g. each unique username should only be assigned unique role values)"
 * 
 * $csv_data:
 * 		Associative array respresenting the rows/columns of the given CSV file,
 * 		each row is represented by an associative array with column names as 
 * 		keys mapped to the corresponding value.
 * 
 * return
 * 		Log object detailing results
 */
function is_no_redundant_pairings($csv_data, string $anchor_column_name, string $comparison_column_name)
{
	$logs = new Log();
	$i = 2;

	$paired_values = array();

	foreach($csv_data as $row)
	{
		$anchor_column_value = strtolower($row[$anchor_column_name]); # $case_insensitive ? strtolower($row[$anchor_column_name]) : $row[$anchor_column_name];
		$comparison_column_value = $row[$comparison_column_name];

		$paired_values[$anchor_column_value]['Compared Values'][$comparison_column_value][] = $i;

		$i++;
	}

	foreach($paired_values as $pair_value=>$paired_data)
	{
		foreach($paired_data['Compared Values'] as $compared_value=>$row_num)
		{
			if( isset($paired_data['Compared Values'][$compared_value]) &&
				count($paired_data['Compared Values'][$compared_value]) > 1 && 
				($pair_value !== '' && $compared_value !== '') )
			{
				$logs->set_result(false);
				$logs->append_log("  • ".$anchor_column_name." (".$pair_value.") paired with ".$comparison_column_name." (".$compared_value.") multiple times:\n");
				$logs->append_log("    Rows ".implode(', ', $paired_data['Compared Values'][$compared_value])."\n");
			}
		}
	}

	return $logs;
}

/*$csv_data = extract_csv_file_data("USERS.csv");
$req_values = array('Username', 'Role Name');
#$logs = is_no_required_values_missing($csv_data, $req_values);
#$logs = is_no_superfluous_whitespace($csv_data);
$logs = is_no_redundant_pairings($csv_data, 'Application Username', 'Role Name');*/

/*echo $logs->get_result() ? 'true' : 'false'."\n";
echo $logs->get_log()."\n";
echo $logs->get_warnings()."\n";*/

?>