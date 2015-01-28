<?php

/*
Plugin Name: Redis
Description: Redis backend for the WP Object Cache.
Version: 1.0.1
Plugin URI: http://www.breakmedia.com
Author: Angel Espiritu
Install this file to wp-content/object-cache.php
*/


require('plugins/redis-cache-predis/predis/PHP5.2/lib/Predis.php');
define(__REDIS_OBJECT_CACHE_INSTALLED, True);

function wp_cache_add($key, $data, $group = 'default', $expire = 0)
{
    global $wp_object_cache;
    return $wp_object_cache->set($key, $data, $group, $expire);
}

function wp_cache_incr($key, $n = 1, $flag = '')
{
    global $wp_object_cache;
    return $wp_object_cache->incr($key, $n, $flag);
}

function wp_cache_decr($key, $n = 1, $flag = '')
{
    global $wp_object_cache;
    return $wp_object_cache->decr($key, $n, $flag);
}

function wp_cache_close()
{
    return true;
}

function wp_cache_delete($id, $group)
{
    global $wp_object_cache;
    return $wp_object_cache->delete($id, $group);
}

function wp_cache_flush()
{
    global $wp_object_cache;
    return $wp_object_cache->flushdb();
}

function wp_cache_get($key, $group = 'default', $expire = 0)
{
    global $wp_object_cache;
    return $wp_object_cache->get_preempt($key, $group, $expire);
}

function wp_cache_init()
{
    global $wp_object_cache;
    $wp_object_cache = new WP_Object_Cache();
}

function wp_cache_replace($key, $data, $group = 'default', $expire = 0)
{
    global $wp_object_cache;
    return $wp_object_cache->replace($key, $data, $group, $expire);
}

function wp_cache_set($key, $data, $flag = '', $expire = 0)
{
    global $wp_object_cache;
    
    if (defined('WP_INSTALLING') == false)
        return $wp_object_cache->set($key, $data, $flag, $expire);
    else
        return $wp_object_cache->delete($key, $flag);
}

function wp_cache_add_global_groups($groups)
{
    global $wp_object_cache;
    
    $wp_object_cache->add_global_groups($groups);
}

function wp_cache_add_non_persistent_groups($groups)
{
    global $wp_object_cache;
    
    $wp_object_cache->add_non_persistent_groups($groups);
}


class WP_Object_Cache
{
    var $global_groups = array('users', 'userlogins', 'usermeta', 'site-options', 'site-lookup', 'blog-lookup', 'blog-details', 'rss');
    var $no_redis_groups = array('comment', 'counts');
    var $autoload_groups = array('options');
    var $tmp_cache = array();
    var $debug = false;
    var $blog_id;
    var $redis;
    var $redis_servers = array('host' => '127.0.0.1', 'port' => 6379, 'database' => 1);
    
    function WP_Object_Cache()
    {
        global $blog_id;
        $this->blog_id = $blog_id;
        $this->redis   = new redis_wp_cache();
        $this->redis->connect($this->redis_servers);
    }
    
    
    function add($id, $data, $group = 'default', $ttl = 0)
    {
        $key = $this->key($id, $group);
        
        if (in_array($group, $this->no_redis_groups)) {
            $this->tmp_cache[$key] = $data;
            return true;
        } elseif (isset($this->tmp_cache[$key]) && $this->tmp_cache[$key] !== false) {
            return false;
        }
        
        //$mc =& $this->get_mc($group);
        //$ttl = ($ttl == 0) ? $this->default_expiration : $ttl;
        //$result = $mc->add($key, $data, false, $ttl);
        
        if ($this->is_seriable($data)) {
            $this->redis->set($key, $this->perform_serialization($data), $ttl);
        } else {
            $this->redis->set($key, $data, $ttl);
        }
        
        if (false !== $result) {
            $this->tmp_cache[$key] = $data;
            $result                = true;
        }
        
        return $result;
    }
    
    function add_global_groups($groups)
    {
        if (!is_array($groups))
            $groups = (array) $groups;
        
        $this->global_groups = array_merge($this->global_groups, $groups);
        $this->global_groups = array_unique($this->global_groups);
    }
    
    function add_non_persistent_groups($groups)
    {
        if (!is_array($groups))
            $groups = (array) $groups;
        
        $this->no_redis_groups = array_merge($this->no_redis_groups, $groups);
        $this->no_redis_groups = array_unique($this->no_redis_groups);
    }
    
    function incr($id, $n, $group)
    {
        $key = $this->key($id, $group);
        return $this->redis->incrby($key, $n);
    }
    
    function decr($id, $n, $group)
    {
        $key = $this->key($id, $group);
        return $this->redis->decrby($key, $n);
    }
    
    function delete($id, $group = 'default')
    {
        $key = $this->key($id, $group);
        
        if (in_array($group, $this->no_redis_groups)) {
            unset($this->tmp_cache[$key]);
            return true;
        }
        $result = $this->redis->del($key);
        
        if (false !== $result)
            unset($this->tmp_cache[$key]);
        return $result;
    }
    
    function get($key, $group = 'default', $expire = 0)
    {
        $key = $this->key($key, $group);
        if (isset($this->tmp_cache[$key])) {
            return $this->tmp_cache[$key];
        }
        $get = $this->redis->get($key);
        //error_log($get,3,"/var/tmp/my-errors.log");
        $tmp = unserialize($get);
        //  if ( $tmp )
        return $tmp;
        //return $get;
    }
    function get_preempt($key, $group = 'default', $expire = 0)
    {
        $when_refresh_minutes = 2; //2 minutes
        $key                  = $this->key($key, $group);
        $ttl                  = $this->redis->ttl($key);
        $ttlrefreshtime       = $when_refresh_minutes * 60 + $this->get_last_digit_ip() * 60;
        if ($ttl < $ttlrefreshtime) {
            $this->redis->expire($key, $expire);
            if (isset($this->tmp_cache[$key])) {
                $tmp = $this->tmp_cache[$key];
            } else {
                $get = $this->redis->get($key);
                $tmp = unserialize($get);
            }
            //$this->set($key, $tmp, $group, $expire);
            return $tmp;
        } else {
            $get = $this->redis->get($key);
            $tmp = unserialize($get);
            return $tmp;
        }
    }
    function get_last_digit_ip()
    {
        $address = $_SERVER["SERVER_ADDR"];
        return intval(substr($address, -1));
    }
    function key($key, $group)
    {
        if (empty($group))
            $group = 'default';
        if (false !== array_search($group, $this->global_groups))
            $prefix = '';
        else
            $prefix = $this->blog_id . ':';
        return preg_replace('/\s+/', '', "$prefix$group:$key");
    }
    
    function replace($id, $data, $group = 'default', $expire = 0)
    {
        $key    = $this->key($id, $group);
        $result = $this->set($key, $data, false, $expire);
        if (false !== $result)
            $this->tmp_cache[$key] = $data;
        return $result;
    }
    
    function set($key, $data, $group = 'default', $ttl = 0)
    {
        $key = $this->key($key, $group);
        if (isset($this->tmp_cache[$key]))
            return false;
        if (in_array($group, $this->no_redis_groups))
            return true;
        if ($this->is_seriable($data)) {
            $this->redis->set($key, $this->perform_serialization($data), $ttl);
        } else {
            $this->redis->set($key, $data, $ttl);
        }
        $this->tmp_cache[$key] = $data;
        return true;
    }
    
    function is_seriable($var)
    {
        if (is_array($var))
            return true;
        else if (is_object($var))
            return true;
        else
            return false;
    }
    function perform_serialization($var)
    {
        return serialize($var);
    }
    
    function _debug($msg)
    {
        if ($this->_debug)
            echo "'{$msg}'\n<br/>";
    }
    
    function _log($msg)
    {
        $fp = @fopen('log.txt', 'a+');
        if (!$fp) {
            //echo "could not write to log";
            return;
        } else {
            fwrite($fp, $msg . "\n");
            fclose($fp);
        }
    }
}


class redis_wp_cache
{
    var $redis;
    
    function connect($server)
    {
        try {
            $this->redis = @new Predis_Client($server);
        }
        catch (Predis_CommunicationException $e) {
        }
    }
    
    function close()
    {
        //error_log("disconnected \n",3,"/var/tmp/my-errors.log");    
        $this->redis->disconnect();
    }
    
    function get($key)
    {
        //error_log("get:" . $this->redis->get($key) . "\n" , 3, "/var/tmp/my-errors.log");
        return $this->redis->get($key);
    }
    
    function incr($key, $n)
    {
        return $this->redis->incrby($key, $n);
    }
    
    function decr($key, $n)
    {
        return $this->redis->decrby($key, $n);
    }
    
    function del($key)
    {
        return $this->redis->del($key);
    }
    function set($key, $data, $ttl = 0)
    {
        if ($ttl == 0)
            $this->redis->set($key, $data);
        else
            $this->redis->setex($key, $ttl, $data);
        
    }
    
    function flushdb()
    {
        $this->redis->flushdb();
    }
    function ttl($key)
    {
        return $this->redis->ttl($key);
    }
    function expire($key, $ttl)
    {
        $this->redis->expire($key, $ttl);
    }
}
?>