<?php

namespace Thread;

require_once __DIR__ . '/adapter/Interface.php';

class ThreadManager
{
    private
        $_options = array(
            'timeout'            => 60,
            'scriptPath'         => null,
            'process'            => 'php',
            'maxProcess'         => 10,
            'onCompliteCallback' => null,
            'adapter'            => 'UnixProcess',
        ),
        $_adapter          = null,
        $_runningProcesses = array(),
        $_threadQueue      = array();


    /**
     * Создает менеджер тредов.
     * Принимает настройки, список которых можно посмотреть в $this->_options
     *
     * @param array|null $options
     * @return ThreadManager
     */
    public static function factory(array $options = null)
    {
        $instance = new self;

        if ($options) {
            $instance->_options = array_merge($instance->_options, $options);
        }
        return $instance;
    }

    public function setCompliteCallback($callback)
    {
    	$this->_options['onCompliteCallback'] = $callback;
    	return $this;
    }

    public function setAdapter(AdapterInterface $adapter)
    {
    	$this->_adapter = $adapter;
    	return $this;
    }

    /**
     * Добавляет в очередь задание
     *
     * @param $requestParams array массив параметров, которые будут переданы процессу
     * @return ThreadManager
     */
    public function addThread($requestParams = null)
    {
        $this->_threadQueue[] = $this->_createThreadCommand($requestParams);
        return $this;
    }


    /**
     * Запускает выполнение всех тредов.
     * Можно ограничить количество одновременно работающих потоков
     * через опцию maxProcess. По-умолчинию не более 10
     *
     * @return ThreadManager
     */
    public function run()
    {
        //获取参数
        $maxProcess = $this->_getOption('maxProcess');
        $count      = 0;

        foreach ($this->_threadQueue as $i => $thread) {
            if ($count < $maxProcess) {
                $this->_runProcess($thread);
                unset($this->_threadQueue[$i]);
            }
            $count++;
        }

        $this->_startIterations();

        return $this;
    }



    /* PRIVATE */

    private function __construct()
    {
    }

    private function _runProcess($command)
    {
        $this->_runningProcesses[] = $this->_getAdapter()->startThread($command, $this->_options);
        return $this;
    }

    private function _startIterations()
    {
        $_startTime = microtime(true);
        $_timeout   = $this->_getOption('timeout');
        $adapter    = $this->_getAdapter();

        while ($this->_runningProcesses) {

            // 判断是否超时
            if ($_timeout && (microtime(true) - $_startTime) > $_timeout) {
                foreach ($this->_runningProcesses as $i => $thread) {
                    $adapter->closeThread($thread); // 结束进程
                    unset($this->_runningProcesses[$i]);  // 从进程列表删除
                }
            }


            foreach ($this->_runningProcesses as $i => $thread) {

                //得到进程结束，读取返回消息
                $response = $adapter->getThreadResponse($thread);

                if ($response !== false) {

                    $adapter->closeThread($thread); // 结束进程
                    unset($this->_runningProcesses[$i]);  // 从进程列表删除

                    $this->_notifyComplite($response); // 程序完成，调用回调函数


                    /**
                     * Если в очереди еще остались задачи для выполнения
                     * и не превышено общее время выполнения — запускаем еще один процесс из очереди в стек активных
                     */
                    if ($this->_threadQueue && !($_timeout && (microtime(true) - $_startTime) > $_timeout)) {

                        $nextThread = array_shift($this->_threadQueue);
                        $this->_runProcess($nextThread);
                    }
                }
            }

            usleep(10000); // 休息片刻
        }
    }

    private function _notifyComplite($response)
    {
        $callback = $this->_getOption('onCompliteCallback');

        if ($callback && is_callable($callback)) {
            call_user_func($callback, $response);
        }
    }

    private function _createThreadCommand($params = null)
    {
        return $this->_getAdapter()->prepareThreadCommand($params, $this->_options);
    }


    /**
     * @return AdapterInterface
     */
    private function _getAdapter()
    {
        if ($this->_adapter === null) {

            $name = $this->_getOption('adapter');
            $name = preg_replace('/[^\w]/i', '', $name);

            require_once __DIR__ . '/adapter/'. $name .'.php';

            $name = 'Thread\\Adapter\\' . $name;
            $this->_adapter = new $name();
        }
        return $this->_adapter;
    }

    private function _getOption($name)
    {
    	return isset($this->_options[$name]) ? $this->_options[$name] : null;
    }
}
