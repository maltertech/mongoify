# Mongoify Composer Package

## Overview

Mongoify is a simplistic PHP package designed to seamlessly integrate between Shopify's webhook system and MongoDB. It
serves as a bridge, facilitating real-time synchronization of Shopify events with a MongoDB database. This package is
particularly useful for developers looking to build integrations on top of Shopify's platform.

## Features

- **Shopify Webhook Verification**: Validates incoming Shopify webhook requests.
- **Data Processing**: Transforms and prepares webhook data for database operations (BSON Dates).
- **MongoDB Integration**: Supports create, update, and delete operations based on webhook events.

## Requirements

To effectively use Mongoify, ensure the following requirements are met:

- **PHP Version**: PHP 7.0 or higher.
- **MongoDB PHP Driver**: The MongoDB PHP Driver must be installed in your PHP environment.
- **MongoDB Server**: Access to a MongoDB server, either locally or hosted remotely.
- **Web Server**: A web server to deploy your PHP application. This server will act as the endpoint for Shopify
  webhooks, allowing Mongoify to receive and process data.
- **Shopify Account with Webhooks**: An active Shopify account with configured webhooks.

## Installation

Install Mongoify using Composer:

```bash
composer maltertech/mongoify
```

## Usage

### Initialization

Create an instance of the Mongoify class:

```php
require __DIR__ . '/vendor/autoload.php';

use Mongoify\Mongoify;

$mongoify = new Mongoify(
    'your-shopify-client-secret',
    new MongoDB\Client('your-mongodb-connection-string'),
    'your-database-name'
);
```

### Methods

Mongoify provides several methods for accessing data:

- `getTopic()`: Returns the Shopify topic header.
- `getCollection()`: Returns the collection name.
- `getAction()`: Returns the Shopify action header.
- `getWebhook()`: Returns the webhook data.

## Error Handling

Mongoify throws `ErrorException` for any processing issues, allowing for robust error handling in your application.

## Support

For support, queries, or contributions, refer to the issue tracker on the repository or contact the package maintainers.
