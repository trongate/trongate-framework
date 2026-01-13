<?php
class Endpoint_listener extends Trongate {

	/**
	 * Display the endpoint_listener log in a styled table.
	 *
	 * @return void
	 */
	public function index(): void {
		$sql = 'SELECT * FROM endpoint_listener';
		$data['rows'] = $this->db->query($sql, 'object');

//	    $data['rows'] = $this->db->get('id', 'endpoint_listener');
	    $this->view('display_records', $data);
	}

	/**
	 * Record an inbound HTTP request to the endpoint_listener table.
	 *
	 * @return void
	 */
	public function record(): void {
	    // Build full URL
	    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
	    $host   = $_SERVER['HTTP_HOST'] ?? '';
	    $uri    = $_SERVER['REQUEST_URI'] ?? '/';
	    $url    = $scheme . '://' . $host . $uri;

	    // Skip any request whose first path segment is 'endpoint_listener'
	    $special_str = BASE_URL.'endpoint_listener';

	    if (strpos($url, $special_str) !== false) {
	    	return;
	    }

	    // Collect headers
	    $headers = [];
	    foreach ($_SERVER as $k => $v) {
	        if (str_starts_with($k, 'HTTP_')) {
	            $headers[str_replace('_', '-', substr($k, 5))] = $v;
	        }
	    }

	    // Robust payload fetch
	    $payload = $this->get_request_payload();

	    // Ensure payload is always a string (JSON-encoded when necessary)
	    $payloadString = is_array($payload) || is_object($payload)
	        ? json_encode($payload, JSON_UNESCAPED_SLASHES)
	        : $payload;

	    $insert = [
	        'url'          => $url,
	        'request_type' => $_SERVER['REQUEST_METHOD'] ?? '',
	        'ip_address'   => $_SERVER['REMOTE_ADDR'] ?? '',
	        'user_agent'   => $_SERVER['HTTP_USER_AGENT'] ?? null,
	        'referrer'     => $_SERVER['HTTP_REFERER'] ?? null,
	        'headers'      => json_encode($headers, JSON_UNESCAPED_SLASHES),
	        'payload'      => $payloadString,
	        'date_created' => time()
	    ];

	    $this->db->insert($insert, 'endpoint_listener');
	}

	/* ----------------------------------------------------------
	 * Helper: return the *whole* request body – JSON-decoded if
	 * application/json, otherwise $_POST or raw body.
	 * ---------------------------------------------------------- */
	private function get_request_payload(): mixed {
	    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

	    if (stripos($contentType, 'application/json') !== false) {
	        $raw = file_get_contents('php://input');
	        $decoded = json_decode($raw, true);
	        return json_last_error() === JSON_ERROR_NONE ? $decoded : $raw;
	    }

	    // Fallback: form data or empty array
	    return $_POST ?: file_get_contents('php://input') ?: null;
	}

	/**
	 * Remove all rows from the endpoint_listener table.
	 *
	 * @return void
	 */
	public function clear(): void {
		$sql = 'DELETE FROM endpoint_listener';
		$this->db->query($sql);

		$sql2 = 'TRUNCATE endpoint_listener';
		$this->db->query($sql2);

		redirect('endpoint_listener');
	}

}