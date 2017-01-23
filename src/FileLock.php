<?php

namespace Finwo\FileLock;

class FileLock
{
    /**
     * @var string
     */
    protected $filename = null;

    // 10 ms
    const LOCK_WAIT = 10000;

    /**
     * FileLock constructor.
     *
     * @param $filename
     */
    public function __construct( $filename )
    {
        $this->filename = $filename;
    }

    /**
     * @param int $hash
     *
     * @return resource
     */
    protected static function getResource( $hash )
    {
        static $resource = null;
        if(is_null($resource)) {
            $resource = sem_get($hash);
        }
        return $resource;
    }

    /**
     * @param string $filename
     *
     * @return bool
     */
    public static function _acquire( $filename )
    {
        if (function_exists('sem_get')) {
            @sem_acquire(self::getResource(hexdec(md5($filename))));
            return true;
        } else {
            // Generate filename
            $lockfile = $filename.'.lock';

            // Wait until lock expired or gone
            while(file_exists($lockfile)) {
                $lockExpires = intval(@file_get_contents($lockfile));
                if($lockExpires<time()) {
                    unlink($lockfile);
                } else {
                    usleep(self::LOCK_WAIT);
                }
            }

            // Create a lock
            return file_put_contents($lockfile, sprintf("%d",time()+5)) !== false;
        }
    }

    /**
     * @param string $filename
     *
     * @return bool
     */
    public static function _release( $filename )
    {
        if(function_exists('sem_get')) {
            @sem_release(self::getResource(hexdec(md5($filename))));
            return true;
        } else {
            return @unlink($filename.'.lock');
        }
    }

    /**
     * @return bool
     */
    public function acquire() {
        return self::_acquire($this->filename);
    }

    /**
     * @return bool
     */
    public function release() {
        return self::_release($this->filename);
    }


}
