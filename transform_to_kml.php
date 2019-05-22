<?php



function get_schools_from_file()
{
	$file = file_get_contents('schools_2.txt');

	$complete_pin_regex = '/\[et_pb_map_pin_extended(.*?)\[\/et_pb_map_pin_extended\]/ms';
	preg_match_all($complete_pin_regex, $file, $complete_pin_matches);

	// Creating arrays for storing pin attributes.
	$pin_titles = array();
	$pin_latitudes = array();
	$pin_longitudes = array();
	$pin_descriptions = array();
	$pin_schools_served_num = array();


	foreach($complete_pin_matches[0] as $complete_pin)
	{
		// Extracts title, latitude, and longitude from a pin.
		$pin_attributes_regex = '/title="([^"]+)" pin_address="[^"]+" pin_address_lat="([^"]+)" pin_address_lng="([^"]+)"/';
		preg_match($pin_attributes_regex, $complete_pin, $att_matches);
		array_push($pin_titles, $att_matches[1]);
		array_push($pin_latitudes, $att_matches[2]);
		array_push($pin_longitudes, $att_matches[3]);

		// Extracts a description from a pin.
		$description_regex = '/(?:(?!06"]).)*06"\](.*?)[<\[]/ms';
		preg_match($description_regex, $complete_pin, $desc_matches);

		// Extracting number of schools served from a description
		$schools_served_regex = '/[0-9]+/';
		preg_match($schools_served_regex, $desc_matches[1], $schools_served_matches);
		array_push($pin_schools_served_num, $schools_served_matches[0]);

		// Extracts images from a pin.
		$pin_imgs_regex = '/ src="([^"]+)"/ms';
		preg_match_all($pin_imgs_regex, $complete_pin, $img_matches);



		// Creating description from images and appending them to description array.
		$foundational_programs = array();
		$core_programs = array();
		$past_lab_grants = array();

		$intro_grant = '• Intro To Music Grant (preK-5)';
		$piano_grant = '• Keys + Kids Piano Grant (all grades)';
		$band_grant = '• Band Core Grant';
		$string_grant = '• Strings Core Grant';
		$mariachi_grant = '• Mariachi Core Grant';
		$guitar_lab = '• Guitar Lab';
		$key_lab = '• Keyboard Lab';

		foreach($img_matches[1] as $image)
		{
			switch($image) {
				case 'https://www.savethemusic.org/wp-content/uploads/2018/03/generalmusic_intromusic_iconv3.png':
					array_push($foundational_programs, $intro_grant);
					break;
				case 'https://www.savethemusic.org/wp-content/uploads/2018/03/piano_keyskids_iconv3.png':
					array_push($foundational_programs, $piano_grant);
					break;
				case 'https://www.savethemusic.org/wp-content/uploads/2018/03/Band_iconv3.png':
					array_push($core_programs, $band_grant);
					break;
				case 'https://www.savethemusic.org/wp-content/uploads/2018/03/strings_iconv3.png':
					array_push($core_programs, $string_grant);
					break;
				case 'https://www.savethemusic.org/wp-content/uploads/2018/03/mariachi_iconv3.png':
					array_push($core_programs, $mariachi_grant);
					break;
				case 'https://www.savethemusic.org/wp-content/uploads/2018/03/guitarLabiconv3.png':
					array_push($past_lab_grants, $guitar_lab);
					break;
				case 'https://www.savethemusic.org/wp-content/uploads/2018/03/KeyboardLabiconv3.png':
					array_push($past_lab_grants, $key_lab);
					break;

				default:
						;
			}
		}

		$complete_description = '';
		$a_new_line = '&#xD;';

		if (!empty($foundational_programs))
		{
			$complete_description
			.= 'Foundational Programs:'
			. $a_new_line
			. implode($a_new_line, $foundational_programs)
			. $a_new_line
			. $a_new_line;
		}
		if (!empty($core_programs))
		{
			$complete_description
			.= 'Core Programs (grades 3-8):'
			. $a_new_line
			. implode($a_new_line, $core_programs)
			. $a_new_line
			. $a_new_line;
		}
		if (!empty($past_lab_grants))
		{
			$complete_description
			.= 'Past Lab Grants:'
			. $a_new_line
			. implode($a_new_line, $past_lab_grants)
			. $a_new_line
			. $a_new_line;
		}

		array_push($pin_descriptions, $complete_description);
	}


	// Generating the keys for the school multidimentional array creation.
	$schools_sum = count($complete_pin_matches[0]);
	$schools_sum_prepared = --$schools_sum;
	$schools_sum_keys = range(0, $schools_sum_prepared);

	// Create a multidimentional array with every school as an object.
	$schools_group = array();
	$values = array($pin_titles,
					$pin_latitudes,
					$pin_longitudes,
					$pin_descriptions,
					$pin_schools_served_num,
					);
	foreach($schools_sum_keys as $index => $key)
	{
		$school = array();
		foreach($values as $value)
		{
			$school[] = $value[$index];
		}
		$schools_group[$key] = $school;
	}


	return $schools_group;
}
get_schools_from_file();


function transform_pins_to_placemarks()
{
	$schools = get_schools_from_file();

	$all_placemarks = "";

	foreach($schools as $school)
	{
		// Check if the school needs a "school with images" format.
		$title = $school[0];
		$latitude = $school[1];
		$longitude = $school[2];
		$description = $school[3];
		$schools_served = $school[4];

		$format = "
	  <Placemark>
	  	<name>%s</name>
		<styleUrl>#icon-1899-9766d2</styleUrl>
		<ExtendedData>
			<Data name='School District'>
				<value>%s</value>
			</Data>
			<Data name='Schools served'>
				<value>%d</value>
			</Data>
			<Data name='Grant types given'>
				<value>%s</value>
			</Data>
			<Data name='Learn more about our grant types'>
				<value>https://savethemusic.org/grants</value>
			</Data>
		</ExtendedData>
		<Point>
		  <coordinates>
			%f,%f
		  </coordinates>
		</Point>
	  </Placemark>";
		$all_placemarks .= sprintf($format,
									$title,
									$title,
									$schools_served,
									$description,
									$longitude,
									$latitude );
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
      	<color>ffd18802</color>
        <scale>1</scale>
        <Icon>
          <href>http://www.gstatic.com/mapspro/images/stock/503-wht-blank_maps.png</href>
        </Icon>
        <hotSpot x="32" xunits="pixels" y="64" yunits="insetPixels"/>
      </IconStyle>
      <LabelStyle>
        <color>ffd18802</color>
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

	$complete_output_file = $start_of_output_file
	.= $all_placemarks
	.= $end_of_output_file;

	return $complete_output_file;
}

function init()
{
	$new_file = 'map_version_2.kml';
	$output_file = create_output_file();
	file_put_contents($new_file, $output_file);
}

init();

