
/**
 * This file is part of the Phalcon Framework.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace Phalcon\Storage\Adapter;

use Phalcon\Helper\Arr;
use Phalcon\Storage\Exception;
use Phalcon\Storage\SerializerFactory;
use Phalcon\Storage\Serializer\SerializerInterface;

/**
 * Redis adapter
 */
class Redis extends AbstractAdapter
{
    /**
     * @var array
     */
    protected options = [];

    /**
     * Constructor
     */
    public function __construct(<SerializerFactory> factory = null, array! options = [])
    {
        /**
         * Lets set some defaults and options here
         */
        let options["host"]       = Arr::get(options, "host", "127.0.0.1"),
            options["port"]       = (int) Arr::get(options, "port", 6379),
            options["index"]      = Arr::get(options, "index", 0),
            options["persistent"] = Arr::get(options, "persistent", false),
            options["auth"]       = Arr::get(options, "auth", ""),
            options["socket"]     = Arr::get(options, "socket", ""),
            this->prefix          = "ph-reds-",
            this->options         = options;

        parent::__construct(factory, options);
    }

    /**
     * Flushes/clears the cache
     *
     * @return bool
     * @throws Exception
     */
    public function clear() -> bool
    {
        return this->getAdapter()->flushDB();
    }

    /**
     * Decrements a stored number
     *
     * @param string $key
     * @param int    $value
     *
     * @return bool|int
     * @throws Exception
     */
    public function decrement(string! key, int value = 1) -> int | bool
    {
        return this->getAdapter()->decrBy(this->getPrefixedKey(key), value);
    }

    /**
     * Reads data from the adapter
     *
     * @param string $key
     *
     * @return bool
     * @throws Exception
     */
    public function delete(string! key) -> bool
    {
        return (bool) this->getAdapter()->del(this->getPrefixedKey(key));
    }

    /**
     * Reads data from the adapter
     *
     * @param string $key
     * @param null   $defaultValue
     *
     * @return mixed
     * @throws Exception
     */
    public function get(string! key, var defaultValue = null) -> var
    {
        return this->getUnserializedData(
            this->getAdapter()->get(this->getPrefixedKey(key)),
            defaultValue
        );
    }

    /**
     * Returns the already connected adapter or connects to the Redis
     * server(s)
     *
     * @return mixed|\Redis
     * @throws Exception
     */
    public function getAdapter() -> var
    {
        var auth, connection, host, index, options, port, result,
            persistent, persistentid;

        if null === this->adapter {
            let options    = this->options,
                connection = new \Redis(),
                auth       = options["auth"],
                host       = options["host"],
                port       = options["port"],
                index      = options["index"],
                persistent = options["persistent"];

            if !persistent {
                let result = connection->connect(host, port, this->lifetime);
            } else {
                let persistentid = "persistentid_" . index;
                let result = connection->pconnect(host, port, this->lifetime, persistentid);
            }

            if !result {
                throw new Exception(
                    "Could not connect to the Redisd server [" . host . ":" . port . "]"
                );
            }

            if !empty auth && !connection->auth(auth) {
                throw new Exception("Failed to authenticate with the Redis server");
            }

            if index > 0 && !connection->select(index) {
                throw new Exception("Redis server selected database failed");
            }

            connection->setOption(\Redis::OPT_PREFIX, this->prefix);

            this->setSerializer(connection);
            let this->adapter = connection;
        }

        return this->adapter;
    }

    /**
     * Stores data in the adapter
     *
     * @return array
     * @throws Exception
     */
    public function getKeys() -> array
    {
    	var key, keys;
    	array results;

        let keys    = this->getAdapter()->keys("*"),
            keys    = !keys ? [] : keys,
            results = Arr::filter(
            keys,
            function ($value) {
                return substr($value, 0, strlen($this->prefix)) === $this->prefix;
            }
        );

        return results;
    }

    /**
     * Checks if an element exists in the cache
     *
     * @param string $key
     *
     * @return bool
     * @throws Exception
     */
    public function has(string! key) -> bool
    {
        return (bool) this->getAdapter()->exists(this->getPrefixedKey(key));
    }

    /**
     * Increments a stored number
     *
     * @param string $key
     * @param int    $value
     *
     * @return bool|int
     * @throws Exception
     */
    public function increment(string! key, int value = 1) -> int | bool
    {
        return this->getAdapter()->incrBy(this->getPrefixedKey(key), value);
    }

    /**
     * Stores data in the adapter
     *
     * @param string $key
     * @param mixed  $value
     * @param null   $ttl
     *
     * @return bool
     * @throws Exception
     */
    public function set(string! key, var value, var ttl = null) -> bool
    {
        return this->getAdapter()->set(
            this->getPrefixedKey(key),
            this->getSerializedData(value),
            this->getTtl(ttl)
        );
    }

    /**
     * Checks the serializer. If it is a supported one it is set, otherwise
     * the custom one is set.
     *
     * @param \Redis $connection
     */
    private function setSerializer(<\Redis> connection)
    {
        var serializer;
        array map;

        let map = [
            "none" : \Redis::SERIALIZER_NONE,
            "php"  : \Redis::SERIALIZER_PHP
        ];

        /**
         * In case IGBINARY or MSGPACK are not defined for previous versions
         * of Redis
         */
        if defined("\\Redis::SERIALIZER_IGBINARY") {
            let map["igbinary"] = constant("\\Redis::SERIALIZER_IGBINARY");
        }

        if defined("\\Redis::SERIALIZER_MSGPACK") {
            let map["msgpack"]  = constant("\\Redis::SERIALIZER_MSGPACK");
        }

        let serializer = strtolower(this->defaultSerializer);

        if isset map[serializer] {
            let this->defaultSerializer = "";
            connection->setOption(\Redis::OPT_SERIALIZER, map[serializer]);
        } else {
            this->initSerializer();
        }
    }
}
