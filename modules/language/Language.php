<?php
class Language extends Trongate {

    /**
     * Default language fallback
     * * @var string
     */
    private string $default_language = 'en';

    /**
     * Cookie name
     * * @var string
     */
    private string $cookie_name = 'app_lang';

    /**
     * Cookie lifetime (30 days)
     * * @var int
     */
    private int $cookie_lifetime = 2592000;

    /**
     * Set the current language
     * * @param string $lang
     * @return void
     */
    public function set_language(string $lang): void {
        $_SESSION['app_lang'] = $lang;
        setcookie($this->cookie_name, $lang, time() + $this->cookie_lifetime, '/');
    }

    /**
     * Get the current language
     * Priority: Session > Cookie > Constant > Default
     * * @return string
     */
    public function get_language(): string {
        if (isset($_SESSION['app_lang']) && is_string($_SESSION['app_lang'])) {
            return $_SESSION['app_lang'];
        }

        if (isset($_COOKIE[$this->cookie_name]) && is_string($_COOKIE[$this->cookie_name])) {
            return $_COOKIE[$this->cookie_name];
        }

        if (defined('APP_LANG') && is_string(APP_LANG)) {
            return APP_LANG;
        }

        return $this->default_language;
    }

    /**
     * Reset language to default
     * * @return void
     */
    public function reset_language(): void {
        if (isset($_SESSION['app_lang'])) {
            unset($_SESSION['app_lang']);
        }

        if (isset($_COOKIE[$this->cookie_name])) {
            setcookie($this->cookie_name, '', time() - 3600, '/');
            unset($_COOKIE[$this->cookie_name]);
        }
    }

    /**
     * Load a language file
     * * Example:
     * modules/validation/language/{lang}/validation_errors.php
     * * @param string $path
     * @return array
     */
    public function load(string $path): array {
        $lang = $this->get_language();

        // Replace placeholder
        $path = str_replace('{lang}', $lang, $path);

        // Build full path
        $full_path = APPPATH . $path;

        if (!file_exists($full_path)) {
            return [];
        }

        require $full_path;

        return $phrases ?? [];
    }
}