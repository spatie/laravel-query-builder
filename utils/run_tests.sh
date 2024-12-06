#!/bin/bash

# Exit on any error
set -euo pipefail

# Define constants
readonly PREFIX="laravel-query-builder"
readonly DEFAULT_PHP_VERSION="8.3"
readonly DEFAULT_LARAVEL_VERSION="11.*"

# Function to display help message
show_help() {
    echo "Usage: $0 [options]"
    echo ""
    echo "Options:"
    echo "  -h, --help     Display this help message"
    echo "  -v, --version  Display version information"
    echo "  -p PHP_VERSION Set the PHP version (default: ${DEFAULT_PHP_VERSION})"
    echo "  -l LARAVEL_VERSION Set the Laravel version (default: ${DEFAULT_LARAVEL_VERSION})"
    echo "  --filter FILTER  Specify test filter(s)"
    echo ""
    echo "Example:"
    echo "  $0 --filter FieldsTest"
}

# Parse command-line arguments
while [[ $# -gt 0 ]]; do
    case "$1" in
        -h|--help)
            show_help
            exit 0
            ;;
        -v|--version)
            echo "Version: 1.0"
            exit 0
            ;;
        -p|--php-version)
            shift
            PHP_VERSION="$1"
            ;;
        -l|--laravel-version)
            shift
            LARAVEL_VERSION="$1"
            ;;
        --filter)
            shift
            FILTER="$1"
            ;;
        *)
            echo "Unknown option: $1" >&2
            exit 1
            ;;
    esac
    shift
done

# Set default values if not provided
PHP_VERSION="${PHP_VERSION:-$DEFAULT_PHP_VERSION}"
LARAVEL_VERSION="${LARAVEL_VERSION:-$DEFAULT_LARAVEL_VERSION}"

# Ensure we're in the project root
cd "$(dirname "$0")"

# Create a custom Docker network
DOCKER_NETWORK_NAME="${PREFIX}-network"
docker network create "${DOCKER_NETWORK_NAME}" || true

# Function to remove and recreate a container if it exists
recreate_container() {
    local container_name="$1"
    
    # Remove the container if it exists (forcefully)
    if docker ps -a --format '{{.Names}}' | grep -q "^${container_name}$"; then
        echo "Removing existing container: ${container_name}"
        docker rm -f "${container_name}"
    fi
}

# Prepare container names with prefix
MYSQL_CONTAINER_NAME="${PREFIX}-mysql"
REDIS_CONTAINER_NAME="${PREFIX}-redis"
TEST_RUNNER_IMAGE_NAME="${PREFIX}-test-runner"
TEST_CONTAINER_NAME="${PREFIX}-test-runner-container"

# Recreate containers
recreate_container "${MYSQL_CONTAINER_NAME}"
recreate_container "${REDIS_CONTAINER_NAME}"
recreate_container "${TEST_CONTAINER_NAME}"

# Set project root (parent of script directory)
PROJECT_ROOT="$(dirname "$(pwd)")"

# Build the Docker image
docker build -t "${TEST_RUNNER_IMAGE_NAME}" -f - "$PROJECT_ROOT" <<EOF
FROM php:$PHP_VERSION-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libmagickwand-dev \
    libmcrypt-dev \
    libreadline-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    default-mysql-client \
    redis-tools

# Install PHP extensions
RUN docker-php-ext-install \
    dom \
    curl \
    xml \
    mbstring \
    zip \
    pcntl \
    pdo \
    pdo_mysql \
    bcmath \
    intl \
    gd \
    exif \
    iconv

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project files
COPY . /app

# Set working directory
WORKDIR /app

# Install dependencies
RUN composer require "laravel/framework:${LARAVEL_VERSION}" "orchestra/testbench:9.*" --no-interaction --no-update
RUN composer update --prefer-dist --no-interaction
EOF

# Run MySQL container
docker run -d --name "${MYSQL_CONTAINER_NAME}" \
    --network "${DOCKER_NETWORK_NAME}" \
    -e MYSQL_ROOT_PASSWORD=secretroot \
    -e MYSQL_DATABASE=laravel_query_builder \
    -e MYSQL_USER=user \
    -e MYSQL_PASSWORD=secret \
    mysql:8.0

# Run Redis container
docker run -d --name "${REDIS_CONTAINER_NAME}" \
    --network "${DOCKER_NETWORK_NAME}" \
    redis

# Wait for MySQL to be fully ready
max_tries=30
tries=0
while [ $tries -lt $max_tries ]; do
    if docker exec "${MYSQL_CONTAINER_NAME}" mysql -h localhost -u user -psecret -e "SELECT 1" laravel_query_builder 2>/dev/null; then
        echo "MySQL is ready!"
        break
    fi
    sleep 4
    tries=$((tries+1))
done

if [ $tries -eq $max_tries ]; then
    echo "MySQL did not become ready in time"
    exit 1
fi

# Run tests in Docker
docker run --rm \
    --name "${TEST_CONTAINER_NAME}" \
    --network "${DOCKER_NETWORK_NAME}" \
    -e DB_HOST="${MYSQL_CONTAINER_NAME}" \
    -e DB_PORT=3306 \
    -e DB_USERNAME=user \
    -e DB_PASSWORD=secret \
    -e REDIS_HOST="${REDIS_CONTAINER_NAME}" \
    -e REDIS_PORT=6379 \
    "${TEST_RUNNER_IMAGE_NAME}" \
    vendor/bin/pest ${FILTER:+--filter "$FILTER"}

# Cleanup containers
docker stop "${MYSQL_CONTAINER_NAME}" "${REDIS_CONTAINER_NAME}" "${TEST_CONTAINER_NAME}"
docker rm "${MYSQL_CONTAINER_NAME}" "${REDIS_CONTAINER_NAME}" "${TEST_CONTAINER_NAME}"
docker network rm "${DOCKER_NETWORK_NAME}"

echo "Tests completed successfully!"