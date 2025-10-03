<?php

namespace BookneticApp\Backend\Dashboard;


use BookneticApp\Models\Appointment;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Helpers\Helper;

class Controller extends \BookneticApp\Providers\Core\Controller {

	public function index() {
		Capabilities::must( 'dashboard' );

		$totalAccordingToStatus = Appointment::where( 'id', '>', 0 )
		                                     ->select( [ Appointment::getCountFieldAs( 'status', 'count' ), 'status' ] )
		                                     ->groupBy( 'status' )
		                                     ->fetchAll();

		$totalAccordingToStatus = Helper::assocByKey( $totalAccordingToStatus, 'status' );

		$this->view( 'index', compact( 'totalAccordingToStatus' ) );
	}

}
