<?php

// First regex:
// [et_pb_map_pin_extended](get this)[/et_pb_map_pin_extended]

// Second regex:
// (title)(pin_address)(pin_address_lat)(pin_address_lng)( # of schools served: (n))(src)*

$file = file_get_contents('schools.txt');
$first_pin_regex = '/\[et_pb_map_pin_extended(.*?)\/et_pb_map_pin_extended\]/ms';


preg_match_all($first_pin_regex, $file, $matches);

$all_matches = implode(' ', $matches[1]);

echo $all_matches;

// TODO:
//	1. Ask the user for the file name.
//	2. Create or ask for map name and description.
//	3. Return a file ready to be imported.
function get_schools_from_file()
{
	$file = file_get_contents('schools.txt');

	//$all_pins_from_file = '';
	//
	// Matches contents of title, latitude, longitude and description in that order.
	$pin_regex = '/title="([^"]+)" pin_address="[^"]+" pin_address_lat="([^"]+)" pin_address_lng="([^"]+)"(?:(?!# of).)*(# of schools served: [0-9]*)/';
	preg_match_all($pin_regex, $file, $matches);


	$schools_sum = count($matches[0]);
	$schools_sum_prepared = --$schools_sum;
	$schools_sum_keys = range(0, $schools_sum_prepared);

	// Create an array with every school as an object
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

	//print_r($schools_array);
	return $schools_array;
}

function transform_pins_to_placemarks()
{
	$schools = get_schools_from_file();

	$all_placemarks = "";

	foreach($schools as $school)
	{
		$title = $school[0];
		$latitude = $school[1];
		$longitude = $school[2];
		$description = $school[3];

		$format = "
	  <Placemark>
		<name>%s</name>
		<description>%s</description>
		<styleUrl>#icon-1899-9766d2</styleUrl>
		<Point>
		  <coordinates>
			%f,%f
		  </coordinates>
		</Point>
	  </Placemark>";
		$all_placemarks .= sprintf($format, $title, $description, $longitude, $latitude );
	}

	return $all_placemarks;
}

function create_output_file()
{
	$all_placemarks = transform_pins_to_placemarks();

	$start_of_output_file =
'<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Document>
    <name>Save The Music Impact Map</name>
    <description>
    	We’ve helped over 2,000 schools start music programs impacting millions of children.
	</description>
    <Style id="icon-1899-9766d2-normal">
      <IconStyle>
        <scale>1</scale>
        <Icon>
          <href>images/icon-1.png</href>
        </Icon>
        <hotSpot x="32" xunits="pixels" y="64" yunits="insetPixels"/>
      </IconStyle>
      <LabelStyle>
        <scale>0</scale>
      </LabelStyle>
    </Style>
    <Style id="icon-1899-9766d2-highlight">
      <IconStyle>
        <scale>1</scale>
        <Icon>
          <href>images/icon-1.png</href>
        </Icon>
        <hotSpot x="32" xunits="pixels" y="64" yunits="insetPixels"/>
      </IconStyle>
      <LabelStyle>
        <scale>1</scale>
      </LabelStyle>
    </Style>
    <StyleMap id="icon-1899-9766d2">
      <Pair>
        <key>normal</key>
        <styleUrl>#icon-1899-9766d2-normal</styleUrl>
      </Pair>
      <Pair>
        <key>highlight</key>
        <styleUrl>#icon-1899-9766d2-highlight</styleUrl>
      </Pair>
    </StyleMap>
    <Folder>
      <name>School districts</name>';

	$end_of_output_file = '
    </Folder>
  </Document>
</kml>';

	$complete_output_file = $start_of_output_file .= $all_placemarks .= $end_of_output_file;

	return $complete_output_file;
}

function init()
{
	$new_file = 'map.kml';
	$output_file = create_output_file();
	file_put_contents($new_file, $output_file);
}

//init();

