This plugin adds preemptive caching to wordpress sites via redis.

Copy object-cache.php to /wp-content/
copy redis-cache-predis to /wp-content/plugins/ folder

modify line 103 in object-cache.php:

    var $redis_servers = array('host' => '127.0.0.1', 'port' => 6379, 'database' => 1);


Change host value (127.0.0.1) to the redis server being used, change the database value (1) to another value to prevent conflicts.

