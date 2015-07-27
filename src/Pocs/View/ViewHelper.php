<?php

namespace Pocs\View;

class ViewHelper
{
    /**
     * @param $bytes
     * @return string
     */
    public function sizeForHumans($bytes)
    {
        if ($bytes > 1048576) {
            return sprintf('%.2f MB', $bytes / 1048576);
        } else {
            if ($bytes > 1024) {
                return sprintf('%.2f kB', $bytes / 1024);
            } else {
                return sprintf('%d bytes', $bytes);
            }
        }
    }

    /**
     * @param $number
     * @return string
     */
    public function formatNumber($number)
    {
        if (class_exists('NumberFormatter')) {
            $a = new \NumberFormatter("en-US", \NumberFormatter::DECIMAL);
            return $a->format($number);
        }

        return $number;
    }
}
