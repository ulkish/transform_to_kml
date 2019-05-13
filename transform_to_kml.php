<?php



function get_schools_from_file()
{
	$file = file_get_contents('schools.txt');

	$complete_pin_regex = '/\[et_pb_map_pin_extended(.*?)\[\/et_pb_map_pin_extended\]/ms';
	preg_match_all($complete_pin_regex, $file, $complete_pin_matches);

	// Creating arrays for storing pin attributes.
	$pin_titles = array();
	$pin_latitudes = array();
	$pin_longitudes= array();
	$pin_descriptions = array();


	foreach($complete_pin_matches[0] as $complete_pin)
	{
		// Extracts title, latitude, and longitude from every pin.
		$pin_attributes_regex = '/title="([^"]+)" pin_address="[^"]+" pin_address_lat="([^"]+)" pin_address_lng="([^"]+)"/';
		preg_match($pin_attributes_regex, $complete_pin, $att_matches);
		// Pushing attributes to their corresponding arrays
		array_push($pin_titles, $att_matches[1]);
		array_push($pin_latitudes, $att_matches[2]);
		array_push($pin_longitudes, $att_matches[3]);

		// Extract descriptions from every pin.
		$description_regex = '/(?:(?!06"]).)*06"\](.*?)[<\[]/ms';
		preg_match($description_regex, $complete_pin, $desc_matches);
		$pin_description = $desc_matches[1] . '<br>';

		// Extracts images from every pin.
		$pin_imgs_regex = '/ src="([^"]+)"/ms';
		preg_match_all($pin_imgs_regex, $complete_pin, $img_matches);
		$images_as_text = '';
		foreach($img_matches[1] as $image)
		{
			switch($image) {
				case 'http://vh1savethemusic.dev9.tipit.net/wp-content/uploads/2018/03/generalmusic_intromusic_iconv3.png':
					$images_as_text .= '• Intro To Music Grant <br>';
					break;
				case 'http://vh1savethemusic.dev9.tipit.net/wp-content/uploads/2018/03/Band_iconv3.png':
					$images_as_text .= '• Band Core Grant <br>';
					break;
				case 'http://vh1savethemusic.dev9.tipit.net/wp-content/uploads/2018/03/strings_iconv3.png':
					$images_as_text .= '• Strings Core Grant <br>';
					break;
				case 'http://vh1savethemusic.dev9.tipit.net/wp-content/uploads/2018/03/mariachi_iconv3.png':
					$images_as_text .= '• Mariachi Core Grant <br>';
					break;
				case 'http://vh1savethemusic.dev9.tipit.net/wp-content/uploads/2018/03/guitarLabiconv3.png':
					$images_as_text .= '• Guitar Lab <br>';
					break;
				case 'http://vh1savethemusic.dev9.tipit.net/wp-content/uploads/2018/03/KeyboardLabiconv3.png':
					$images_as_text .= '• Keyboard Lab <br>';
					break;
				case 'http://vh1savethemusic.dev9.tipit.net/wp-content/uploads/2018/03/piano_keyskids_iconv3.png':
					$images_as_text .= '• Keys + Kids Piano Grant <br>';
					break;
				default:
					$images_as_text .= '';
			}
		}
		// Cleaning, formatting and finally appending image names to pin descriptions.
		$cleaned_pin_description = preg_replace( "/\r|\n/", " ", $pin_description);

		$cleaned_pin_description .= $images_as_text;

		$formatted_pin_description = '<![CDATA[' . $cleaned_pin_description . ']]>';
		array_push($pin_descriptions, $formatted_pin_description);
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
					);
	foreach($schools_sum_keys as $index => $key)
	{
		$school = array();
		foreach($values as $value)
		{
			$school[] = $value[$index];
		}
		$schools_group[$key]  = $school;
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

init();

