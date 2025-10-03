<?php

namespace BookneticApp\Backend\Dashboard;


use BookneticApp\Models\Appointment;
use BookneticApp\Models\AppointmentPrice;
use BookneticApp\Models\Customer;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\NotificationHelper;
use BookneticApp\Providers\Request\Post;

class Ajax extends Controller
{

	public function get_stat()
	{
		Capabilities::must( 'dashboard' );

        $type = Post::string('type', 'today');
        $start = Post::string('start');
        $end = Post::string('end');

		switch( $type )
		{

			case 'today':
				$start = Date::epoch('today');
				$end = Date::epoch( 'today' , '+1 day');
				break;

			case 'yesterday':
				$start = Date::epoch( 'yesterday' );
				$end = Date::epoch('today' );
				break;

			case 'tomorrow':
				$start = Date::epoch('tomorrow' );
				$end = Date::epoch('tomorrow', '+1 day' );
				break;

			case 'this_week':
				$start = Date::epoch('monday this week' );
				$end = Date::epoch('monday next week'  );
				break;

			case 'last_week':
				$start = Date::epoch('monday previous week' );
				$end = Date::epoch('monday this week');
				break;

			case 'this_month':
				$start = Date::epoch( Date::format( 'Y-m-01' ) );
				$end = Date::epoch( Date::format( 'Y-m-t' ) ,'+1 day' );
				break;

			case 'this_year':
				$start = Date::epoch( Date::format( 'Y-01-01' ) );
				$end = Date::epoch( Date::format( 'Y-12-31' ) ,'+1 day' );
				break;

			case 'custom':
				$start = Date::epoch( Date::reformatDateFromCustomFormat( $start ) );
				$end = Date::epoch( Date::reformatDateFromCustomFormat( $end ) ,'+1 day' );
				break;

		}

		$result = Appointment::select([
		    'count(id) as appointments',
            'sum( (SELECT sum(`price`*`negative_or_positive`) FROM `' . DB::table(AppointmentPrice::getTableName()) . '` WHERE `appointment_id`=' . Appointment::getField( 'id' ) . ' ) ) AS `revenue`',
            'sum( `ends_at` - `starts_at` ) AS duration',
        ])
            ->where( Appointment::getField( 'starts_at' ), '>=', $start )
            ->where( Appointment::getField( 'ends_at' ), '<=', $end )
            ->where( Appointment::getField( 'status' ), 'IN', Helper::getBusyAppointmentStatuses() )->fetch();

		$customers = Customer::where( Customer::getField('created_at' ) , '>=' , Date::dateSQL($start))
            ->where( Customer::getField('created_at' ) , '<' ,  Date::dateSQL( $end ) )
            ->count();

        $totalAccordingToStatus = Appointment::select(['count(status) as count' , 'status'] , true )
            ->where( Appointment::getField( 'starts_at' ), '>=', $start )
            ->where( Appointment::getField( 'ends_at' ), '<=', $end )
            ->groupBy(['status'])
            ->fetchAll();

        $totalAccordingToStatus = Helper::assocByKey($totalAccordingToStatus,'status');

		return $this->response(true, [
			'appointments'	    => $result['appointments'],
			'revenue'		    => Helper::price( $result['revenue'] ?: 0 ),
			'duration'		    => Helper::secFormat( (int)$result['duration']),
            'count_by_status'   => $totalAccordingToStatus,
            'customers'         => $customers
		]);
	}

	public function get_graph_data()
    {
        $year = Post::string('year');

        if ( $year === 'last_year' )
        {
            $startDate = date('Y-m-d' , strtotime(date("Y-m-d") . '-1 year'));
            $endDate = date('Y-m-d');
        }
        else
        {
            $startDate = Date::dateSQL( date("$year-01-01") );
            $endDate = date("Y-m-d" , strtotime($startDate . '+1 year -1day'));
        }

        return $this->modalView( 'svg' , compact('startDate' , 'endDate') );
    }

	public function dismiss_notification() {
        $slug = Post::string( 'slug' );

		if ( empty( $slug ) ) {
			return $this->response( true );
		}

		$notifications = NotificationHelper::getAll();

		if ( empty( $notifications ) || empty( $notifications[ $slug ] ) ) {
			return $this->response( true );
		}

		$notifications[ $slug ][ 'visible' ] = false;

		NotificationHelper::save( $notifications );

		return $this->response( true );
	}
}
