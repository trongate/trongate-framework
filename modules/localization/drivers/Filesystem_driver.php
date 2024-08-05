<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'Localization_driver.php';

/**
 * Loads JSON translations into memory and offers functionality to search and update them.
 * TODO: Do we need to use an iterator instead of an array to reduce memory usage?
 */
class Filesystem_driver implements Localization_driver
{
    // ../assets/lang
    const LANG_PATH = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'lang';

    public array $languages = [];
    public array $translations = [];

    public function languages(): array
    {
        return $this->languages;
    }

    public function translations(): array
    {
        return $this->translations;
    }

    public function read(): Localization_driver
    {
        $fileIterator = new FilesystemIterator(self::LANG_PATH);

        foreach ($fileIterator as $file) {
            $language = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $this->languages[] = $language;

            // ../assets/lang/en.json
            $localePath = self::LANG_PATH . DIRECTORY_SEPARATOR . $language . '.json';
            $translations = file_get_contents($localePath);

            $this->translations[$language] = json_decode($translations, true);
        }

        return $this;
    }

    public function write(string $key, array $data): Localization_driver
    {
        foreach($this->languages as $language) {
            $localePath = self::LANG_PATH . DIRECTORY_SEPARATOR . $language . '.json';
            $translations = $this->translations[$language];
            $value = $data[$language] ?? '';

            $translations[$key] = $value;
            file_put_contents($localePath, json_encode($translations, JSON_PRETTY_PRINT));
            $this->translations[$language] = $translations;
        }

        return $this;
    }

    private function searchMatch(string $key, string $query): bool
    {
        // First try to match the key with stripos
        if (stripos($key, $query) !== false) {
            return true;
        }

        // Define a list of delimiters
        $delimiters = " ,.;\n";

        // Tokenize the key using the delimiters
        $token = strtok($key, $delimiters);
        while ($token !== false) {
            if (stripos($token, $query) !== false) {
                return true; // Token matches the query
            }
            $token = strtok($delimiters); // Continue with the next token
        }

        return false; // No match found
    }

    public function search(string $query): iterable
    {
        $results = [];

        foreach($this->translations as $language => $translations) {
            foreach($translations as $key => $value) {
                // $value may be a string or an array
                // If it's an array, we need to convert it to a string using dot notation
                if (is_array($value)) {
                    $value = implode('.', $value);
                }

                if ($this->searchMatch($key, $query) || $this->searchMatch($value, $query)) {
                    $results[$language][$key] = $value;
                }
            }
        }

        return $results;
    }
}