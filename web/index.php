<?php

define('THOUSAND_SEPARATOR', true);

if (false === extension_loaded('Zend OPcache')) {
    die("Module Zend OPcache is not loaded");
}

$pocsConfigFile = str_replace(
    'phar://',
    '',
    dirname(dirname(__DIR__)). '/pocs.config.php'
);

if (file_exists($pocsConfigFile)) {
    include $pocsConfigFile;

    if (defined('POCS_AUTH_USER') && defined('POCS_AUTH_PW')) {
        $user = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null;
        $pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null;

        if (false === (isset($user, $pass) && [POCS_AUTH_USER, POCS_AUTH_PW] === [$user, $pass])) {
            include dirname(__DIR__) . '/share/templates/401.php';
            exit;
        }
    }
}

class IndexView
{
    private $configuration;
    private $status;
    private $d3Scripts = array();

    public function __construct()
    {
        $this->configuration = opcache_get_configuration();
        $this->status = opcache_get_status();
    }

    public function pageTitle()
    {
        return 'PHP ' . PHP_VERSION . " with OpCache {$this->configuration['version']['version']}";
    }

    public function getStatus()
    {
        foreach ($this->status as $key => $value) {
            if ($key === 'scripts') {
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    if ($v === false) {
                        $value = 'false';
                    } elseif ($v === true) {
                        $value = 'true';
                    }
                    if ($k === 'used_memory' || $k === 'free_memory' || $k === 'wasted_memory') {
                        $value = $this->_size_for_humans(
                            $v
                        );
                    } elseif ($k === 'current_wasted_percentage' || $k === 'opcache_hit_rate') {
                        $value = number_format(
                                $v,
                                2
                            ) . '%';
                    } elseif ($k === 'blacklist_miss_ratio') {
                        $value = number_format($v, 2) . '%';
                    } elseif ($k === 'start_time' || $k === 'last_restart_time') {
                        $value = ($v ? date(DATE_RFC822, $v) : 'never');
                    }

                    if (THOUSAND_SEPARATOR === true && is_int($v)) {
                        $value = number_format($v);
                    }
                    yield ["key" => $k, "value" => $value];
                }
            } else {
                if ($value === false) {
                    $value = 'false';
                } elseif ($value === true) {
                    $value = 'true';
                }
                yield ["key" => $key, "value" => $value];
            }
        }
    }

    public function getSettings()
    {
        foreach ($this->configuration['directives'] as $key => $value) {
            if ($value === false) {
                $value = 'false';
            } elseif ($value === true) {
                $value = 'true';
            }
            if ($key == 'opcache.memory_consumption') {
                $value = $this->_size_for_humans($value);
            }
            yield ["config" => $key, "value" => $value];
        }
    }

    public function getScriptStatusRows()
    {
        foreach ($this->status['scripts'] as $key => $data) {
            $dirs[dirname($key)][basename($key)] = $data;
            $this->_arrayPset($this->d3Scripts, $key, array(
                'name' => basename($key),
                'size' => $data['memory_consumption'],
            ));
        }

        asort($dirs);

        $basename = '';
        while (true) {
            if (count($this->d3Scripts) !=1) break;
            $basename .= DIRECTORY_SEPARATOR . key($this->d3Scripts);
            $this->d3Scripts = reset($this->d3Scripts);
        }

        $this->d3Scripts = $this->_processPartition($this->d3Scripts, $basename);
        $id = 1;

        $rows = array();
        foreach ($dirs as $dir => $files) {
            $count = count($files);
            $filePlural = $count > 1 ? 's' : null;
            $m = 0;
            foreach ($files as $file => $data) {
                $m += $data["memory_consumption"];
            }
            $m = $this->_size_for_humans($m);

            $row = [
                'id' => $id,
                'count' => $count,
                'dir' => $dir,
                'file_plural' => $filePlural,
                'total_memory_consumption' => \Closure::bind(function () use ($files) {
                    return $this->_size_for_humans(
                        array_sum(array_map(function($data) {
                            return $data['memory_consumption'];
                        }, $files))
                    );
                }, $this),
                'files' => \Closure::bind(function () use ($files) {
                    foreach ($files as $file => $data) {
                        $row = [
                            'file' => $file,
                            'hits' => $this->_format_value($data['hits']),
                            'memory_consumption' => $this->_size_for_humans($data['memory_consumption'])
                        ];
                        yield $row;
                    }
                }, $this),
            ];

            ++$id;

            yield $row;
        }
    }

    public function getScriptStatusCount()
    {
        return count($this->status["scripts"]);
    }

    public function getGraphDataSetJson()
    {
        $dataset = array();
        $dataset['memory'] = array(
            $this->status['memory_usage']['used_memory'],
            $this->status['memory_usage']['free_memory'],
            $this->status['memory_usage']['wasted_memory'],
        );

        $dataset['keys'] = array(
            $this->status['opcache_statistics']['num_cached_keys'],
            $this->status['opcache_statistics']['max_cached_keys'] - $this->status['opcache_statistics']['num_cached_keys'],
            0
        );

        $dataset['hits'] = array(
            $this->status['opcache_statistics']['misses'],
            $this->status['opcache_statistics']['hits'],
            0,
        );

        $dataset['restarts'] = array(
            $this->status['opcache_statistics']['oom_restarts'],
            $this->status['opcache_statistics']['manual_restarts'],
            $this->status['opcache_statistics']['hash_restarts'],
        );

        if (THOUSAND_SEPARATOR === true) {
            $dataset['TSEP'] = 1;
        } else {
            $dataset['TSEP'] = 0;
        }

        return json_encode($dataset);
    }

    public function getHumanUsedMemory()
    {
        return $this->_size_for_humans($this->getUsedMemory());
    }

    public function getHumanFreeMemory()
    {
        return $this->_size_for_humans($this->getFreeMemory());
    }

    public function getHumanWastedMemory()
    {
        return $this->_size_for_humans($this->getWastedMemory());
    }

    public function getUsedMemory()
    {
        return $this->status['memory_usage']['used_memory'];
    }

    public function getFreeMemory()
    {
        return $this->status['memory_usage']['free_memory'];
    }

    public function getWastedMemory()
    {
        return $this->status['memory_usage']['wasted_memory'];
    }

    public function getWastedMemoryPercentage()
    {
        return number_format($this->status['memory_usage']['current_wasted_percentage'], 2);
    }

    public function getD3Scripts()
    {
        return $this->d3Scripts;
    }

    private function _processPartition($value, $name = null)
    {
        if (array_key_exists('size', $value)) {
            return $value;
        }

        $array = array('name' => $name,'children' => array());

        foreach ($value as $k => $v) {
            $array['children'][] = $this->_processPartition($v, $k);
        }

        return $array;
    }

    private function _format_value($value)
    {
        if (THOUSAND_SEPARATOR === true) {
            return number_format($value);
        } else {
            return $value;
        }
    }

    private function _size_for_humans($bytes)
    {
        if ($bytes > 1048576) {
            return sprintf('%.2f&nbsp;MB', $bytes / 1048576);
        } else {
            if ($bytes > 1024) {
                return sprintf('%.2f&nbsp;kB', $bytes / 1024);
            } else {
                return sprintf('%d&nbsp;bytes', $bytes);
            }
        }
    }

    // Borrowed from Laravel
    private function _arrayPset(&$array, $key, $value)
    {
        if (is_null($key)) return $array = $value;
        $keys = explode(DIRECTORY_SEPARATOR, ltrim($key, DIRECTORY_SEPARATOR));
        while (count($keys) > 1) {
            $key = array_shift($keys);
            if ( ! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = array();
            }
            $array =& $array[$key];
        }
        $array[array_shift($keys)] = $value;
        return $array;
    }
}

$view = new IndexView();

include dirname(__DIR__) . '/share/templates/index.php';
