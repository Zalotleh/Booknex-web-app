<?php

namespace BookneticApp\Providers\Core;

use BookneticApp\Backend\Appointments\Helpers\ReminderService;
use BookneticApp\Providers\Helpers\BackgrouondProcess;
use BookneticApp\Providers\Helpers\Helper;

class CronJob
{

	private static $reScheduledList = [];

	/**
	 * @var BackgrouondProcess
	 */
	private static $backgroundProcess;

	public static function init()
	{
		self::$backgroundProcess = new BackgrouondProcess();

        if ( ! Helper::processRuntimeController( 'cron_job', 60 ) )
            return;

		if( defined( 'DOING_CRON' ) )
		{
			self::runTasks();
		}
		else if( !self::isThisProcessBackgroundTask() )
		{
			self::$backgroundProcess->dispatch();
		}
	}

	public static function isThisProcessBackgroundTask()
	{
		$action = Helper::_get('action', '', 'string');

		return $action === self::$backgroundProcess->getAction();
	}

	public static function runTasks()
	{
        do_action('bkntc_cronjob');
        LicenseService::syncLicenseStatus();
		ReminderService::run();
	}

}
