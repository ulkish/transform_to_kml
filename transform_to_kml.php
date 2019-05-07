<?php


// (title)(pin_address)(pin_address_lat)(pin_address_lng)( # of schools served: (n))(src)*

// TODO:
//	1. Ask the user for the file name.
//	2. Create or ask for map name and description.
//	3. Return a file ready to be imported.
function get_schools_from_file()
{
	$file = file_get_contents('schools.txt');
	// Matches contents of title, latitude, longitude and description in that order.
	$pin_regex = '/title="([^"]+)" pin_address="[^"]+" pin_address_lat="([^"]+)" pin_address_lng="([^"]+)"(?:(?!# of).)*(# of schools served: [0-9]*)/';
	preg_match_all($pin_regex, $file, $matches);

	// Should be count($matches[0])
	$schools_sum = count($matches[0]);
	$schools_sum_prepared = --$schools_sum;
	$schools_sum_keys = range(0, $schools_sum_prepared);


	$schools_array = array();
	$values = array($matches[1], $matches[2], $matches[3], $matches[4]);
	foreach($schools_sum_keys as $index => $key)
	{
		$school = array();
		foreach($values as $value)
		{
			$school[] = $value[$index];
		}
		$schools_array[$key]  = $school;
	}

	return $schools_array;
}

function transform_pin_to_placemark()
{
	$schools = get_schools_from_file();
	foreach($schools as $school)
	{
		echo "A title: " . $school[0] . " and lat: " . $school[1] . " . ";
	}
}

transform_pin_to_placemark();


//print_r($school_list);
// A placemark looks like this:
//
 //  <Placemark>
	// <name>{$title}</name>
	// <description>{$description}</description>
	// <styleUrl>#icon-1899-0288D1</styleUrl>
	// <Point>
	//   <coordinates>
	// 	{$longitude},{$latitude}
	//   </coordinates>
	// </Point>
 //  </Placemark>
