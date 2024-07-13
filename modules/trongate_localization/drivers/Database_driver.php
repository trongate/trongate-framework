<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'Localization_driver.php';

class Database_driver implements Localization_driver
{
    private Model $model;

    protected array $languages = [];
    protected array $translations = [];

    public function __construct()
    {
        $this->model = new Model('trongate_localization');
    }

    public function languages(): iterable
    {
        return $this->languages;
    }

    public function translations(): iterable
    {
        return $this->translations;
    }

    public function read(): Localization_driver
    {
        $translations = $this->model->query(
            sql: '
                SELECT * 
                FROM `trongate_localization` 
                ORDER BY `language` ASC, `key` ASC
            ',
            return_type: 'array'
        );

        // Group by language
        foreach ($translations as $translation) {
            if (!in_array($translation['language'], $this->languages)) {
                $this->languages[] = $translation['language'];
            }

            $this->translations[$translation['language']][$translation['key']] = $translation['value'];
        }

        return $this;
    }

    public function write(string $key, array $data): Localization_driver
    {
        $this->model->query('START TRANSACTION');

        try {
            foreach($this->languages as $language) {
                $this->model->query_bind(
                    sql: '
                    INSERT INTO `trongate_localization` 
                    (`language`, `key`, `value`) 
                    VALUES 
                    (:language, :key, :value) 
                    ON DUPLICATE KEY UPDATE 
                    `value` = :value
                ',
                    data: [
                        ':language' => $language,
                        ':key' => $key,
                        ':value' => $data[$language] ?? ''
                    ]
                );
            }
        } catch (RuntimeException $e) {
            $this->model->query('ROLLBACK');
            throw $e;
        }

        $this->model->query('COMMIT');

        return $this;
    }

    public function search(string $query): iterable
    {
        $translations = $this->model->query_bind(
            sql: '
                SELECT * 
                FROM `trongate_localization` 
                WHERE `key` LIKE :query 
                OR `value` LIKE :query
                ORDER BY `language` ASC, `key` ASC
            ',
            data: [
                ':query' => "%$query%"
            ],
            return_type: 'array'
        );

        // Group by language
        $results = [];
        foreach ($translations as $translation) {
            $results[$translation['language']][] = $translation;
        }

        return $results;
    }
}