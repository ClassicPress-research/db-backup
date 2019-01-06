<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Engine\Core\Helper;


use DateTimeZone;

class OutFileSetupProvider
{
	/**
	 * @return array
	 */
	public static function provider__construct()
	{
		$pointInTime = '2018-01-01 00:00:00 GMT';
		$barePoint   = '2018-01-01 00:00:00';
		$tzUTC       = new DateTimeZone('UTC');
		$tzIndy      = new DateTimeZone('America/Indiana/Indianapolis');
		$dtUTC       = new \DateTime($barePoint, $tzUTC);
		$dtIndy      = new \DateTime($barePoint, $tzIndy);

		return [
			// $date, $tz, $expectedDate, $expectedTime
			'Everything is UTC objects'                      => [
				$dtUTC, $tzUTC, $dtUTC, $tzUTC,
			],
			'String time, UTC tz'                            => [
				$pointInTime, $tzUTC, $dtUTC, $tzUTC,
			],
			'Integer time, UTC tz'                            => [
				$dtUTC->getTimestamp(), $tzUTC, $dtUTC, $tzUTC,
			],
			'String time, string tz'                         => [
				$pointInTime, 'UTC', $dtUTC, $tzUTC,
			],
			'Object time, string tz'                         => [
				$dtUTC, 'UTC', $dtUTC, $tzUTC,
			],
			'Object time, different TZ'                      => [
				$dtUTC, $tzIndy, $dtUTC, $tzIndy,
			],
			'String time with TZ different than provided TZ' => [
				$pointInTime, $tzIndy, $dtUTC, $tzIndy,
			],
			'String time without TZ'                         => [
				$barePoint, $tzIndy, $dtIndy, $tzIndy,
			],
		];
	}

	public static function providerGetLocalTimeStamp()
	{
		$barePoint   = '2018-01-01 00:00:00';
		$tzUTC       = new DateTimeZone('UTC');
		$tzIndy      = new DateTimeZone('America/Indiana/Indianapolis');
		$dtUTC       = new \DateTime($barePoint, $tzUTC);
		$altUTC      = new \DateTime('2018-02-03 04:05:06', $tzUTC);
		$dtIndy      = new \DateTime($barePoint, $tzIndy);

		return [
			'UTC time, UTC TZ, null datetime' => [
				$dtUTC, $tzUTC, null, '2018-01-01 00:00:00 UTC'
			],
			'UTC time, UTC TZ,, datetime object' => [
				$dtUTC, $tzUTC, $altUTC, '2018-02-03 04:05:06 UTC'
			],
			'UTC time, UTC TZ,, integer datetime' => [
				$dtUTC, $tzUTC, $altUTC->getTimestamp(), '2018-02-03 04:05:06 UTC'
			],

			'UTC time, Indy TZ, null datetime' => [
				$dtUTC, $tzIndy, null, '2017-12-31 19:00:00 EST'
			],
			'UTC time, Indy TZ,, datetime object' => [
				$dtUTC, $tzIndy, $altUTC, '2018-02-02 23:05:06 EST'
			],
			'UTC time, Indy TZ,, integer datetime' => [
				$dtUTC, $tzIndy, $altUTC->getTimestamp(), '2018-02-02 23:05:06 EST'
			],

			'Indy time, UTC TZ, null datetime' => [
				$dtIndy, $tzUTC, null, '2018-01-01 05:00:00 UTC'
			],
			'Indy time, UTC TZ,, datetime object' => [
				$dtIndy, $tzUTC, $altUTC, '2018-02-03 04:05:06 UTC'
			],
			'Indy time, UTC TZ,, integer datetime' => [
				$dtIndy, $tzUTC, $altUTC->getTimestamp(), '2018-02-03 04:05:06 UTC'
			],

			'Indy time, Indy TZ, null datetime' => [
				$dtIndy, $tzIndy, null, '2018-01-01 00:00:00 EST'
			],
			'Indy time, Indy TZ,, datetime object' => [
				$dtIndy, $tzIndy, $altUTC, '2018-02-02 23:05:06 EST'
			],
			'Indy time, Indy TZ,, integer datetime' => [
				$dtIndy, $tzIndy, $altUTC->getTimestamp(), '2018-02-02 23:05:06 EST'
			],
		];
	}
}