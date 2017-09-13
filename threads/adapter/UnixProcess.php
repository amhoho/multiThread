<?php

namespace Thread\Adapter;

require_once 'Abstract.php';

class UnixProcess extends \Thread\Adapter\AdapterAbstract
{
    public function startThread($command, array $options = null)
    {
        //用 创建管道 的 方式启动一个 进程, 并调用 shell
        /**
         * 返回一个和 fopen() 所返回的相同的文件指针，只不过它是单向的（只能用于读或写）并且必须用 pclose() 来关闭。此指针可以用于 fgets()，fgetss() 和 fwrite()。
         */
        $process = popen($command, 'r');
        /**stream_set_blocking ( resource $stream , int $mode ) 为 stream 设置阻塞或者阻塞模。如果 mode 为0，资源流将会被转换为非阻塞模式；如果是1，资源流将会被转换为阻塞模式。 该参数的设置将会影响到像 fgets() 和 fread() 这样的函数从资源流里读取数据。 在非阻塞模式下，调用 fgets() 总是会立即返回；而在阻塞模式下，将会一直等到从资源流里面获取到数据才能返回*/
        stream_set_blocking($process, false);
        return $process;
    }

    public function closeThread($thread)
    {
    	pclose($thread);
    }

    public function prepareThreadCommand($params, $options)
    {
    	$scriptPath = isset($options['scriptPath']) ? $options['scriptPath'] : null;
        $process    = !empty($options['process']) ? $options['process'] : 'php';

        if (!$scriptPath || !file_exists($scriptPath)) {
            throw new \Exception('script path is invalid!');
        }
        $args = str_replace('&', ' ', http_build_query((array) $params));//修正
        return "{$process} {$scriptPath} {$args} &";
    }
}
