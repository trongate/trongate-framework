<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'Localization_driver.php';

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
}