<?php

namespace BookneticApp\Backend\Staff;

use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\UI\TabUI;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Models\Holiday;
use BookneticApp\Models\SpecialDay;
use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Models\ServiceStaff;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;
use Exception;

class Ajax extends \BookneticApp\Providers\Core\Controller
{

	public function add_new()
	{
		$cid = Helper::_post('id', '0', 'integer');

		$selectedServices = [];
		if( $cid > 0 )
		{
			Capabilities::must( 'staff_edit' );

			$staffInfo = Staff::get( $cid );
			if( !$staffInfo )
			{
				return $this->response(false, bkntc__('Staff not found!'));
			}

			$getSelectedServices = ServiceStaff::where('staff_id', $cid)->fetchAll();
			foreach ( $getSelectedServices AS $selected_service )
			{
				$selectedServices[] = (string)$selected_service->service_id;
			}
		}
		else
		{
			Capabilities::must( 'staff_add' );
			$allowedLimit = Capabilities::getLimit( 'staff_allowed_max_number' );

			if( $allowedLimit > -1 && Staff::count() >= $allowedLimit )
			{
				$view = Helper::renderView('Base.view.modal.permission_denied', [
					'text' => bkntc__('You can\'t add more than %d Staff. Please upgrade your plan to add more Staff.', [ $allowedLimit ] )
				]);

				return $this->response( true, [ 'html' => $view ] );
			}

			$staffInfo = new Collection();
		}

		if( !( Permission::isAdministrator() || Capabilities::userCan('staff_add') ) && ($cid == 0 || !in_array( $cid, Permission::myStaffId() )) )
		{
			return $this->response(false, bkntc__('You do not have sufficient permissions to perform this action'));
		}

		$timesheet = DB::DB()->get_row(
			DB::DB()->prepare( 'SELECT staff_id, timesheet FROM '.DB::table('timesheet').' WHERE ((service_id IS NULL AND staff_id IS NULL) OR (staff_id=%d)) '.DB::tenantFilter().' ORDER BY staff_id DESC LIMIT 0,1', [ $cid ] ),
			ARRAY_A
		);

		$specialDays = SpecialDay::where('staff_id', $cid)->fetchAll();
		$holidays = Holiday::where('staff_id', $cid)->fetchAll();

		$holidaysArr = [];
		foreach( $holidays AS $holiday )
		{
			$holidaysArr[ Date::dateSQL( $holiday['date'] ) ] = $holiday['id'];
		}

		$locations  = Location::my()->fetchAll();
		$services   = Service::fetchAll();

		$users = DB::DB()->get_results('SELECT * FROM `'.DB::DB()->base_prefix.'users`', ARRAY_A);

        TabUI::get( 'staff_add' )
             ->item( 'details' )
             ->setTitle( bkntc__( 'STAFF DETAILS' ) )
             ->addView( __DIR__ . '/view/tab/details.php', [], 1 )
             ->setPriority( 1 );

        TabUI::get( 'staff_add' )
             ->item( 'timesheet' )
             ->setTitle( bkntc__( 'WEEKLY SCHEDULE' ) )
             ->addView( __DIR__ . '/view/tab/timesheet.php', [], 1 )
             ->setPriority( 2 );

        TabUI::get( 'staff_add' )
             ->item( 'special_days' )
             ->setTitle( bkntc__( 'SPECIAL DAYS' ) )
             ->addView( __DIR__ . '/view/tab/special_days.php', [], 1 )
             ->setPriority( 3 );

        TabUI::get( 'staff_add' )
             ->item( 'holidays' )
             ->setTitle( bkntc__( 'HOLIDAYS' ) )
             ->addView( __DIR__ . '/view/tab/holidays.php', [], 1 )
             ->setPriority( 4 );

        $timeS = empty($timesheet['timesheet']) ? [
            ["day_off" => 0, "start" => "00:00", "end" => "24:00", "breaks" =>[]],
            ["day_off" => 0, "start" => "00:00", "end" => "24:00", "breaks" =>[]],
            ["day_off" => 0, "start" => "00:00", "end" => "24:00", "breaks" =>[]],
            ["day_off" => 0, "start" => "00:00", "end" => "24:00", "breaks" =>[]],
            ["day_off" => 0, "start" => "00:00", "end" => "24:00", "breaks" =>[]],
            ["day_off" => 0, "start" => "00:00", "end" => "24:00", "breaks" =>[]],
            ["day_off" => 0, "start" => "00:00", "end" => "24:00", "breaks" =>[]] ] : json_decode($timesheet['timesheet'], true);

        $data = [
            'users'                     => $users,
            'locations'                 => $locations,
            'services'                  => $services,
            'selected_services'         => $selectedServices,
            'id'                        => $cid,
            'staff'                     => $staffInfo,
            'special_days'              => $specialDays,
            'timesheet'                 => $timeS,
            'has_specific_timesheet'    => ! empty($timesheet['staff_id']) && $timesheet['staff_id'] > 0,
            'holidays'                  => json_encode( $holidaysArr ),
        ];

		return $this->modalView( 'add_new', $data );
	}

    /**
     * @throws CapabilitiesException|Exception
     */
    public function save_staff()
	{
        $staff = new StaffObject();

        $staff->hasCapability();
        $staff->validate();

        $staff->checkIfUserCanCreateNewStaff();
        $staff->fetchOldStaffInfo();
        $staff->fetchLocations();
        $staff->fetchServices();
        $staff->checkAllowedStaffLimit();
        $staff->parseWeeklySchedule();
        $staff->handleStaffLogin();
        $staff->handleProfileImage();

        $staff->applySqlDataFilters();

        $staff->save();
        $staff->saveServiceStaff();
        $staff->saveWeeklySchedule();
        $staff->saveSpecialDays();
        $staff->saveHolidays();
        $staff->saveTranslations();

		return $this->response(true, [
            'is_edit' => $staff->isEdit(),
            'staff_id' => $staff->getId()
        ] );
	}

	public function hide_staff()
	{
		Capabilities::must( 'staff_edit' );

		$staff_id	= Helper::_post('staff_id', '', 'int');

		if( !( $staff_id > 0 ) )
		{
			return $this->response(false);
		}

		$staff = Staff::get( $staff_id );

		if( !$staff )
		{
			return $this->response( false );
		}

		$new_status = $staff['is_active'] == 1 ? 0 : 1;

		Staff::where('id', $staff_id)->update([ 'is_active' => $new_status ]);

		return $this->response( true );
	}

    public function get_available_times_all()
    {
        $search		    = Helper::_post('q', '', 'string');

        $timeslotLength = Helper::getOption('timeslot_length', 5);

        $tEnd = Date::epoch('00:00:00', '+1 days');
        $timeCursor = Date::epoch('00:00:00');
        $data = [];
        while( $timeCursor <= $tEnd )
        {
            $timeId = Date::timeSQL( $timeCursor );
            $timeText = Date::time( $timeCursor );

            if( $timeCursor == $tEnd && $timeId = "00:00" )
            {
                $timeText = "24:00";
                $timeId = "24:00";
            }

            $timeCursor += $timeslotLength * 60;

            // search...
            if( !empty( $search ) && strpos( $timeText, $search ) === false )
            {
                continue;
            }

            $data[] = [
                'id'	=>	$timeId,
                'text'	=>	$timeText
            ];
        }

        return $this->response(true, [ 'results' => $data ]);
    }
}
