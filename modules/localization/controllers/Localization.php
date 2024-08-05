<?php

require_once  __DIR__ . DIRECTORY_SEPARATOR . 'Translate.php';

class Localization extends Trongate
{
    use Translate;

    #region Endpoints
    public function get_translations(): void
    {
        // Call the locale method to set the locale and currency
        $this->locale();

        $translations = $this->model->query(
            sql: 'SELECT `locale`, `key`, `value` FROM `localization`',
            return_type: 'object'
        );

        $locales = [];
        $map = [];

        foreach ($translations as $translation) {
            $locales[$translation->locale] = $translation->locale;
            $map[$translation->locale][$translation->key] = $translation->value;
        }

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'locale' => $this->locale,
            'currency' => $this->currency,
            'locales' => $locales,
            'translations' => $map,
        ]);
    }

    public function update_translations(): void
    {
        api_auth();

        $data = json_decode(file_get_contents('php://input'), true);
        $key = $data['translation_string'];

        $this->model->query_bind(
            sql: '
                INSERT INTO `localization` 
                (`locale`, `key`, `value`) 
                VALUES 
                (:locale, :key, :value)
                ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)
            ',
            data: [
                'value' => $data['value'],
                'locale' => $data['locale'],
                'key' => $key,
            ]
        );

        http_response_code(200);
    }
    #endregion
    
    #region Views
    function manage(): void {
        $this->module('trongate_security');
        $this->trongate_security->_make_sure_allowed();

        $service = $this->_service();
        $service->load();

        if (segment(2) !== '' && !empty($_GET['searchphrase'])) {
            $data['headline'] = $service->translate('Search Results');
            $searchphrase = trim($_GET['searchphrase']);
            $data['rows'] = $service->driver()->search($searchphrase);
        } else {
            $data['headline'] = $service->translate('Manage Localizations');
            $data['rows'] = $service->driver()->translations();
        }

        $data['view_module'] = 'Localization';
        $data['view_file'] = 'manage';
        $data['t'] = $service->translate(...);
        $this->template('admin', $data);
    }

    #endregion
}