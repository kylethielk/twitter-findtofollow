<?php
/**
 * Timer class from: https://github.com/davidzoli/php-timer-class
 */
class timer
{
    static private $output_method = 0;
    static private $timers;
    static private $datas;
    static private $msg = '';

    function __construct()
    {
        timer::add_timer('global');
    }

    static function add_timer($timername)
    {
        $now = microtime(true);
        if (!isset(self::$timers[$timername]))
        {
            self::$timers[$timername]['start'] = $now;
            self::$timers[$timername]['last'] = $now;
        }
    }

    static function add_cp($checkpoint = '', $timername = 'global')
    {
        if (self::$msg != '')
        {
            return;
        }
        if ($timername == 'global')
        {
            timer::add_timer('global');
        }
        if (!isset(self::$timers[$timername]))
        {
            self::$msg = 'No timer like: ' . $timername . '!';
        }
        $now = microtime(true);
        $trace = debug_backtrace();
        if ($checkpoint != '')
        {
            self::$datas[$timername][$checkpoint] ['time'] = $now;
            self::$datas[$timername][$checkpoint] ['elapsed'] = $now - self::$timers[$timername]['last'];
            self::$datas[$timername][$checkpoint] ['duration'] = $now - self::$timers[$timername]['start'];
            self::$datas[$timername][$checkpoint] ['place'] = $trace[0]['file'] . ':' . $trace[0]['line'];
            self::$timers[$timername]['last'] = $now;
        }
    }

    static function showme($timer = '')
    {
        if (self::$output_method == 1)
        {
            echo "\n" . '<!--' . "\n";
            echo 'PHP timer class output' . "\n";
        }
        if ($timer != '')
        {
            echo timer::get_datas($timer);
        }
        else
        {
            foreach (self::$timers as $timer => $data)
            {
                echo timer::get_datas($timer);
            }
        }

        if (self::$output_method == 1)
        {
            echo "\n" . '-->' . "\n";
        }
    }

    static function get_datas($timer)
    {
        $out = '';
        if (count(self::$datas[$timer]))
        {
            if (self::$output_method == 1)
            {
                $nl = "\n";
            }
            if (self::$output_method == 2)
            {
                $nl = '<br/>';
            }

            $out .= $nl . 'Checkpoint infos for timer: ' . $timer . $nl;
            foreach (self::$datas[$timer] as $checkpoint => $cp_datas)
            {
                $out .= $checkpoint . ', Elapsed: ' . round($cp_datas['elapsed'], 6) . ', Total:' . round($cp_datas['duration'], 6) . $nl;
            }
        }
        return $out;
    }

    static function set_output($method)
    {
        self::$output_method = $method;
    }

}

?>