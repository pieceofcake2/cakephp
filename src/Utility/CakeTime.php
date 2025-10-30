<?php

namespace Cake\Utility;

use Cake\Core\Configure;
use Cake\Error\CakeException;
use Cake\I18n\Multibyte;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use function PHP81_BC\strftime;

/**
 * CakeTime utility class file.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Utility
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Time Helper class for easy use of time data.
 *
 * Manipulation of time data.
 *
 * @package       Cake.Utility
 * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html
 */
class CakeTime
{
    /**
     * The format to use when formatting a time using `CakeTime::nice()`
     *
     * The format should use the locale strings as defined in the PHP docs under
     * `strftime` (http://php.net/manual/en/function.strftime.php)
     *
     * @var string
     * @see CakeTime::format()
     */
    public static string $niceFormat = '%a, %b %eS %Y, %H:%M';

    /**
     * The format to use when formatting a time using `CakeTime::timeAgoInWords()`
     * and the difference is more than `CakeTime::$wordEnd`
     *
     * @var string
     * @see CakeTime::timeAgoInWords()
     */
    public static string $wordFormat = 'j/n/y';

    /**
     * The format to use when formatting a time using `CakeTime::niceShort()`
     * and the difference is between 3 and 7 days
     *
     * @var string
     * @see CakeTime::niceShort()
     */
    public static string $niceShortFormat = '%B %d, %H:%M';

    /**
     * The format to use when formatting a time using `CakeTime::timeAgoInWords()`
     * and the difference is less than `CakeTime::$wordEnd`
     *
     * @var array
     * @see CakeTime::timeAgoInWords()
     */
    public static array $wordAccuracy = [
        'year' => 'day',
        'month' => 'day',
        'week' => 'day',
        'day' => 'hour',
        'hour' => 'minute',
        'minute' => 'minute',
        'second' => 'second',
    ];

    /**
     * The end of relative time telling
     *
     * @var string
     * @see CakeTime::timeAgoInWords()
     */
    public static string $wordEnd = '+1 month';

    /**
     * Temporary variable containing the timestamp value, used internally in convertSpecifiers()
     *
     * @var int|null
     */
    protected static ?int $_time = null;

    /**
     * Magic set method for backwards compatibility.
     * Used by TimeHelper to modify static variables in CakeTime
     *
     * @param string $name Variable name
     * @param mixed $value Variable value
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        if ($name == 'niceFormat') {
            static::${$name} = $value;
        }
    }

    /**
     * Magic set method for backwards compatibility.
     * Used by TimeHelper to get static variables in CakeTime
     *
     * @param string $name Variable name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'niceFormat' => static::${$name},
            default => null,
        };
    }

    /**
     * Converts a string representing the format for the function strftime and returns a
     * Windows safe and i18n aware format.
     *
     * @param string $format Format with specifiers for strftime function.
     *    Accepts the special specifier %S which mimics the modifier S for date()
     * @param int|null $time UNIX timestamp
     * @return string Windows safe and date() function compatible format for strftime
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::convertSpecifiers
     */
    public static function convertSpecifiers(string $format, ?int $time = null): string
    {
        if (!$time) {
            $time = time();
        }
        static::$_time = $time;

        return preg_replace_callback('/%(\w+)/', ['CakeTime', '_translateSpecifier'], $format);
    }

    /**
     * Auxiliary function to translate a matched specifier element from a regular expression into
     * a Windows safe and i18n aware specifier
     *
     * @param array $specifier match from regular expression
     * @return string converted element
     */
    protected static function _translateSpecifier(array $specifier): string
    {
        switch ($specifier[1]) {
            case 'a':
                $abday = __dc('cake', 'abday', 5);
                if (is_array($abday)) {
                    return $abday[date('w', static::$_time)];
                }
                break;
            case 'A':
                $day = __dc('cake', 'day', 5);
                if (is_array($day)) {
                    return $day[date('w', static::$_time)];
                }
                break;
            case 'c':
                $format = __dc('cake', 'd_t_fmt', 5);
                if ($format !== 'd_t_fmt') {
                    return static::convertSpecifiers($format, static::$_time);
                }
                break;
            case 'C':
                return sprintf('%02d', date('Y', static::$_time) / 100);
            case 'D':
                return '%m/%d/%y';
            case 'e':
                if (DIRECTORY_SEPARATOR === '/') {
                    return '%e';
                }

                $day = date('j', static::$_time);
                if ($day < 10) {
                    $day = ' ' . $day;
                }

                return $day;
            case 'eS':
                return date('jS', static::$_time);
            case 'b':
            case 'h':
                $months = __dc('cake', 'abmon', 5);
                if (is_array($months)) {
                    return $months[date('n', static::$_time) - 1];
                }

                return '%b';
            case 'B':
                $months = __dc('cake', 'mon', 5);
                if (is_array($months)) {
                    return $months[date('n', static::$_time) - 1];
                }
                break;
            case 'n':
                return "\n";
            case 'p':
            case 'P':
                $default = ['am' => 0, 'pm' => 1];
                $meridiem = $default[date('a', static::$_time)];
                $format = __dc('cake', 'am_pm', 5);
                if (is_array($format)) {
                    $meridiem = $format[$meridiem];

                    return $specifier[1] === 'P' ? strtolower($meridiem) : strtoupper($meridiem);
                }
                break;
            case 'r':
                $complete = __dc('cake', 't_fmt_ampm', 5);
                if ($complete !== 't_fmt_ampm') {
                    return str_replace('%p', static::_translateSpecifier(['%p', 'p']), $complete);
                }
                break;
            case 'R':
                return date('H:i', static::$_time);
            case 't':
                return "\t";
            case 'T':
                return '%H:%M:%S';
            case 'u':
                return ($weekDay = date('w', static::$_time)) ? $weekDay : 7;
            case 'x':
                $format = __dc('cake', 'd_fmt', 5);
                if ($format !== 'd_fmt') {
                    return static::convertSpecifiers($format, static::$_time);
                }
                break;
            case 'X':
                $format = __dc('cake', 't_fmt', 5);
                if ($format !== 't_fmt') {
                    return static::convertSpecifiers($format, static::$_time);
                }
                break;
        }

        return $specifier[0];
    }

    /**
     * Converts given time (in server's time zone) to user's local time, given his/her timezone.
     *
     * @param int $serverTime Server's timestamp.
     * @param DateTimeZone|string|int|null $timezone User's timezone string or DateTimeZone object.
     * @return int User's timezone timestamp.
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::convert
     */
    public static function convert(int $serverTime, DateTimeZone|string|int|null $timezone): int
    {
        static $serverTimezone = null;
        if ($serverTimezone === null || (date_default_timezone_get() !== $serverTimezone->getName())) {
            $serverTimezone = new DateTimeZone(date_default_timezone_get());
        }
        $serverOffset = $serverTimezone->getOffset(new DateTime('@' . $serverTime));
        $gmtTime = $serverTime - $serverOffset;
        if (is_numeric($timezone)) {
            $userOffset = $timezone * 60 * 60;
        } else {
            $timezone = static::timezone($timezone);
            $userOffset = $timezone->getOffset(new DateTime('@' . $gmtTime));
        }
        $userTime = $gmtTime + $userOffset;

        return (int)$userTime;
    }

    /**
     * Returns a timezone object from a string or the user's timezone object
     *
     * @param DateTimeZone|string|null $timezone Timezone string or DateTimeZone object
     *  If null it tries to get timezone from 'Config.timezone' config var
     * @return DateTimeZone Timezone object
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::timezone
     */
    public static function timezone(DateTimeZone|string|null $timezone = null): DateTimeZone
    {
        static $tz = null;

        if (is_object($timezone)) {
            if ($tz === null || $tz->getName() !== $timezone->getName()) {
                $tz = $timezone;
            }
        } else {
            if ($timezone === null) {
                $timezone = Configure::read('Config.timezone') ?? date_default_timezone_get();
            }

            if ($tz === null || $tz->getName() !== $timezone) {
                $tz = new DateTimeZone($timezone);
            }
        }

        return $tz;
    }

    /**
     * Returns server's offset from GMT in seconds.
     *
     * @return int Offset
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::serverOffset
     */
    public static function serverOffset(): int
    {
        return (int)date('Z', time());
    }

    /**
     * Returns a timestamp, given either a UNIX timestamp or a valid strtotime() date string.
     *
     * @param DateTimeInterface|string|int|null $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param DateTimeZone|string|null $timezone Timezone string or DateTimeZone object
     * @return int|false Parsed given timezone timestamp.
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::fromString
     */
    public static function fromString(
        DateTimeInterface|string|int|null $dateString,
        DateTimeZone|string|null $timezone = null,
    ): int|false {
        if (empty($dateString)) {
            return false;
        }

        $containsDummyDate = (is_string($dateString) && str_starts_with($dateString, '0000-00-00'));
        if ($containsDummyDate) {
            return false;
        }

        if (is_int($dateString) || is_numeric($dateString)) {
            $date = (int)$dateString;
        } elseif (
            $dateString instanceof DateTime &&
            $dateString->getTimezone()->getName() != date_default_timezone_get()
        ) {
            $clone = clone $dateString;
            $clone->setTimezone(new DateTimeZone(date_default_timezone_get()));
            $date = (int)$clone->format('U') + $clone->getOffset();
        } elseif ($dateString instanceof DateTime) {
            $date = (int)$dateString->format('U');
        } else {
            $date = strtotime($dateString);
        }

        if ($date === -1 || empty($date)) {
            return false;
        }

        if ($timezone === null) {
            $timezone = Configure::read('Config.timezone');
        }

        if ($timezone !== null) {
            return static::convert($date, $timezone);
        }

        return $date;
    }

    /**
     * Returns a nicely formatted date string for given Datetime string.
     *
     * See http://php.net/manual/en/function.strftime.php for information on formatting
     * using locale strings.
     *
     * @param DateTimeInterface|string|int|null $date UNIX timestamp, strtotime() valid string or DateTime object
     * @param DateTimeZone|string|null $timezone Timezone string or DateTimeZone object
     * @param string|null $format The format to use. If null, `CakeTime::$niceFormat` is used
     * @return string Formatted date string
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::nice
     */
    public static function nice(
        DateTimeInterface|string|int|null $date = null,
        DateTimeZone|string|null $timezone = null,
        ?string $format = null,
    ): string {
        if (!$date) {
            $date = time();
        }
        $timestamp = static::fromString($date, $timezone);
        if (!$format) {
            $format = static::$niceFormat;
        }
        $convertedFormat = static::convertSpecifiers($format, $timestamp);

        return static::_strftimeWithTimezone($convertedFormat, $timestamp, $date, $timezone);
    }

    /**
     * Returns a formatted descriptive date string for given datetime string.
     *
     * If the given date is today, the returned string could be "Today, 16:54".
     * If the given date is tomorrow, the returned string could be "Tomorrow, 16:54".
     * If the given date was yesterday, the returned string could be "Yesterday, 16:54".
     * If the given date is within next or last week, the returned string could be "On Thursday, 16:54".
     * If $dateString's year is the current year, the returned string does not
     * include mention of the year.
     *
     * @param DateTimeInterface|string|int|null $date UNIX timestamp, strtotime() valid string or DateTime object
     * @param DateTimeZone|string|null $timezone Timezone string or DateTimeZone object
     * @return string Described, relative date string
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::niceShort
     */
    public static function niceShort(
        DateTimeInterface|string|int|null $date = null,
        DateTimeZone|string|null $timezone = null,
    ): string {
        if (!$date) {
            $date = time();
        }
        $timestamp = static::fromString($date, $timezone);

        if (static::isToday($date, $timezone)) {
            $formattedDate = static::_strftimeWithTimezone('%H:%M', $timestamp, $date, $timezone);

            return __d('cake', 'Today, %s', $formattedDate);
        }
        if (static::wasYesterday($date, $timezone)) {
            $formattedDate = static::_strftimeWithTimezone('%H:%M', $timestamp, $date, $timezone);

            return __d('cake', 'Yesterday, %s', $formattedDate);
        }
        if (static::isTomorrow($date, $timezone)) {
            $formattedDate = static::_strftimeWithTimezone('%H:%M', $timestamp, $date, $timezone);

            return __d('cake', 'Tomorrow, %s', $formattedDate);
        }

        $d = static::_strftimeWithTimezone('%w', $timestamp, $date, $timezone);
        $day = [
            __d('cake', 'Sunday'),
            __d('cake', 'Monday'),
            __d('cake', 'Tuesday'),
            __d('cake', 'Wednesday'),
            __d('cake', 'Thursday'),
            __d('cake', 'Friday'),
            __d('cake', 'Saturday'),
        ];
        if (static::wasWithinLast('7 days', $date, $timezone)) {
            $formattedDate = static::_strftimeWithTimezone(static::$niceShortFormat, $timestamp, $date, $timezone);

            return sprintf('%s %s', $day[$d], $formattedDate);
        }
        if (static::isWithinNext('7 days', $date, $timezone)) {
            $formattedDate = static::_strftimeWithTimezone(static::$niceShortFormat, $timestamp, $date, $timezone);

            return __d('cake', 'On %s %s', $day[$d], $formattedDate);
        }

        $y = '';
        if (!static::isThisYear($timestamp)) {
            $y = ' %Y';
        }
        $format = static::convertSpecifiers("%b %eS{$y}, %H:%M", $timestamp);

        return static::_strftimeWithTimezone($format, $timestamp, $date, $timezone);
    }

    /**
     * Returns a partial SQL string to search for all records between two dates.
     *
     * @param DateTimeInterface|string|int|null $begin UNIX timestamp, strtotime() valid string or DateTime object
     * @param DateTimeInterface|string|int|null $end UNIX timestamp, strtotime() valid string or DateTime object
     * @param string $fieldName Name of database field to compare with
     * @param DateTimeZone|string|null $timezone Timezone string or DateTimeZone object
     * @return string Partial SQL string.
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::daysAsSql
     */
    public static function daysAsSql(
        DateTimeInterface|string|int|null $begin,
        DateTimeInterface|string|int|null $end,
        string $fieldName,
        DateTimeZone|string|null $timezone = null,
    ): string {
        $begin = static::fromString($begin, $timezone);
        $end = static::fromString($end, $timezone);
        $begin = date('Y-m-d', $begin) . ' 00:00:00';
        $end = date('Y-m-d', $end) . ' 23:59:59';

        return "($fieldName >= '$begin') AND ($fieldName <= '$end')";
    }

    /**
     * Returns a partial SQL string to search for all records between two times
     * occurring on the same day.
     *
     * @param DateTimeInterface|string|int|null $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param string $fieldName Name of database field to compare with
     * @param DateTimeZone|string|null $timezone Timezone string or DateTimeZone object
     * @return string Partial SQL string.
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::dayAsSql
     */
    public static function dayAsSql(
        DateTimeInterface|string|int|null $dateString,
        string $fieldName,
        DateTimeZone|string|null $timezone = null,
    ): string {
        return static::daysAsSql($dateString, $dateString, $fieldName, $timezone);
    }

    /**
     * Returns true if given datetime string is today.
     *
     * @param DateTimeInterface|string|int|null $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param DateTimeZone|string|null $timezone Timezone string or DateTimeZone object
     * @return bool True if datetime string is today
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::isToday
     */
    public static function isToday(
        DateTimeInterface|string|int|null $dateString,
        DateTimeZone|string|null $timezone = null,
    ): bool {
        $timestamp = static::fromString($dateString, $timezone);
        $now = static::fromString('now', $timezone);

        return date('Y-m-d', $timestamp) === date('Y-m-d', $now);
    }

    /**
     * Returns true if given datetime string is in the future.
     *
     * @param DateTimeInterface|string|int|null $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param DateTimeZone|string|null $timezone Timezone string or DateTimeZone object
     * @return bool True if datetime string is in the future
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::isFuture
     */
    public static function isFuture(
        DateTimeInterface|string|int|null $dateString,
        DateTimeZone|string|null $timezone = null,
    ): bool {
        $timestamp = static::fromString($dateString, $timezone);

        return $timestamp > time();
    }

    /**
     * Returns true if given datetime string is in the past.
     *
     * @param DateTimeInterface|string|int|null $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param DateTimeZone|string|null $timezone Timezone string or DateTimeZone object
     * @return bool True if datetime string is in the past
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::isPast
     */
    public static function isPast(
        DateTimeInterface|string|int|null $dateString,
        DateTimeZone|string|null $timezone = null,
    ): bool {
        $timestamp = static::fromString($dateString, $timezone);

        return $timestamp < time();
    }

    /**
     * Returns true if given datetime string is within this week.
     *
     * @param DateTimeInterface|string|int|null $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param DateTimeZone|string|null $timezone Timezone string or DateTimeZone object
     * @return bool True if datetime string is within current week
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::isThisWeek
     */
    public static function isThisWeek(
        DateTimeInterface|string|int|null $dateString,
        DateTimeZone|string|null $timezone = null,
    ): bool {
        $timestamp = static::fromString($dateString, $timezone);
        $now = static::fromString('now', $timezone);

        return date('W o', $timestamp) === date('W o', $now);
    }

    /**
     * Returns true if given datetime string is within this month
     *
     * @param DateTimeInterface|string|int|null $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param DateTimeZone|string|null $timezone Timezone string or DateTimeZone object
     * @return bool True if datetime string is within current month
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::isThisMonth
     */
    public static function isThisMonth(
        DateTimeInterface|string|int|null $dateString,
        DateTimeZone|string|null $timezone = null,
    ): bool {
        $timestamp = static::fromString($dateString, $timezone);
        $now = static::fromString('now', $timezone);

        return date('m Y', $timestamp) === date('m Y', $now);
    }

    /**
     * Returns true if given datetime string is within current year.
     *
     * @param DateTimeInterface|string|int|null $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param DateTimeZone|string|null $timezone Timezone string or DateTimeZone object
     * @return bool True if datetime string is within current year
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::isThisYear
     */
    public static function isThisYear(
        DateTimeInterface|string|int|null $dateString,
        DateTimeZone|string|null $timezone = null,
    ): bool {
        $timestamp = static::fromString($dateString, $timezone);
        $now = static::fromString('now', $timezone);

        return date('Y', $timestamp) === date('Y', $now);
    }

    /**
     * Returns true if given datetime string was yesterday.
     *
     * @param DateTimeInterface|string|int|null $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param DateTimeZone|string|null $timezone Timezone string or DateTimeZone object
     * @return bool True if datetime string was yesterday
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::wasYesterday
     */
    public static function wasYesterday(
        DateTimeInterface|string|int|null $dateString,
        DateTimeZone|string|null $timezone = null,
    ): bool {
        $timestamp = static::fromString($dateString, $timezone);
        $yesterday = static::fromString('yesterday', $timezone);

        return date('Y-m-d', $timestamp) === date('Y-m-d', $yesterday);
    }

    /**
     * Returns true if given datetime string is tomorrow.
     *
     * @param DateTimeInterface|string|int|null $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param DateTimeZone|string|null $timezone Timezone string or DateTimeZone object
     * @return bool True if datetime string was yesterday
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::isTomorrow
     */
    public static function isTomorrow(
        DateTimeInterface|string|int|null $dateString,
        DateTimeZone|string|null $timezone = null,
    ): bool {
        $timestamp = static::fromString($dateString, $timezone);
        $tomorrow = static::fromString('tomorrow', $timezone);

        return date('Y-m-d', $timestamp) === date('Y-m-d', $tomorrow);
    }

    /**
     * Returns the quarter
     *
     * @param DateTimeInterface|string|int|null $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param bool $range if true returns a range in Y-m-d format
     * @return array|int 1, 2, 3, or 4 quarter of year or array if $range true
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::toQuarter
     */
    public static function toQuarter(
        DateTimeInterface|string|int|null $dateString,
        bool $range = false,
    ): array|int {
        $time = static::fromString($dateString);
        $date = (int)ceil(date('m', $time) / 3);
        if ($range === false) {
            return $date;
        }

        $year = date('Y', $time);

        return match ($date) {
            1 => [$year . '-01-01', $year . '-03-31'],
            2 => [$year . '-04-01', $year . '-06-30'],
            3 => [$year . '-07-01', $year . '-09-30'],
            4 => [$year . '-10-01', $year . '-12-31'],
            default => throw new CakeException(__d(
                'cake_dev',
                'Invalid quarter value %s. Expected 1-4.',
                $date,
            )),
        };
    }

    /**
     * Returns a UNIX timestamp from a textual datetime description. Wrapper for PHP function strtotime().
     *
     * @param DateTimeInterface|string|int|null $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param DateTimeZone|string|null $timezone Timezone string or DateTimeZone object
     * @return int Unix timestamp
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::toUnix
     */
    public static function toUnix(
        DateTimeInterface|string|int|null $dateString,
        DateTimeZone|string|null $timezone = null,
    ): int {
        return static::fromString($dateString, $timezone);
    }

    /**
     * Returns a formatted date in server's timezone.
     *
     * If a DateTime object is given or the dateString has a timezone
     * segment, the timezone parameter will be ignored.
     *
     * If no timezone parameter is given and no DateTime object, the passed $dateString will be
     * considered to be in the UTC timezone.
     *
     * @param DateTimeInterface|string|int|null $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param DateTimeZone|string|null $timezone Timezone string or DateTimeZone object
     * @param string $format date format string
     * @return string Formatted date
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::toServer
     */
    public static function toServer(
        DateTimeInterface|string|int|null $dateString,
        DateTimeZone|string|null $timezone = null,
        string $format = 'Y-m-d H:i:s',
    ): string {
        if ($timezone === null) {
            $timezone = new DateTimeZone('UTC');
        } elseif (is_string($timezone)) {
            $timezone = new DateTimeZone($timezone);
        }

        if ($dateString instanceof DateTimeInterface) {
            $date = $dateString;
        } elseif (is_int($dateString) || is_numeric($dateString)) {
            $dateString = (int)$dateString;

            $date = new DateTime('@' . $dateString);
            $date->setTimezone($timezone);
        } else {
            $date = new DateTime($dateString, $timezone);
        }

        if (method_exists($date, 'setTimezone')) {
            $date->setTimezone(new DateTimeZone(date_default_timezone_get()));
        }

        return $date->format($format);
    }

    /**
     * Returns a date formatted for Atom RSS feeds.
     *
     * @param DateTimeInterface|string|int|null $dateString Datetime string or Unix timestamp
     * @param DateTimeZone|string|null $timezone Timezone string or DateTimeZone object
     * @return string Formatted date string
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::toAtom
     */
    public static function toAtom(
        DateTimeInterface|string|int|null $dateString,
        DateTimeZone|string|null $timezone = null,
    ): string {
        return date('Y-m-d\TH:i:s\Z', static::fromString($dateString, $timezone));
    }

    /**
     * Formats date for RSS feeds
     *
     * @param DateTimeInterface|string|int|null $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param DateTimeZone|string|null $timezone Timezone string or DateTimeZone object
     * @return string Formatted date string
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::toRSS
     */
    public static function toRSS(
        DateTimeInterface|string|int|null $dateString,
        DateTimeZone|string|null $timezone = null,
    ): string {
        $date = static::fromString($dateString, $timezone);

        if ($timezone === null) {
            return date('r', $date);
        }

        $userOffset = $timezone;
        if (!is_numeric($timezone)) {
            if (!is_object($timezone)) {
                $timezone = new DateTimeZone($timezone);
            }
            $currentDate = new DateTime('@' . $date);
            $currentDate->setTimezone($timezone);
            $userOffset = $timezone->getOffset($currentDate) / 60 / 60;
        }

        $timezone = '+0000';
        if ($userOffset != 0) {
            $hours = (int)floor(abs($userOffset));
            $minutes = (int)(fmod(abs($userOffset), $hours) * 60);
            $timezone = ($userOffset < 0 ? '-' : '+')
                . str_pad((string)$hours, 2, '0', STR_PAD_LEFT)
                . str_pad((string)$minutes, 2, '0', STR_PAD_LEFT);
        }

        return date('D, d M Y H:i:s', $date) . ' ' . $timezone;
    }

    /**
     * Returns either a relative or a formatted absolute date depending
     * on the difference between the current time and given datetime.
     * $datetime should be in a *strtotime* - parsable format, like MySQL's datetime datatype.
     *
     * ### Options:
     *
     * - `format` => a fall back format if the relative time is longer than the duration specified by end
     * - `accuracy` => Specifies how accurate the date should be described (array)
     *    - year =>   The format if years > 0   (default "day")
     *    - month =>  The format if months > 0  (default "day")
     *    - week =>   The format if weeks > 0   (default "day")
     *    - day =>    The format if weeks > 0   (default "hour")
     *    - hour =>   The format if hours > 0   (default "minute")
     *    - minute => The format if minutes > 0 (default "minute")
     *    - second => The format if seconds > 0 (default "second")
     * - `end` => The end of relative time telling
     * - `relativeString` => The printf compatible string when outputting past relative time
     * - `relativeStringFuture` => The printf compatible string when outputting future relative time
     * - `absoluteString` => The printf compatible string when outputting absolute time
     * - `userOffset` => Users offset from GMT (in hours) *Deprecated* use timezone instead.
     * - `timezone` => The user timezone the timestamp should be formatted in.
     *
     * Relative dates look something like this:
     *
     * - 3 weeks, 4 days ago
     * - 15 seconds ago
     *
     * Default date formatting is d/m/yy e.g: on 18/2/09
     *
     * The returned string includes 'ago' or 'on' and assumes you'll properly add a word
     * like 'Posted ' before the function output.
     *
     * NOTE: If the difference is one week or more, the lowest level of accuracy is day
     *
     * @param DateTimeInterface|string|int|null $dateTime Datetime UNIX timestamp, strtotime() valid string or DateTime object
     * @param array|string $options Default format if timestamp is used in $dateString
     * @return string Relative time string.
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::timeAgoInWords
     */
    public static function timeAgoInWords(
        DateTimeInterface|string|int|null $dateTime,
        array|string $options = [],
    ): string {
        $timezone = null;
        $accuracies = static::$wordAccuracy;
        $format = static::$wordFormat;
        $relativeEnd = static::$wordEnd;
        $relativeStringPast = __d('cake', '%s ago');
        $relativeStringFuture = __d('cake', 'in %s');
        $absoluteString = __d('cake', 'on %s');

        if (is_string($options)) {
            $format = $options;
        } elseif (!empty($options)) {
            if (isset($options['timezone'])) {
                $timezone = $options['timezone'];
            } elseif (isset($options['userOffset'])) {
                $timezone = $options['userOffset'];
            }

            if (isset($options['accuracy'])) {
                if (is_array($options['accuracy'])) {
                    $accuracies = array_merge($accuracies, $options['accuracy']);
                } else {
                    foreach ($accuracies as $key => $level) {
                        $accuracies[$key] = $options['accuracy'];
                    }
                }
            }

            if (isset($options['format'])) {
                $format = $options['format'];
            }
            if (isset($options['end'])) {
                $relativeEnd = $options['end'];
            }
            if (isset($options['relativeString'])) {
                $relativeStringPast = $options['relativeString'];
                unset($options['relativeString']);
            }
            if (isset($options['relativeStringFuture'])) {
                $relativeStringFuture = $options['relativeStringFuture'];
                unset($options['relativeStringFuture']);
            }
            if (isset($options['absoluteString'])) {
                $absoluteString = $options['absoluteString'];
                unset($options['absoluteString']);
            }
            unset($options['end'], $options['format']);
        }

        $now = static::fromString(time(), $timezone);
        $inSeconds = static::fromString($dateTime, $timezone);
        $isFuture = ($inSeconds > $now);

        if ($isFuture) {
            $startTime = $now;
            $endTime = $inSeconds;
        } else {
            $startTime = $inSeconds;
            $endTime = $now;
        }
        $diff = $endTime - $startTime;

        if ($diff === 0) {
            return __d('cake', 'just now', 'just now');
        }

        $isAbsoluteDate = $diff > abs($now - static::fromString($relativeEnd));
        if ($isAbsoluteDate) {
            if (!str_contains($format, '%')) {
                $date = date($format, $inSeconds);
            } else {
                $date = static::_strftime($format, $inSeconds);
            }

            return sprintf($absoluteString, $date);
        }

        $years = $months = $weeks = $hours = $minutes = $seconds = 0;

        // If more than a week, then take into account the length of months
        if ($diff >= 604800) {
            [$future['H'], $future['i'], $future['s'], $future['d'], $future['m'], $future['Y']] = explode('/', date('H/i/s/d/m/Y', $endTime));
            [$past['H'], $past['i'], $past['s'], $past['d'], $past['m'], $past['Y']] = explode('/', date('H/i/s/d/m/Y', $startTime));

            $years = (int)$future['Y'] - (int)$past['Y'];
            $months = (int)$future['m'] + (12 * $years) - (int)$past['m'];

            if ($months >= 12) {
                $years = (int)floor($months / 12);
                $months = $months - ($years * 12);
            }
            if ((int)$future['m'] < (int)$past['m'] && (int)$future['Y'] - (int)$past['Y'] === 1) {
                $years--;
            }

            if ((int)$future['d'] >= (int)$past['d']) {
                $days = (int)$future['d'] - (int)$past['d'];
            } else {
                $daysInPastMonth = date('t', $startTime);
                $daysInFutureMonth = date('t', mktime(0, 0, 0, (int)$future['m'] - 1, 1, (int)$future['Y']));

                if ($isFuture) {
                    $days = $daysInFutureMonth - (int)$past['d'] + (int)$future['d'];
                } else {
                    $days = $daysInPastMonth - (int)$past['d'] + (int)$future['d'];
                }

                if ((int)$future['m'] !== (int)$past['m']) {
                    $months--;
                }
            }

            if (!$months && $years >= 1 && $diff < $years * 31536000) {
                $months = 11;
                $years--;
            }

            if ($months >= 12) {
                $years = $years + 1;
                $months = $months - 12;
            }

            if ($days >= 7) {
                $weeks = (int)floor($days / 7);
                $days = $days - ($weeks * 7);
            }
        } else {
            $days = (int)floor($diff / 86400);
            $diff = $diff - ($days * 86400);

            $hours = (int)floor($diff / 3600);
            $diff = $diff - ($hours * 3600);

            $minutes = (int)floor($diff / 60);
            $diff = $diff - ($minutes * 60);

            $seconds = $diff;
        }

        $accuracy = $accuracies['second'];
        if ($years > 0) {
            $accuracy = $accuracies['year'];
        } elseif (abs($months) > 0) {
            $accuracy = $accuracies['month'];
        } elseif (abs($weeks) > 0) {
            $accuracy = $accuracies['week'];
        } elseif (abs($days) > 0) {
            $accuracy = $accuracies['day'];
        } elseif (abs($hours) > 0) {
            $accuracy = $accuracies['hour'];
        } elseif (abs($minutes) > 0) {
            $accuracy = $accuracies['minute'];
        }

        $accuracyNum = str_replace(
            ['year', 'month', 'week', 'day', 'hour', 'minute', 'second'],
            ['1', '2', '3', '4', '5', '6', '7'],
            $accuracy,
        );

        $relativeDate = [];
        if ($accuracyNum >= 1 && $years > 0) {
            $relativeDate[] = __dn('cake', '%d year', '%d years', $years, $years);
        }
        if ($accuracyNum >= 2 && $months > 0) {
            $relativeDate[] = __dn('cake', '%d month', '%d months', $months, $months);
        }
        if ($accuracyNum >= 3 && $weeks > 0) {
            $relativeDate[] = __dn('cake', '%d week', '%d weeks', $weeks, $weeks);
        }
        if ($accuracyNum >= 4 && $days > 0) {
            $relativeDate[] = __dn('cake', '%d day', '%d days', $days, $days);
        }
        if ($accuracyNum >= 5 && $hours > 0) {
            $relativeDate[] = __dn('cake', '%d hour', '%d hours', $hours, $hours);
        }
        if ($accuracyNum >= 6 && $minutes > 0) {
            $relativeDate[] = __dn('cake', '%d minute', '%d minutes', $minutes, $minutes);
        }
        if ($accuracyNum >= 7 && $seconds > 0) {
            $relativeDate[] = __dn('cake', '%d second', '%d seconds', $seconds, $seconds);
        }
        $relativeDate = implode(', ', $relativeDate);

        if ($relativeDate) {
            $relativeString = $isFuture ? $relativeStringFuture : $relativeStringPast;

            return sprintf($relativeString, $relativeDate);
        }

        if ($isFuture) {
            $strings = [
                'second' => __d('cake', 'in about a second'),
                'minute' => __d('cake', 'in about a minute'),
                'hour' => __d('cake', 'in about an hour'),
                'day' => __d('cake', 'in about a day'),
                'week' => __d('cake', 'in about a week'),
                'year' => __d('cake', 'in about a year'),
            ];
        } else {
            $strings = [
                'second' => __d('cake', 'about a second ago'),
                'minute' => __d('cake', 'about a minute ago'),
                'hour' => __d('cake', 'about an hour ago'),
                'day' => __d('cake', 'about a day ago'),
                'week' => __d('cake', 'about a week ago'),
                'year' => __d('cake', 'about a year ago'),
            ];
        }

        return $strings[$accuracy];
    }

    /**
     * Returns true if specified datetime was within the interval specified, else false.
     *
     * @param string|int $timeInterval the numeric value with space then time type.
     *    Example of valid types: 6 hours, 2 days, 1 minute.
     * @param DateTimeInterface|string|int|null $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param DateTimeZone|string|null $timezone Timezone string or DateTimeZone object
     * @return bool
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::wasWithinLast
     */
    public static function wasWithinLast(
        string|int $timeInterval,
        DateTimeInterface|string|int|null $dateString,
        DateTimeZone|string|null $timezone = null,
    ): bool {
        $tmp = str_replace(' ', '', (string)$timeInterval);
        if (is_numeric($tmp)) {
            $timeInterval = $tmp . ' ' . __d('cake', 'days');
        }

        $date = static::fromString($dateString, $timezone);
        $interval = static::fromString('-' . $timeInterval);
        $now = static::fromString('now', $timezone);

        return $date >= $interval && $date <= $now;
    }

    /**
     * Returns true if specified datetime is within the interval specified, else false.
     *
     * @param string|int $timeInterval the numeric value with space then time type.
     *    Example of valid types: 6 hours, 2 days, 1 minute.
     * @param DateTimeInterface|string|int|null $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @param DateTimeZone|string|null $timezone Timezone string or DateTimeZone object
     * @return bool
     */
    public static function isWithinNext(
        string|int $timeInterval,
        DateTimeInterface|string|int|null $dateString,
        DateTimeZone|string|null $timezone = null,
    ): bool {
        $tmp = str_replace(' ', '', $timeInterval);
        if (is_numeric($tmp)) {
            $timeInterval = $tmp . ' ' . __d('cake', 'days');
        }

        $date = static::fromString($dateString, $timezone);
        $interval = static::fromString('+' . $timeInterval);
        $now = static::fromString('now', $timezone);

        return $date <= $interval && $date >= $now;
    }

    /**
     * Returns gmt as a UNIX timestamp.
     *
     * @param DateTimeInterface|string|int|null $dateString UNIX timestamp, strtotime() valid string or DateTime object
     * @return int UNIX timestamp
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::gmt
     */
    public static function gmt(DateTimeInterface|string|int|null $dateString = null): int
    {
        $time = time();
        if ($dateString) {
            $time = static::fromString($dateString);
        }

        return gmmktime(
            (int)date('G', $time),
            (int)date('i', $time),
            (int)date('s', $time),
            (int)date('n', $time),
            (int)date('j', $time),
            (int)date('Y', $time),
        );
    }

    /**
     * Returns a formatted date string, given either a UNIX timestamp or a valid strtotime() date string.
     * This function also accepts a time string and a format string as first and second parameters.
     * In that case this function behaves as a wrapper for TimeHelper::i18nFormat()
     *
     * ## Examples
     *
     * Create localized & formatted time:
     *
     * ```
     *   CakeTime::format('2012-02-15', '%m-%d-%Y'); // returns 02-15-2012
     *   CakeTime::format('2012-02-15 23:01:01', '%c'); // returns preferred date and time based on configured locale
     *   CakeTime::format('0000-00-00', '%d-%m-%Y', 'N/A'); // return N/A becuase an invalid date was passed
     *   CakeTime::format('2012-02-15 23:01:01', '%c', 'N/A', 'America/New_York'); // converts passed date to timezone
     * ```
     *
     * @param DateTimeInterface|string|int|null $date UNIX timestamp, strtotime() valid string or DateTime object (or a date format string)
     * @param DateTimeInterface|string|int|null $format date format string (or UNIX timestamp, strtotime() valid string or DateTime object)
     * @param string|false $default if an invalid date is passed it will output supplied default value. Pass false if you want raw conversion value
     * @param DateTimeZone|string|null $timezone Timezone string or DateTimeZone object
     * @return string Formatted date string
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::format
     * @see CakeTime::i18nFormat()
     */
    public static function format(
        DateTimeInterface|string|int|null $date,
        DateTimeInterface|string|int|null $format = null,
        string|false $default = false,
        DateTimeZone|string|null $timezone = null,
    ): string {
        // Backwards compatible params re-order test
        $time = static::fromString($format, $timezone);

        if ($time === false) {
            return static::i18nFormat($date, $format, $default, $timezone);
        }

        return date($date, $time);
    }

    /**
     * Returns a formatted date string, given either a UNIX timestamp or a valid strtotime() date string.
     * It takes into account the default date format for the current language if a LC_TIME file is used.
     *
     * @param DateTimeInterface|string|int|null $date UNIX timestamp, strtotime() valid string or DateTime object
     * @param string|null $format strftime format string.
     * @param string|false $default if an invalid date is passed it will output supplied default value. Pass false if you want raw conversion value
     * @param DateTimeZone|string|null $timezone Timezone string or DateTimeZone object
     * @return string Formatted and translated date string
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::i18nFormat
     */
    public static function i18nFormat(
        DateTimeInterface|string|int|null $date,
        string|null $format = null,
        string|false $default = false,
        DateTimeZone|string|null $timezone = null,
    ): string {
        $timestamp = static::fromString($date, $timezone);
        if ($timestamp === false && $default !== false) {
            return $default;
        }
        if ($timestamp === false) {
            return '';
        }
        if (empty($format)) {
            $format = '%x';
        }
        $convertedFormat = static::convertSpecifiers($format, $timestamp);

        return static::_strftimeWithTimezone($convertedFormat, $timestamp, $date, $timezone);
    }

    /**
     * Get list of timezone identifiers
     *
     * @param string|int|null $filter A regex to filter identifier
     *  Or one of DateTimeZone class constants (PHP 5.3 and above)
     * @param string|null $country A two-letter ISO 3166-1 compatible country code.
     *  This option is only used when $filter is set to DateTimeZone::PER_COUNTRY (available only in PHP 5.3 and above)
     * @param array{
     *     group?: bool,
     *      abbr?: bool,
     *      before?: string|null,
     *      after?: string|null
     * }|bool $options If true (default value) groups the identifiers list by primary region.
     *  Otherwise, an array containing `group`, `abbr`, `before`, and `after` keys.
     *  Setting `group` and `abbr` to true will group results and append timezone
     *  abbreviation in the display value. Set `before` and `after` to customize
     *  the abbreviation wrapper.
     * @return array List of timezone identifiers
     * @since 2.2
     * @link https://book.cakephp.org/2.0/en/core-libraries/helpers/time.html#TimeHelper::listTimezones
     */
    public static function listTimezones(
        string|int|null $filter = null,
        ?string $country = null,
        array|bool $options = [],
    ): array {
        if (is_bool($options)) {
            $options = [
                'group' => $options,
            ];
        }
        $defaults = [
            'group' => true,
            'abbr' => false,
            'before' => ' - ',
            'after' => null,
        ];
        $options += $defaults;
        $group = $options['group'];

        $regex = null;
        if (is_string($filter)) {
            $regex = $filter;
            $filter = null;
        }
        if ($filter === null) {
            $filter = DateTimeZone::ALL;
        }
        $identifiers = DateTimeZone::listIdentifiers($filter, $country);

        if ($regex) {
            foreach ($identifiers as $key => $tz) {
                if (!preg_match($regex, $tz)) {
                    unset($identifiers[$key]);
                }
            }
        }

        if ($group) {
            $return = [];
            $now = time();
            $before = $options['before'];
            $after = $options['after'];
            foreach ($identifiers as $tz) {
                $abbr = null;
                if ($options['abbr']) {
                    $dateTimeZone = new DateTimeZone($tz);
                    $trans = $dateTimeZone->getTransitions($now, $now);
                    $abbr = isset($trans[0]['abbr']) ?
                        $before . $trans[0]['abbr'] . $after :
                        null;
                }
                $item = explode('/', $tz, 2);
                if (isset($item[1])) {
                    $return[$item[0]][$tz] = $item[1] . $abbr;
                } else {
                    $return[$item[0]] = [$tz => $item[0] . $abbr];
                }
            }

            return $return;
        }

        return array_combine($identifiers, $identifiers);
    }

    /**
     * Multibyte wrapper for strftime.
     *
     * Handles utf8_encoding the result of strftime when necessary.
     *
     * @param string $format Format string.
     * @param DateTimeInterface|string|int|null $timestamp Timestamp to format.
     * @return string formatted string with correct encoding.
     */
    protected static function _strftime(
        string $format,
        DateTimeInterface|string|int|null $timestamp,
    ): string {
        $format = strftime($format, $timestamp);

        $encoding = Configure::read('App.encoding');
        if (!empty($encoding) && $encoding === 'UTF-8') {
            if (function_exists('mb_check_encoding')) {
                $valid = mb_check_encoding($format, $encoding);
            } else {
                $valid = Multibyte::checkMultibyte($format);
            }
            if (!$valid) {
                $format = mb_convert_encoding($format, 'UTF-8', 'ISO-8859-1');
            }
        }

        return $format;
    }

    /**
     * Multibyte wrapper for strftime.
     *
     * Adjusts the timezone when necessary before formatting the time.
     *
     * @param string $format Format string.
     * @param DateTimeInterface|string|int|null $timestamp Timestamp to format.
     * @param DateTimeInterface|string|int|null $date Timestamp, strtotime() valid string or DateTime object.
     * @param DateTimeZone|string|null $timezone Timezone string or DateTimeZone object.
     * @return string Formatted date string with correct encoding.
     */
    protected static function _strftimeWithTimezone(
        string $format,
        DateTimeInterface|string|int|null $timestamp,
        DateTimeInterface|string|int|null $date,
        DateTimeZone|string|null $timezone,
    ): string {
        $serverTimeZone = date_default_timezone_get();
        if (
            !empty($timezone) &&
            $date instanceof DateTimeInterface &&
            $date->getTimezone()->getName() != $serverTimeZone
        ) {
            date_default_timezone_set($timezone);
        }
        $result = static::_strftime($format, $timestamp);
        date_default_timezone_set($serverTimeZone);

        return $result;
    }
}
