<?php

declare(strict_types=1);

class Template
{
    public static function get_view_module()
    {
        //attempt to get view_module from URL

        $url = str_replace(BASE_URL, '', current_url());
        $url = filter_var($url, FILTER_SANITIZE_URL);
        $url_bits = explode('/', $url);

        if (isset($url_bits[0])) {
            $view_module = $url_bits[0];
        } else {
            $view_module = DEFAULT_MODULE;
        }

        return $view_module;
    }

    public static function display($data = null): void
    {
        if (! isset($data['view_module'])) {
            $data['view_module'] = self::get_view_module();
        }

        if (! isset($data['view_file'])) {
            $data['view_file'] = 'index';
        }

        $file_path = APPPATH.'modules/'.$data['view_module'].'/views/'.$data['view_file'].'.php';
        self::attempt_include($file_path, $data);
    }

    public static function partial($file_name, $data = null): void
    {
        $file_path = APPPATH.'templates/views/'.$file_name.'.php';
        self::attempt_include($file_path, $data);
    }

    private static function attempt_include($file_path, $data = null): void
    {
        if (file_exists($file_path)) {
            if (isset($data)) {
                extract($data);
            }

            require_once $file_path;
        } else {
            exit('<br><b>ERROR:</b> View file does not exist at: '.$file_path);
        }
    }
}
