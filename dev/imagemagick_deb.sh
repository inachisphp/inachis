#!/bin/bash
set -e

# Update system
sudo apt update
sudo apt upgrade -y

# Install dependencies for ImageMagick with HEIC and WebP
sudo apt install -y build-essential \
libheif-dev libwebp-dev libjpeg-dev libpng-dev libtiff-dev \
libfreetype6-dev pkg-config wget

# Remove any old ImageMagick
sudo apt remove -y imagemagick

# Download latest ImageMagick source
cd /tmp
wget https://download.imagemagick.org/ImageMagick/download/ImageMagick.tar.gz
tar xvzf ImageMagick.tar.gz
cd ImageMagick-*

# Configure and compile ImageMagick with HEIC and WebP support
./configure --with-heic=yes --with-webp=yes --with-modules --with-quantum-depth=16
make -j$(nproc)
sudo make install
sudo ldconfig

# Install PHP development tools and Imagick
sudo apt install -y php-cli php-dev php-pear
sudo pecl install imagick

# Enable Imagick extension in PHP
PHP_CONF="/etc/php/$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')/cli/conf.d/20-imagick.ini"
echo "extension=imagick.so" | sudo tee "$PHP_CONF"

# Restart PHP-FPM if installed
if systemctl is-active --quiet php*-fpm; then
    sudo systemctl restart php*-fpm
fi

# Verification
echo "ImageMagick version and delegates:"
magick -version

php -r "echo 'Imagick version: '.phpversion('imagick').\"\nSupported formats: \".implode(', ', Imagick::queryFormats()).\"\n\";"
