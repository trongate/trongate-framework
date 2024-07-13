<?php

class Trongate_localization extends Trongate
{
    #region Module scope
    public ?Localization_service $localizationService = null;

    public function _service(): Localization_service
    {
        if (!$this->localizationService) {
            require_once __DIR__ . '/../services/Localization_service.php';
            $this->localizationService = new Localization_service();
        }

        return $this->localizationService;
    }

    /**
     * Forward undefined calls to the underlying service.
     * due to the nature of trongate (methods starting with _ are not accessible via the URL),
     * so we can safely forward all calls to the service.
     *
     * @param $method
     * @param $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        $service = $this->_service();
        $service->load();

        return $service->$method(...$args);
    }
    #endregion

    #region Endpoints
    public function get_translations(): void
    {
        $service = $this->_service();

        $service->load();

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'locale' => $service->locale,
            'language' => $service->language(),
            'currency' => $service->currency,
            'languages' => $service->driver()->languages(),
            'translations' => $service->driver()->translations(),
        ]);
    }

    public function update_translations(): void
    {
        api_auth();

        $data = json_decode(file_get_contents('php://input'), true);
        $key = $data['translation_string'];

        $this->_service()->driver()->write($key, $data);

        http_response_code(200);
        echo json_encode([
            'translations' => $this->_service()->translations()
        ]);
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

        $data['view_module'] = 'trongate_localization';
        $data['view_file'] = 'manage';
        $data['t'] = $service->translate(...);
        $this->template('admin', $data);
    }

    #endregion
}