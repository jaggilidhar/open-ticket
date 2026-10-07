# Ubuntu 22.04 / 24.04 installation

This CMS needs PHP 8.2+; Ubuntu 24.04 provides a compatible PHP by default. On Ubuntu 22.04, arrange a supported PHP 8.2+ installation with your server administrator before continuing; do not assume its default PHP is sufficient.

For Ubuntu 24.04:

```sh
sudo apt update
sudo apt install nginx mariadb-server php-fpm php-mysql unzip
sudo mkdir -p /var/www/openticket
```

Upload the application ZIP and extract it into `/var/www/openticket`. Give the web server read access and ownership of the storage folder:

```sh
sudo chown -R root:www-data /var/www/openticket
sudo find /var/www/openticket -type d -exec chmod 0750 {} \;
sudo find /var/www/openticket -type f -exec chmod 0640 {} \;
sudo chown -R www-data:www-data /var/www/openticket/storage
sudo chmod 0750 /var/www/openticket/storage
sudo mariadb
```

In MariaDB, replace the example password:

```sql
CREATE DATABASE openticket CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'openticket'@'localhost' IDENTIFIED BY 'REPLACE-WITH-A-LONG-RANDOM-PASSWORD';
GRANT ALL PRIVILEGES ON openticket.* TO 'openticket'@'localhost';
EXIT;
```

Configure Nginx for your actual domain. Point the domain's DNS at your VPS. Use your installed PHP-FPM socket, commonly `/run/php/php8.3-fpm.sock` on Ubuntu 24.04.

```nginx
server {
    listen 80;
    server_name tickets.example.com;
    root /var/www/openticket;
    index index.php;
    autoindex off;

    location / { try_files $uri $uri/ =404; }
    location ~ ^/(app|storage|docs|tests|scripts)(/|$) { return 404; }
    location ~ /\. { deny all; }
    location ~ \.php$ {
        try_files $uri =404;
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
```

Nginx does not read `.htaccess`; the protected-directory rules above are essential. Put protected-directory rules before the generic PHP location. For a subfolder installation, adjust them to that subfolder. Do not proxy untrusted client-supplied identity headers; this CMS uses native sessions.

Enable the configuration, run `sudo nginx -t`, and reload Nginx. Install an HTTPS certificate using your hosting provider or Certbot before opening the installer. If using a reverse proxy, configure PHP to receive the real HTTPS state through trusted server configuration; do not blindly trust forwarded headers from the internet.

Open the domain and follow README.md. The one-time install key can be read using your SFTP client or `sudo cat /var/www/openticket/storage/install-key.php` after the first visit. Never share this key or the config file publicly.

Back up MariaDB with your normal database backup process and back up `storage/config.php` privately. Restrict public firewall access to necessary HTTP/HTTPS and administrative SSH ports.
