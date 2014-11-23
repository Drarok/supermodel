<?php

namespace Zerifas\Supermodel\Transformer;

use DateTime;

abstract class Date
{
    /**
     * Transform a 'yyyy-mm-dd hh:mm:ss' format string to a DateTime.
     *
     * @param string $string Date string.
     *
     * @return DateTime
     */
    public static function fromDateTimeString($string)
    {
        if ($string === null) {
            return null;
        }

        return DateTime::createFromFormat('Y-m-d H:i:s', $string);
    }

    /**
     * Transform a DateTime to a 'yyyy-mm-dd hh:mm:ss' format string.
     *
     * @param DateTime $datetime DateTime object.
     *
     * @return string
     */
    public static function fromDateTime(DateTime $datetime = null)
    {
        if ($datetime === null) {
            return null;
        }

        return $datetime->format('Y-m-d H:i:s');
    }

    /**
     * Transform a 'yyyy-mm-dd' format string to a DateTime.
     *
     * @param string $string Date string.
     *
     * @return DateTime
     */
    public static function fromDateString($string)
    {
        if ($string === null) {
            return null;
        }

        return DateTime::createFromFormat('Y-m-d', $string);
    }

    /**
     * Transform a DateTime to a 'yyyy-mm-dd' format string.
     *
     * @param DateTime $datetime DateTime object.
     *
     * @return string
     */
    public static function fromDate(DateTime $datetime = null)
    {
        if ($datetime === null) {
            return null;
        }

        return $datetime->format('Y-m-d');
    }

    /**
     * Transform a 'hh:mm:ss' format string to a DateTime.
     *
     * @param string $string Date string.
     *
     * @return DateTime
     */
    public static function fromTimeString($string)
    {
        if ($string === null) {
            return null;
        }

        return DateTime::createFromFormat('H:i:s', $string);
    }

    /**
     * Transform a DateTime to a 'yyyy-mm-dd' format string.
     *
     * @param DateTime $datetime DateTime object.
     *
     * @return string
     */
    public static function fromTime(DateTime $datetime = null)
    {
        if ($datetime === null) {
            return null;
        }

        return $datetime->format('H:i:s');
    }

    /**
     * Transform an ISO8601 format string for a DateTime.
     *
     * @param string $string Datetime string.
     *
     * @return DateTime
     */
    public static function fromISO8601DateTimeString($string)
    {
        if ($string === null) {
            return null;
        }

        return DateTime::createFromFormat(DateTime::ISO8601, $string);
    }

    /**
     * Transform a DateTime to an ISO8601 format string.
     *
     * @param DateTime $datetime DateTime object.
     *
     * @return string
     */
    public static function fromISO8601DateTime(DateTime $datetime = null)
    {
        if ($datetime === null) {
            return null;
        }

        return $datetime->format(DateTime::ISO8601);
    }

    /**
     * Convert a timestamp integer to a DateTime instance.
     *
     * @param int $timestamp UNIX timestamp.
     *
     * @return DateTime
     */
    public static function timestampToDateTime($timestamp)
    {
        if ($timestamp === null) {
            return null;
        }

        return DateTime::createFromFormat('U', $timestamp);
    }

    /**
     * Convert a DateTime instance to a unix timestamp.
     *
     * @param DateTime $dateTime DateTime instance.
     *
     * @return int
     */
    public static function dateTimeToTimestamp(DateTime $dateTime = null)
    {
        if ($dateTime === null) {
            return null;
        }

        return $dateTime->getTimestamp();
    }
}
