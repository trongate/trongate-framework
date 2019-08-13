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