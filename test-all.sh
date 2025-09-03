#!/usr/bin/env bash
SUPERMODEL_VERSION=4.0
PHP_VERSIONS="8.3 8.4"

for PHP_VERSION in $PHP_VERSIONS; do
    IMAGE_NAME="supermodel:$SUPERMODEL_VERSION-php-$PHP_VERSION"
    docker build \
        -t "$IMAGE_NAME" \
        -f "docker/Dockerfile.php-$PHP_VERSION" \
        .
    docker run "$IMAGE_NAME" vendor/bin/phpunit
done
