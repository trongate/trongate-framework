<?php
class url {

    public function segment($num) {

        $segments = SEGMENTS;
        if (isset($segments[$num])) {
            $value = $segments[$num];
        } else {
            $value = '';
        }

        return $value;
    }

}

function previous_url() {
    if (isset($_SERVER['HTTP_REFERER'])) {
        $url = $_SERVER['HTTP_REFERER'];
    } else {
        $url = '';
    }
    return $url;
}

function current_url() {
    $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] .  $_SERVER['REQUEST_URI']; 
    return $current_url;
}

function redirect($target_url) {

    $str = substr($target_url, 0, 4);
    if ($str != 'http') {
        $target_url = BASE_URL.$target_url;
    }

    header('location: '.$target_url);
    die();
}

function anchor($target_url, $text, $attributes=NULL, $additional_code=NULL) {

    $str = substr($target_url, 0, 4);
    if ($str != 'http') {
        $target_url = BASE_URL.$target_url;
    }

    $target_url = attempt_return_nice_url($target_url);

    $text_type = gettype($text);

    if ($text_type == 'boolean') {
        return $target_url;
    }

    $extra = '';
    if (isset($attributes)) {
        foreach ($attributes as $key => $value) {
            $extra.= ' '.$key.'="'.$value.'"';
        }
    }

    if (isset($additional_code)) {
        $extra.= ' '.$additional_code;
    }

    $link = '<a href="'.$target_url.'"'.$extra.'>'.$text.'</a>';
    return $link;
}

function url_title($string) {
    $string = trim($string);
    $string = preg_replace('/\s+/', ' ', $string);
    $string = preg_replace("/[^A-Za-z0-9 ]/", '', $string);
    $string = rawurlencode(utf8_encode($string));
    $string = preg_replace('/-+/', '-', $string);
    $string = str_replace("%20", '-', $string);
    return $string;
}

function api_auth() {
    //find out where the api.json file lives
    $validation_complete = false;
    $target_url = str_replace(BASE_URL, '', current_url());
    $segments = explode('/', $target_url);
    
    if ((isset($segments[0])) && (isset($segments[1]))) {
        $current_module = $segments[0];
        $filepath = APPPATH.'modules/'.$current_module.'/assets/api.json';

        if (file_exists($filepath)) {
            
            //extract the rules for the current path
            $target_method = $segments[1];
            $api_rules_content = file_get_contents($filepath);
            $target_str1 = '"url_segments": "'.$current_module.'/'.$target_method.'"';
            $target_str2 = '"request_type": "'.$_SERVER['REQUEST_METHOD'].'",';
            $target_str3 = '"authorization":';

            $api_rules = explode(': {', $api_rules_content);
            foreach ($api_rules as $key => $value) {

                if ((is_numeric(strpos($value, $target_str1))) && ((is_numeric(strpos($value, $target_str2)))) && ((is_numeric(strpos($value, $target_str3))))) {
                    //attempt to extract authorization rules for this endpoint
                    $previous_key = $key-1;
                    $previous_rule_block = $api_rules[$previous_key];
                    $bits = explode(',', $previous_rule_block);
                    $num_bits = count($bits);
                    $endpoint_name = $bits[$num_bits-1];
                    $endpoint_name = str_replace('{', '', $endpoint_name);
                    $endpoint_name = ltrim(trim(str_replace('"', '', $endpoint_name)));
                    
                    $token_validation_data['endpoint'] = $endpoint_name;
                    $token_validation_data['module_name'] = $current_module;
                    $token_validation_data['module_endpoints'] = $api_rules_content;
                    $api_class_location = APPPATH.'engine/Api.php';

                    if (file_exists($api_class_location)) {
                        include_once $api_class_location;
                        $api_helper = new Api;
                        $api_helper->_validate_token($token_validation_data);
                        $validation_complete = true;
                    }

                } 

            }

        }

    }

    if ($validation_complete == false) {
        http_response_code(401);
        echo "Invalid token."; die();
    }

}