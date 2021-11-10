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
    $target_url = rtrim($target_url, '/');
    $target_segments_str = str_replace(BASE_URL,'', $target_url);
    $target_segments = explode('/',$target_segments_str);

    foreach (CUSTOM_ROUTES as $custom_route => $custom_route_destination) {
        $custom_route_segments = explode('/',$custom_route);
        if(count($target_segments) == count($custom_route_segments)){
            if ($custom_route == $target_segments_str) { //perfect match; return immediatly
                $target_url = str_replace($custom_route, $custom_route_destination, $target_url);
                break;
            }
            $abort_route_check = false;
            $correction_counter = 0;
            $new_custom_url = rtrim(BASE_URL.$custom_route_destination,'/');
            for ($i=0; $i < count($target_segments); $i++) { 
                if($custom_route_segments[$i] == $target_segments[$i]){
                }else if($custom_route_segments[$i] == "(:num)" && is_numeric($target_segments[$i]) ){
                    $correction_counter++;
                    $new_custom_url = str_replace('$'.$correction_counter, $target_segments[$i], $new_custom_url);
                }else if($custom_route_segments[$i] == "(:any)"){
                    $correction_counter++;
                    $new_custom_url = str_replace('$'.$correction_counter, $target_segments[$i], $new_custom_url);
                }else{
                    $abort_route_check = true;
                    break;
                }
            }
            if(!$abort_route_check){
                $target_url = $new_custom_url;    
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
