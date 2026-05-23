# Trongate

A zero-dependency PHP framework that includes CSS and JavaScript libraries.

## Why Trongate?

- High performance ([benchmarks](https://trongate.io/benchmarks))
- Stability-focused
- Zero dependencies
- No Composer

## Requirements

- PHP 8+
- `gd` module loaded in `php.ini` (for image uploader)
- `intl` module loaded in `php.ini` (for `url_title()` string helper)
- MariaDB or MySQL

## Installation

Download the latest release from the [Trongate GitHub repository](https://github.com/trongate/trongate-framework) and extract it into your web server's document root (e.g., `C:\xampp\htdocs` on Windows or `/var/www/html/` on Linux). 

Rename the extracted folder to something simple, like `trongate`.

Edit `config/config.php` and set `BASE_URL` to the URL where the framework will run, e.g., `http://localhost/trongate/`. Ensure it ends with **`/`**.

### Web Server Configuration

**Apache:** No changes required. The included `.htaccess` files handle routing.

**Nginx:** In `/etc/nginx/sites-available/default`, inside the main `server{...}` block, add:

```nginx
location /trongate/ {
    alias /var/www/html/trongate/public/;
    try_files $uri $uri/ /trongate/index.php;
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $request_filename;
    }
}
```

### Database Setup

On Linux, the `root` user often cannot connect over TCP, so create a dedicated database user:

```sql
sudo mariadb
CREATE USER 'trongate'@'localhost' IDENTIFIED BY 'secret';
GRANT ALL PRIVILEGES ON demo.* TO 'trongate'@'localhost';
FLUSH PRIVILEGES;
```

Replace these values with your own credentials.

Update `config/database.php` accordingly.

### Critical Production Settings

Before you deploy to production, perform the checks in [Pre-Launch Checklist](https://trongate.io/documentation/trongate_php_framework/tips-and-best-practices/pre-launch-checklist).

## Usage

Trongate apps use **modules** containing controllers and optional views. Routing is simple: 
`{module}/{method}/{...params}` maps to a controller method.

### Example: A Minimal Full‑Stack Cycle

Create a module:

**`modules/demo`**

**NOTE:** do not include hyphens (`-`) or `_module` in module names.

Create a database and table:

```sql
CREATE DATABASE demo;
USE demo;

CREATE TABLE demo_table (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);
```

Add a controller:

**`modules/demo/Demo.php`**

```php
<?php
class Demo extends Trongate {
    public function index(): void {
        $data['records'] = $this->db->get('id', 'demo_table', 'ASC', 'array');
        $this->templates->public($data); // Wraps default 'index' view with 'public' HTML template
    }

    public function create(): void {
        $this->validation->set_rules('name', 'name', 'required|min_length[3]');
        if ($this->validation->run() === true) { // Also checks CSRF
            $this->db->insert([
                'name' => post('name', true)
            ], 'demo_table');
        }
        redirect('demo');
    }
    
    public function delete(): void {
    	$id = segment(3, 'int');
    	if ($id > 0){
    		$this->db->delete($id, 'demo_table');
    	}
    	redirect('demo');
    }
}
```

Add a view:

**`modules/demo/views/index.php`**

```php
<div id="response" class="container text-left"><!-- Trongate CSS classes -->
	<h1>Demo</h1>
		<ul>
		<?php foreach ($records as $r): ?>
		<li><?= out($r['name']) ?>  <!-- Escape HTML output -->
  			<button class="danger" mx-post="<?= BASE_URL ?>demo/delete/<?= $r['id'] ?>" mx-trigger="click" mx-target="#response">Delete</button>
  		</li>
		<?php endforeach; ?>
		</ul>
		

		<?= validation_errors() ?>		
		<form mx-post="<?= BASE_URL ?>demo/create" mx-target="#response" action="#" method="post"><!-- AJAX form submission -->
  			<input name="name" placeholder="Name" autocomplete="off">
  			<button type="submit" name="submit">Add</button>
		<?= form_close() ?>	<!-- CSRF token -->
</div>

<!-- Import Trongate MX JS library -->
<script src="<?= BASE_URL ?>js/trongate-mx.js"></script>

```

Visit:

```
http://localhost/trongate/demo
```

You now have a working module that reads from the database, displays records, validates input, and inserts and deletes data.

## Resources

[Documentation](https://trongate.io/documentation)

[Discussion Forums](https://trongate.io/forums)

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

## License

Released under the [MIT License](http://opensource.org/licenses/MIT). See `license.txt` for more details.
