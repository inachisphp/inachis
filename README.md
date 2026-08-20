# inachis
ianchis is a framework for creating simple websites in PHP using symfony such as a blog.

## Requirements

- PHP 8.3 or above
- Optional: ImageMagick with libheif (for HEIC -> JPEG conversions)

## Installation

1. Download a [release package](https://github.com/inachisphp/inachis/releases), and extract on your intended server
2. Import dev/install/inachis.sql into your DBMS
3. Add database connection settings to `.env.local.php` and `INACHIS_MASTER_KEY`, defined by `php -r "echo 'base64:' . base64_encode(random_bytes(32)) . PHP_EOL;"`
4. Run:
 ```bash
APP_ENV=prod APP_DEBUG=0 composer install --no-dev --optimize-autoloader --no-interaction
npm install
npm run build
rm -rf {node_modules,dev}
```
5. [Create your first administrator](https://github.com/inachisphp/inachis/wiki/Configuration#create-you-first-administrator) and sign-in


## Updates

Check for updates without installing:
```bash
php bin/console inachis:system:update --check-only
```

Run interactively via SSH:
```bash
php bin/console inachis:system:update
```

Run non-interactively in a Cron job or CI deployment:
```bash
php bin/console inachis:system:update --force --no-interaction
```

Packages will contain files such as:

```
inachis-1.2.0.zip
├── manifest.json
├── bin/
├── config/
├── migrations/
├── public/
├── src/
├── templates/
└── vendor/
```

The manifest will look something like:
```json
{
  "name": "inachis",
  "version": "1.2.0",
  "build_date": "2026-07-30T12:00:00Z",
  "persistent": [],

  "requires": {
    "php": ">=8.4",
    "inachis": ">=1.1.0"
  },

  "files": {
    "src/Kernel.php": {
      "size": 12345,
      "sha256": "..."
    }
  },
}
```

Any Doctrine migrations not previously run will also be run when applying a package.