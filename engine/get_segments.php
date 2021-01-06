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
    
    $target_segments = str_replace(BASE_URL, '', $target_url);
    $target_segments = explode('/', $target_segments); 
    if(count($target_segments) > 20)
        return $target_url;
    $first_target_segment = $target_segments[0];    
    
    foreach (CUSTOM_ROUTES as $key => $value) {
        $key_segments = explode('/', $key); 
        // check that first segment matches    
        if ($key_segments[0] == $first_target_segment) {                     
            // remove the segments that are different
            $dif = array_diff($target_segments,$key_segments); 
            foreach($dif as $dif_key => $dif_value){
                unset($target_segments[$dif_key]);
            }             
            // rebuild target segments only allowing exact match to the route key 
            $key_count = count($key_segments); 
            for ($i = 0; $i < $key_count; $i++) {  
                if(isset($target_segments[$i]))              
                $matched_target_segments[] = $target_segments[$i];                
            }
            
            $target_string = implode('/', $matched_target_segments);  
                                     
            if($target_string == $key) {                                
                $additional_segmnets = implode('/',$dif);                        
                $target_url = BASE_URL.$value.$additional_segmnets;
                break;                
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