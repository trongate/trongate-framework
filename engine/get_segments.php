<?php
function get_segments($ignore_custom_routes=NULL,$route_bypass=NULL) {

    $num_segments_to_ditch = get_num_segments_to_ditch();

    $assumed_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] .  $_SERVER['REQUEST_URI'];

    if (!isset($ignore_custom_routes)) {
        $assumed_url = attempt_add_custom_routes($assumed_url);
    }

    $data['assumed_url'] = $assumed_url;

    $segments = get_remaining_segments($assumed_url,$num_segments_to_ditch);
    $data['segments'] = array_values($segments);     
    return $data;
}

function get_remaining_segments($assumed_url,$num_segments_to_ditch){    

    $assumed_url = str_replace('://', '', $assumed_url);
    $assumed_url = rtrim($assumed_url, '/');

    $segments = explode('/', $assumed_url);    

    for ($i=0; $i < $num_segments_to_ditch; $i++) { 
        unset($segments[$i]);
    }

    return $segments;
}

function get_num_segments_to_ditch(){
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

    return $num_segments_to_ditch;
}

function attempt_add_custom_routes($target_url) {
    //takes a nice URL and returns the assumed_url
    //echo $target_url; die;
    
    // check to see if the route allows values to be passed.
    // if it does then check if the request url has additional segments, if so append to the target_url

    $target_segment = str_replace(BASE_URL, '', $target_url);
    //echo $target_segment; die;

    foreach (CUSTOM_ROUTES as $key => $value) {  

        // check for a wildcard, set value & strip target_segment
        if (strpos($value, '*') !== false) {
           $value_wild = str_replace('*','', $value);
           $segments = explode('/',$target_segment); 
           $first_segment = $segments[0];
        }      

        // check for segment match / match with wild card    
        if ($key == $target_segment || isset($value_wild)) {    

            if(isset($value_wild)){ 

                $num_segments_to_ditch = get_num_segments_to_ditch();                
                $additional_segments = get_remaining_segments($target_segment,$num_segments_to_ditch);
                                             
                // prepare segments as string and append
                $segment_string = "";
                foreach($additional_segments as $segment){
                    $segment_string .= "/".$segment;
                }

                $segment_string = rtrim($segment_string, '/');               
                $target_url = str_replace($key, $value_wild, $target_url);
                $target_url = $target_url.'/'.$segment_string;
                $target_url = rtrim($target_url, '/');
                unset($value_wild);                      
                
            } else {
                $target_url = str_replace($key, $value, $target_url);
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