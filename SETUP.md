# Caribbean Gospel Radio HD — Server Setup
## Deploy to /var/www/html and Staging to /var/www/stage

---

## STEP 1 — Create the database

```bash
mysql -u root -p < api/setup.sql
```

Then create a dedicated DB user:
```sql
CREATE USER 'crhd_user'@'localhost' IDENTIFIED BY 'choose_strong_password';
GRANT ALL PRIVILEGES ON crhd.* TO 'crhd_user'@'localhost';
FLUSH PRIVILEGES;
```

---

## STEP 2 — Edit api/config.php

Change these values:
```php
define('DB_USER', 'crhd_user');
define('DB_PASS', 'choose_strong_password');  // match above
define('SESSION_SECRET', 'any_long_random_string_here');
```

---

## STEP 3 — Change admin password

After logging in with default credentials (admin / password), 
run this SQL to set your real password:
```sql
USE crhd;
UPDATE admin_users SET password_hash = '$2y$10$YOUR_HASH' WHERE username = 'admin';
```

Generate a hash with:
```php
<?php echo password_hash('your_new_password', PASSWORD_DEFAULT); ?>
```
Or use: https://bcrypt-generator.com

---

## STEP 4 — Deploy main site

```bash
# Copy files to web root
sudo cp -r /path/to/caribbeanradio/* /var/www/html/

# Create uploads directory
sudo mkdir -p /var/www/html/uploads
sudo chown www-data:www-data /var/www/html/uploads
sudo chmod 755 /var/www/html/uploads

# Enable Apache rewrite for API
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

## STEP 5 — Apache vhost for main site

```apache
<VirtualHost *:443>
    ServerName crhd4.com
    DocumentRoot /var/www/html

    <Directory /var/www/html>
        AllowOverride All
        Require all granted
    </Directory>

    # SSL (wildcard cert)
    SSLEngine on
    SSLCertificateFile    /etc/letsencrypt/live/crhd4.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/crhd4.com/privkey.pem
</VirtualHost>
```

---

## STEP 6 — Set up STAGING site at /var/www/stage

```bash
# Copy files
sudo cp -r /path/to/caribbeanradio/* /var/www/stage/

# Create staging uploads dir
sudo mkdir -p /var/www/stage/uploads
sudo chown www-data:www-data /var/www/stage/uploads

# Create separate staging database
mysql -u root -p -e "CREATE DATABASE crhd_stage;"
mysql -u root -p crhd_stage < api/setup.sql
mysql -u root -p -e "GRANT ALL ON crhd_stage.* TO 'crhd_user'@'localhost';"
```

Then copy `api/config.php` to `/var/www/stage/api/config.php` and change:
```php
define('DB_NAME', 'crhd_stage');
```

---

## STEP 7 — Apache vhost for staging

```apache
<VirtualHost *:443>
    ServerName stage.crhd4.com
    DocumentRoot /var/www/stage

    <Directory /var/www/stage>
        AllowOverride All
        Require all granted
    </Directory>

    # Same wildcard cert covers stage.crhd4.com
    SSLEngine on
    SSLCertificateFile    /etc/letsencrypt/live/crhd4.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/crhd4.com/privkey.pem
</VirtualHost>
```

```bash
sudo a2ensite stage.crhd4.com
sudo systemctl reload apache2
```

---

## How it works now

```
Any device → crhd4.com → /api/config → MySQL → same data everywhere
Admin (any device) → logs in → saves → MySQL → all visitors see update instantly
stage.crhd4.com → separate DB → safe to test without affecting live
```

## File structure on server

```
/var/www/html/          ← LIVE site
  index.html
  schedule.html
  css/main.css
  js/store.js           ← now talks to /api
  admin/
    index.html          ← requires login
    admin.css
  api/
    index.php           ← REST API router
    config.php          ← DB credentials
    setup.sql           ← run once
    .htaccess           ← routes /api/* to index.php
  uploads/              ← uploaded images stored here

/var/www/stage/         ← STAGING site (identical, separate DB)
```
