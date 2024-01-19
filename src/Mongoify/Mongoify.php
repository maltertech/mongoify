<?php

/**
 * Mongoify is a simplistic PHP package designed to seamlessly integrate between Shopify's webhook system and MongoDB.
 */

namespace Mongoify;

use ErrorException;

class Mongoify
{
    /** @var string $shopifyClientSecret Client secret used to validate Shopify webhook requests */
    private $shopifyClientSecret;

    /** @var \MongoDB\Client MongoDB client to access database */
    private $mongoClient;

    /** @var string $database Name of the database to be used. */
    private $database;

    /** @var string $collection Name of the collection within the database. */
    private $collection;

    /** @var string $topic The topic header from the webhook, indicating the type of event. */
    private $topic;

    /** @var string $action The specific action to be taken, extracted from the topic. */
    private $action;

    /** @var array $webhook Webhook data received from Shopify. */
    private $webhook;

    /**
     * Constructor for ShopifySync.
     * Verifies the Shopify webhook, processes incoming data, and determines the action to be taken.
     *
     * @param string $shopifyClientSecret The key for webhook verification.
     * @param \MongoDB\Client $mongoClient The mongodb client used to connect to your database
     * @param string $database The name of the monogodb database where operations will be performed.
     * @throws ErrorException
     */
    public function __construct(string $shopifyClientSecret, \MongoDB\Client $mongoClient, string $database)
    {
        // save instance variables
        $this->shopifyClientSecret = $shopifyClientSecret;
        $this->mongoClient = $mongoClient;
        $this->database = $database;

        // verify webhook
        self::verifyWebhook();

        // get X-Shopify-Topic header
        $this->topic = $_SERVER['HTTP_X_SHOPIFY_TOPIC'];

        // Extracting both the collection and action from the 'X-Shopify-Topic' header in one step
        list($this->collection, $this->action) = explode('/', $_SERVER['HTTP_X_SHOPIFY_TOPIC']);

        // save webhook to associative array
        $webhookArray = json_decode(file_get_contents('php://input'), true);

        // parse dates and save webhook to instance
        $this->webhook = $this->processDatesRecursively($webhookArray);

        // default key for updating documents
        $key = "id";

        // method to call is based on the topic
        if (in_array($this->getAction(), ["create", "update", "updated", "success", "challenged", "failure"])) {
            $this->update($key);
        } else if (in_array($this->getAction(), ["delete", "deleted", "revoke"])) {
            $this->remove($key);
        } else {
            throw new ErrorException("Unknown topic {$_SERVER['HTTP_X_SHOPIFY_TOPIC']}");
        }
    }

    /**
     * @param $CLIENT_SECRET
     * @return void
     * @throws ErrorException
     */
    private function verifyWebhook()
    {
        // get header
        $hmacHeader = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'] ?? null;

        // confirm header is set
        if ($hmacHeader === null) {
            throw new ErrorException("HTTP_X_SHOPIFY_HMAC_SHA256 header not found.");
        }

        $payload = file_get_contents('php://input');
        $calculatedHMAC = base64_encode(hash_hmac('sha256', $payload, $this->shopifyClientSecret, true));
        $verified = hash_equals($hmacHeader, $calculatedHMAC);

        if (!$verified) {
            throw new ErrorException("Unable to verify Shopify webhook.");
        }
    }

    /**
     * Recursively processes dates within the provided array, converting them to a MongoDB\BSON\UTCDateTime format.
     *
     * @param array $array The array containing date strings to be processed.
     * @return array The array with converted date formats.
     */
    private function processDatesRecursively(array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->processDatesRecursively($value);
            } // to covert to a date, it must be a string, starts with 20 and has a T in it
            else if (is_string($value) && str_starts_with($value, "20") && str_contains($value, "T")) {
                // parse date
                $unixTime = strtotime($value);

                // check if the string value is a valid date
                if ($unixTime !== false) {
                    // it's a valid date, convert it to MongoDB\BSON\UTCDateTime
                    $array[$key] = new MongoDB\BSON\UTCDateTime($unixTime * 1000);
                }
            }
        }

        return $array;
    }

    /**
     * Updates a document in the collection based on the webhook data.
     * If the document does not exist, it will be created.
     *
     * @param string $key The key to identify the document for updating.
     */
    private function update(string $key)
    {
        $this->mongoClient->{$this->database}->{$this->collection}->updateOne(
            ["$key" => $this->webhook["$key"]],
            ['$set' => $this->webhook],
            ['upsert' => true]
        );
    }

    /**
     * Removes a document from the collection based on the webhook data.
     *
     * @param string $key The key to identify the document for removal.
     */
    private function remove(string $key)
    {
        $this->mongoClient->{$this->database}->{$this->collection}->deleteOne(
            ["$key" => $this->webhook["$key"]]
        );
    }

    /**
     * Gets the Shopify topic header.
     *
     * @return string The topic header.
     */
    public function getTopic(): string
    {
        return $this->topic;
    }

    /**
     * Gets the name of the collection.
     *
     * @return string The collection name.
     */
    public function getCollection(): string
    {
        return $this->collection;
    }

    /**
     * Gets the Shopify action header.
     *
     * @return string The action header.
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Gets the webhook data.
     *
     * @return array The webhook data.
     */
    public function getWebhook(): array
    {
        return $this->webhook;
    }
}