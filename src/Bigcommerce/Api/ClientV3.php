<?php

namespace Bigcommerce\Api;

use \Exception as Exception;
use Firebase\JWT\JWT;

/**
 * Bigcommerce API Client.
 */
class ClientV3
{
    /**
     * Full Store URL to connect to
     *
     * @var string
     */
    static private $store_url;

    /**
     * Username to connect to the store API with
     *
     * @var string
     */
    static private $username;

    /**
     * API key
     *
     * @var string
     */
    static private $api_key;

    /**
     * Connection instance
     *
     * @var Connection
     */
    static private $connection;

    /**
     * Resource class name
     *
     * @var string
     */
    static private $resource;

    /**
     * API path prefix to be added to store URL for requests
     *
     * @var string
     */
    static private $path_prefix = '/api/v3';

    /**
     * Full URL path to the configured store API.
     *
     * @var string
     */
    static public $api_path;
    static private $client_id;
    static private $store_hash;
    static private $auth_token;
    static private $client_secret;
    static private $stores_prefix = '/stores/%s/v3';
    static private $api_url = 'https://api.bigcommerce.com';
    static private $login_url = 'https://login.bigcommerce.com';

    /**
     * Configure the API client with the required settings to access
     * the API for a store.
     *
     * Accepts OAuth and (for now!) Basic Auth credentials
     *
     * @param array $settings
     */
    public static function configure($settings)
    {
        if (isset($settings['client_id'])) {
            self::configureOAuth($settings);
        } else {
            self::configureBasicAuth($settings);
        }
    }

    /**
     * Configure the API client with the required OAuth credentials.
     *
     * Requires a settings array to be passed in with the following keys:
     *
     * - client_id
     * - auth_token
     * - store_hash
     *
     * @param array $settings
     * @throws \Exception
     */
    public static function configureOAuth($settings)
    {
        if (!isset($settings['auth_token'])) {
            throw new Exception("'auth_token' must be provided");
        }

        if (!isset($settings['store_hash'])) {
            throw new Exception("'store_hash' must be provided");
        }

        self::$client_id = $settings['client_id'];
        self::$auth_token = $settings['auth_token'];
        self::$store_hash = $settings['store_hash'];

        self::$client_secret = isset($settings['client_secret']) ? $settings['client_secret'] : null;

        self::$api_path = self::$api_url . sprintf(self::$stores_prefix, self::$store_hash);
        self::$connection = false;
    }

    /**
     * Configure the API client with the required credentials.
     *
     * Requires a settings array to be passed in with the following keys:
     *
     * - store_url
     * - username
     * - api_key
     *
     * @param array $settings
     * @throws \Exception
     */
    public static function configureBasicAuth(array $settings)
    {
        if (!isset($settings['store_url'])) {
            throw new Exception("'store_url' must be provided");
        }

        if (!isset($settings['username'])) {
            throw new Exception("'username' must be provided");
        }

        if (!isset($settings['api_key'])) {
            throw new Exception("'api_key' must be provided");
        }

        self::$username = $settings['username'];
        self::$api_key = $settings['api_key'];
        self::$store_url = rtrim($settings['store_url'], '/');
        self::$api_path = self::$store_url . self::$path_prefix;
        self::$connection = false;
    }

    /**
     * Configure the API client to throw exceptions when HTTP errors occur.
     *
     * Note that network faults will always cause an exception to be thrown.
     *
     * @param bool $option sets the value of this flag
     */
    public static function failOnError($option = true)
    {
        self::connection()->failOnError($option);
    }

    /**
     * Return XML strings from the API instead of building objects.
     */
    public static function useXml()
    {
        self::connection()->useXml();
    }

    /**
     * Return JSON objects from the API instead of XML Strings.
     * This is the default behavior.
     */
    public static function useJson()
    {
        self::connection()->useXml(false);
    }

    /**
     * Switch SSL certificate verification on requests.
     *
     * @param bool $option sets the value of this flag
     */
    public static function verifyPeer($option = false)
    {
        self::connection()->verifyPeer($option);
    }

    /**
     * Connect to the internet through a proxy server.
     *
     * @param string $host host server
     * @param int|bool $port port number to use, or false
     */
    public static function useProxy($host, $port = false)
    {
        self::connection()->useProxy($host, $port);
    }

    /**
     * Get error message returned from the last API request if
     * failOnError is false (default).
     *
     * @return string
     */
    public static function getLastError()
    {
        return self::connection()->getLastError();
    }

    /**
     * Get an instance of the HTTP connection object. Initializes
     * the connection if it is not already active.
     *
     * @return Connection
     */
    private static function connection()
    {
        if (!self::$connection) {
            self::$connection = new Connection();
            if (self::$client_id) {
                self::$connection->authenticateOauth(self::$client_id, self::$auth_token);
            } else {
                self::$connection->authenticateBasic(self::$username, self::$api_key);
            }
        }

        return self::$connection;
    }

    /**
     * Convenience method to return instance of the connection
     *
     * @return Connection
     */
    public static function getConnection()
    {
        return self::connection();
    }

    /**
     * Set the HTTP connection object. DANGER: This can screw up your Client!
     *
     * @param Connection $connection The connection to use
     */
    public static function setConnection(Connection $connection = null)
    {
        self::$connection = $connection;
    }

    /**
     * Get a collection result from the specified endpoint.
     *
     * @param string $path api endpoint
     * @param string $resource resource class to map individual items
     * @return mixed array|string mapped collection or XML string if useXml is true
     */
    public static function getCollection($path, $resource = 'Resource')
    {
        $response = self::connection()->get(self::$api_path . $path);

        return self::mapCollection($resource, $response);
    }

    /**
     * Get a resource entity from the specified endpoint.
     *
     * @param string $path api endpoint
     * @param string $resource resource class to map individual items
     * @return mixed Resource|string resource object or XML string if useXml is true
     */
    public static function getResource($path, $resource = 'Resource')
    {
        $response = self::connection()->get(self::$api_path . $path);

        return self::mapResource($resource, $response);
    }

    /**
     * Get a count value from the specified endpoint.
     *
     * @param string $path api endpoint
     * @return mixed int|string count value or XML string if useXml is true
     */
    public static function getCount($path)
    {
        $response = self::connection()->get(self::$api_path . $path);

        if ($response == false || is_string($response)) {
            return $response;
        }

        return $response->count;
    }

    /**
     * Send a post request to create a resource on the specified collection.
     *
     * @param string $path api endpoint
     * @param mixed $object object or XML string to create
     * @return mixed
     */
    public static function createResource($path, $object)
    {
        if (is_array($object)) {
            $object = (object)$object;
        }

        return self::connection()->post(self::$api_path . $path, $object);
    }

    /**
     * Send a put request to update the specified resource.
     *
     * @param string $path api endpoint
     * @param mixed $object object or XML string to update
     * @return mixed
     */
    public static function updateResource($path, $object)
    {
        if (is_array($object)) {
            $object = (object)$object;
        }

        return self::connection()->put(self::$api_path . $path, $object);
    }

    /**
     * Send a delete request to remove the specified resource.
     *
     * @param string $path api endpoint
     * @return mixed
     */
    public static function deleteResource($path)
    {
        return self::connection()->delete(self::$api_path . $path);
    }

    /**
     * Internal method to wrap items in a collection to resource classes.
     *
     * @param string $resource name of the resource class
     * @param array $object object collection
     * @return array
     */
    private static function mapCollection($resource, $object)
    {
        if ($object == false || is_string($object)) {
            return $object;
        }

        $baseResource = __NAMESPACE__ . '\\' . $resource;
        self::$resource = (class_exists($baseResource)) ? $baseResource : 'Bigcommerce\\Api\\Resources\\' . $resource;

        return array_map(array('self', 'mapCollectionObject'), $object);
    }

    /**
     * Callback for mapping collection objects resource classes.
     *
     * @param \stdClass $object
     * @return Resource
     */
    private static function mapCollectionObject($object)
    {
        $class = self::$resource;

        return new $class($object);
    }

    /**
     * Map a single object to a resource class.
     *
     * @param string $resource name of the resource class
     * @param \stdClass $object
     * @return Resource
     */
    private static function mapResource($resource, $object)
    {
        if ($object == false || is_string($object)) {
            return $object;
        }

        $baseResource = __NAMESPACE__ . '\\' . $resource;
        $class = (class_exists($baseResource)) ? $baseResource : 'Bigcommerce\\Api\\Resources\\' . $resource;
        return new $class($object);
    }

    /**
     * Map object representing a count to an integer value.
     *
     * @param \stdClass $object
     * @return int
     */
    private static function mapCount($object)
    {
        if ($object == false || is_string($object)) {
            return $object;
        }

        return $object->count;
    }

    /**
     * Swaps a temporary access code for a long expiry auth token.
     *
     * @param \stdClass|array $object
     * @return \stdClass
     */
    public static function getAuthToken($object)
    {
        $context = array_merge(array('grant_type' => 'authorization_code'), (array)$object);
        $connection = new Connection();

        return $connection->post(self::$login_url . '/oauth2/token', $context);
    }

    /**
     * @param int $id
     * @param string $redirectUrl
     * @param string $requestIp
     * @return string
     */
    public static function getCustomerLoginToken($id, $redirectUrl = '', $requestIp = '')
    {
        if (empty(self::$client_secret)) {
            throw new Exception('Cannot sign customer login tokens without a client secret');
        }

        $payload = array(
            'iss' => self::$client_id,
            'iat' => time(),
            'jti' => bin2hex(random_bytes(32)),
            'operation' => 'customer_login',
            'store_hash' => self::$store_hash,
            'customer_id' => $id
        );

        if (!empty($redirectUrl)) {
            $payload['redirect_to'] = $redirectUrl;
        }

        if (!empty($requestIp)) {
            $payload['request_ip'] = $requestIp;
        }

        return JWT::encode($payload, self::$client_secret, 'HS256');
    }

    /**
     * Pings the time endpoint to test the connection to a store.
     *
     * @return \DateTime
     */
    public static function getTime()
    {
        $response = self::connection()->get(self::$api_path . '/time');

        if ($response == false || is_string($response)) {
            return $response;
        }

        return new \DateTime("@{$response->time}");
    }


    /**
     * The request logs with usage history statistics.
     */
    public static function getRequestLogs()
    {
        return self::getCollection('/requestlogs', 'RequestLog');
    }

    public static function getStore()
    {
        $response = self::connection()->get(self::$api_path . '/store');
        return $response;
    }

    /**
     * The number of requests remaining at the current time. Based on the
     * last request that was fetched within the current script. If no
     * requests have been made, pings the time endpoint to get the value.
     *
     * @return int
     */
    public static function getRequestsRemaining()
    {
        $limit = self::connection()->getHeader('X-BC-ApiLimit-Remaining');

        if (!$limit) {
            $result = self::getTime();

            if (!$result) {
                return false;
            }

            $limit = self::connection()->getHeader('X-BC-ApiLimit-Remaining');
        }

        return (int)$limit;
    }

    
    /**
     * Returns all scripts.
     *
     * @return mixed Resource|string resource object or XML string if useXml is true
     */
    public static function getScripts()
    {
        $response = self::connection()->get(self::$api_path . '/content/scripts');
        return self::mapCollection('Script', $response->data);
    }
    
    /**
     * Returns data for a specific script.
     *
     * @param int $id
     * @return mixed Resource|string resource object or XML string if useXml is true
     */
    public static function getScript($id)
    {
        return self::getResource('/content/scripts/' . $id);
    }
    
    /**
     * Creates a script.
     *
     * @param mixed $object object or XML string to create
     * @return mixed
     */
    public static function createScript($object)
    {
        return self::createResource('/content/scripts', $object);
    }
    
    /**
     * Updates the given script.
     *
     * @param int $id
     * @param mixed $object object or XML string to create
     * @return mixed
     */
    public static function updateScript($id, $object)
    {
        return self::updateResource('/content/scripts/' . $id, $object);
    }
    
    /**
     * Delete the given script.
     *
     * @param int $id
     * @return mixed
     */
    public static function deleteScript($id)
    {
        return self::deleteResource('/content/scripts/' . $id);
    }


    /**
     * Returns all customer attributes.
     *
     * @return mixed Resource|string resource object or XML string if useXml is true
     */
    public static function getCustomerAttributes()
    {
        $response = self::connection()->get(self::$api_path . '/customers/attributes');
        return self::mapCollection('CustomerAttribute', $response->data);
    }
    
    /**
     * Returns data for a specific customer attribute.
     *
     * @param int $id
     * @return mixed Resource|string resource object or XML string if useXml is true
     */
    public static function getCustomerAttribute($id)
    {
        return self::getResource('/customers/attributes/' . $id);
    }
    
    /**
     * Creates a customer attribute.
     *
     * @param mixed $object object or XML string to create
     * @return mixed
     */
    public static function createCustomerAttribute($object)
    {
        return self::createResource('/customers/attributes', $object);
    }
    
    /**
     * Updates the given customer attribute.
     *
     * @param int $id
     * @param mixed $object object or XML string to create
     * @return mixed
     */
    public static function updateCustomerAttribute($id, $object)
    {
        return self::updateResource('/customers/attributes/' . $id, $object);
    }
    
    /**
     * Delete the given customer attribute.
     *
     * @param int $id
     * @return mixed
     */
    public static function deleteCustomerAttribute($id)
    {
        return self::deleteResource('/customers/attributes/' . $id);
    }



}
