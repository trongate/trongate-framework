<?php
function get_segments($ignore_custom_routes=NULL) {

    //figure out how many segments needs to be ditched
    $psuedo_url = str_replace('://', '', BASE_URL);
    $psuedo_url = rtrim($psuedo_url, '/');
    $bits = explode('/', $psuedo_url);
    $num_bits = count($bits);

    if ($num_bits>1) {
        $num_segments_to_ditch = $num_bits-1;
    } else {
        $num_segments_to_ditch = 0;
    }

    $assumed_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] .  $_SERVER['REQUEST_URI'];

    if (!isset($ignore_custom_routes)) {
        $assumed_url = attempt_add_custom_routes($assumed_url);
    }

    $data['assumed_url'] = $assumed_url;

    $assumed_url = str_replace('://', '', $assumed_url);
    $assumed_url = rtrim($assumed_url, '/');

    $segments = explode('/', $assumed_url);

    for ($i=0; $i < $num_segments_to_ditch; $i++) { 
        unset($segments[$i]);
    }

    $data['segments'] = array_values($segments); 
    return $data;
}

function attempt_add_custom_routes($target_url) {
    //takes a nice URL and returns the assumed_url

    $target_segment = str_replace(BASE_URL, '', $target_url);


    foreach (CUSTOM_ROUTES as $key => $value) {

        if ($key == $target_segment) {
            $target_url = str_replace($key, $value, $target_url);
        }else if(strpos(explode('/',$key)[0], explode('/',$target_segment)[0]) > -1){
            $target_segment_pieces = explode('/',$target_segment);
            $custom_route_key = explode('/',$key);
            if(count($target_segment_pieces) == count($custom_route_key)){
                $url_probability = true;
                $new_custom_url = BASE_URL.$value;
                $correction_counter = 0;
                foreach ($target_segment_pieces as $segment_piece_key => $segment_piece_value) {                    
                    if( $segment_piece_value == $custom_route_key[$segment_piece_key]){
                        continue;
                    }else if($custom_route_key[$segment_piece_key] == '(:any)'){
                        $correction_counter++;
                        $new_custom_url = str_replace('$'.$correction_counter, $segment_piece_value, $new_custom_url);
                    }else if($custom_route_key[$segment_piece_key] == '(:num)' && is_numeric($segment_piece_value)){
                        $correction_counter++;
                        $new_custom_url = str_replace('$'.$correction_counter, $segment_piece_value, $new_custom_url);
                    }else{
                        $url_probability = false;
                        break;
                    }
                }
                if($url_probability == true){
                    $target_url = $new_custom_url;
                }
            }
        }
    }

    return $target_url;
}

function attempt_return_nice_url($target_url) {
    //takes an assumed_url and returns the nice_url

    foreach (CUSTOM_ROUTES as $key => $value) {

        $pos = strpos($target_url, $value);

        if (is_numeric($pos)) {
            $target_url = str_replace($value, $key, $target_url);
        }

    }

    return $target_url;
}

$data = get_segments();

define('SEGMENTS', $data['segments']);
define('ASSUMED_URL', $data['assumed_url']);
