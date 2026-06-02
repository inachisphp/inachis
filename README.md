# inachis
ianchis is a framework for creating simple websites in PHP using symfony such as a blog.

## Requirements

- PHP 8.3 or above
- Optional: ImageMagick with libheif (for HEIC -> JPEG conversions)

## Installation

1. Download a [release package](https://github.com/inachisphp/inachis/releases), and extract on your intended server
2. Import dev/install/inachis.sql into your DBMS
3. Add database connection settings to `.env.local.php` and `INACHIS_MASTER_KEY`, defined by `php -r "echo 'base64:' . base64_encode(random_bytes(32)) . PHP_EOL;"`
4. Run ```bash
APP_ENV=prod APP_DEBUG=0 composer install --no-dev --optimize-autoloader --no-interaction
npm install
npm run build
rm -rf {node_modules,dev}
```
5. [Create your first administrator](https://github.com/inachisphp/inachis/wiki/Configuration#create-you-first-administrator) and sign-in
