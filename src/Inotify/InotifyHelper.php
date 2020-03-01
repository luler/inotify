<?php
/**
 * Created By 1207032539@qq.com
 */

namespace Luler\Inotify;

class InotifyHelper
{
    /**
     * 获取所有文件
     * @param $path
     * @return array
     * @throws \Exception
     */
    private static function getAllFiles($path)
    {
        $path = realpath($path);
        if (!file_exists($path)) {
            throw new \Exception ('路径不存在');
        }
        $res = [];
        if (!is_dir($path)) {
            $res [] = realpath($path);
        } else {
            $handle = opendir($path);
            while (($item = readdir($handle)) !== false) {
                if ($item == '.' || $item == '..') {
                    continue;
                }
                $item = $path . DIRECTORY_SEPARATOR . $item;
                if (!is_dir($item)) {
                    $res [] = realpath($item);
                    continue;
                }
                $res = array_merge($res, self::getAllFiles($item));
            }
            closedir($handle);
            return $res;
        }
    }

    /**
     *
     * @param $path //文件路径
     * @param $closure //回调函数
     * @param null $file_suffixes //监控的文件后缀，多个以逗号隔开，格式："php,txt"
     * @param bool $show_change //是否显示变动的文件
     * @param int $check_interval //检查文件变动的事件间隔
     */
    public static function notify($path, $closure, $file_suffixes = null, $show_change = true, $check_interval = 3)
    {
        $original_files = [];
        while (1) {
            $files = self::getAllFiles($path);
            $res = [];
            foreach ($files as $file) {
                if (is_dir($file)) {
                    continue;
                }

                if ($file_suffixes !== null) {
                    if (!is_array($file_suffixes)) {
                        $file_suffixes = explode(',', $file_suffixes);
                    }
                    if (!in_array(substr($file, strrpos($file, '.') + 1), $file_suffixes)) {
                        continue;
                    }
                }
                $res [$file] = filemtime($file);
            }
            if ($show_change) {
                $is_changed = 0;
                if (!empty ($original_files)) {
                    foreach ($res as $k => $v) {
                        if (!isset ($original_files [$k])) {
                            $is_changed = 1;
                            echo 'Created ' . $k . PHP_EOL;
                        } elseif ($v !== $original_files [$k]) {
                            $is_changed = 1;
                            echo 'Modified ' . $k . PHP_EOL;
                        }
                    }
                    $delete_files = array_diff(array_keys($original_files), array_keys($res));
                    if (!empty ($delete_files)) {
                        array_map(function ($value) use (&$is_changed) {
                            $is_changed = 1;
                            echo 'Deleted ' . $value . PHP_EOL;
                        }, $delete_files);
                    }
                }
                if ($is_changed) {
                    call_user_func_array($closure, []);
                }
                $original_files = $res;
            } else {
                $md5 = md5(serialize($res));
                if (!empty ($key) && $key !== $md5) {
                    call_user_func_array($closure, []);
                }
                $key = $md5;
            }

            sleep($check_interval);
        }
    }
}
