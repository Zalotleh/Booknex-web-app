<?php

namespace BookneticApp\Backend\Appointments\Helpers;

use BookneticApp\Backend\Customers\Helpers\CustomerService;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\AppointmentPrice;
use BookneticApp\Models\AppointmentExtra;
use BookneticApp\Models\Data;
use BookneticApp\Models\Enums\AppointmentPaymentStatus;
use BookneticApp\Models\Service;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Math;
use Exception;

class AppointmentService
{
	use RecurringAppointmentService;

    /**
     * @throws Exception
     */
    public static function createAppointment()
	{
        $paymentId = md5( uniqid() );

        AppointmentRequests::self()->setPaymentId( $paymentId );

        foreach ( AppointmentRequests::appointments() as $appointmentData )
        {
            self::createSingle( $appointmentData, $paymentId );
        }
	}

    private static function createSingle( AppointmentRequestData $appointmentData, $pmId )
    {
        $recurringId = $appointmentData->isRecurring() ? md5(uniqid()) : null;

        $payableSlotsCount = $appointmentData->getPayableAppointmentsCount();

        foreach ($appointmentData->getAllTimeslots() AS $appointment )
        {
            $paidAmount = $payableSlotsCount > 0 ? $appointmentData->getPayableToday() : 0;
            $paymentMethod = $payableSlotsCount > 0 ? $appointmentData->paymentMethod : 'local';
            $paymentStatus = $paymentMethod == 'local' ? 'not_paid' : 'pending';

            $appointmentInsertData = apply_filters( 'bkntc_appointment_insert_data', [
                'location_id'				=>	$appointmentData->locationId,
                'service_id'				=>	$appointmentData->serviceId,
                'staff_id'					=>	$appointmentData->staffId,
                'customer_id'               =>  $appointmentData->customerId,
                'status'                    =>  $appointmentData->status,
                'starts_at'                 =>  $appointment->getTimestamp(),
                'ends_at'                   =>  $appointment->getTimestamp() + ((int) $appointmentData->serviceInf->duration + (int) $appointmentData->getExtrasDuration()) * 60,
                'busy_from'                 =>  $appointment->getTimestamp() - ((int) $appointmentData->serviceInf->buffer_before) * 60,
                'busy_to'                   =>  $appointment->getTimestamp() + ((int) $appointmentData->serviceInf->duration + (int) $appointmentData->getExtrasDuration() + (int) $appointmentData->serviceInf->buffer_after) * 60,
                'weight'                    =>  $appointmentData->weight,
                'paid_amount'			    =>	$paidAmount,
                'payment_method'		    =>	$paymentMethod,
                'payment_status'		    =>	$paymentStatus,
                'payment_id'                =>  $pmId,
                'recurring_id'              =>  $recurringId,
                'note'                      =>  $appointmentData->note,
                'locale'                    =>  self::getCustomerLocale( $appointmentData->customerId ),
                'client_timezone'           =>  self::getCustomerTimezone( $appointmentData->customerId ),
                'created_at'                =>  (new \DateTime())->getTimestamp()
            ], $appointmentData );

            $payableSlotsCount--;

            Appointment::insert( $appointmentInsertData );

            $appointmentData->createdAt = $appointmentInsertData["created_at"];

            $appointmentId = DB::lastInsertedId();

            foreach ( $appointmentData->getServiceExtras() AS $extra )
            {
                AppointmentExtra::insert([
                    'appointment_id'        =>  $appointmentId,
                    'extra_id'				=>	$extra['id'],
                    'quantity'				=>	$extra['quantity'],
                    'price'					=>	$extra['price'],
                    'duration'				=>	(int)$extra['duration']
                ]);
            }

            foreach ( $appointmentData->getGroupedPrices() AS $priceKey => $priceInf )
            {
                AppointmentPrice::insert([
                    'appointment_id'            =>  $appointmentId,
                    'unique_key'                =>  $priceKey,
                    'price'                     =>  Math::abs( $priceInf->getPrice() ),
                    'negative_or_positive'      =>  $priceInf->getNegativeOrPositive()
                ]);
            }

            $appointmentData->createdAppointments[] = $appointmentId;

			$appointmentData->appointmentId = $appointmentId;

 			/**
			 * @doc bkntc_appointment_created Action triggered when an appointment created
			 */
			 do_action( 'bkntc_appointment_created', $appointmentData );
        }
    }

	public static function editAppointment( AppointmentRequestData $appointmentObj )
	{
        $timeslot = $appointmentObj->getAllTimeslots()[ 0 ];

        $shouldChangePrices = (bool) $appointmentObj->getData( 'change_prices', 1, 'int', [ 1, 0 ] );

        /*doit add_filter()*/
		$appointmentUpdateData = apply_filters( 'bkntc_appointment_update_data', [
			'location_id' => $appointmentObj->locationId,
			'service_id'  => $appointmentObj->serviceId,
			'staff_id'    => $appointmentObj->staffId,
            'customer_id' => $appointmentObj->customerId,
            'status'      => $appointmentObj->status,
            'weight'      => $appointmentObj->weight,
            'starts_at'   => $timeslot->getTimestamp(),
            'ends_at'     => $timeslot->getTimestamp() + ((int) $appointmentObj->serviceInf->duration + (int) $appointmentObj->getExtrasDuration()) * 60,
            'busy_from'   => $timeslot->getTimestamp() - ((int) $appointmentObj->serviceInf->buffer_before) * 60,
            'busy_to'     => $timeslot->getTimestamp() + ((int) $appointmentObj->serviceInf->duration + (int) $appointmentObj->getExtrasDuration() + (int) $appointmentObj->serviceInf->buffer_after) * 60,
            'note'        => $appointmentObj->note,
        ], $appointmentObj );

		Appointment::where( 'id', $appointmentObj->appointmentId )->update( $appointmentUpdateData );

        if ( ! $shouldChangePrices )
            return;

        AppointmentPrice::where( 'appointment_id', $appointmentObj->appointmentId )->delete();
        AppointmentExtra::where( 'appointment_id', $appointmentObj->appointmentId )->delete();

        foreach ( $appointmentObj->getServiceExtras() as $extra )
        {
            AppointmentExtra::insert( [
                'appointment_id' => $appointmentObj->appointmentId,
                'extra_id'		 =>	$extra[ 'id' ],
                'quantity'		 =>	$extra[ 'quantity' ],
                'price'			 =>	$extra[ 'price' ],
                'duration'		 =>	( int ) $extra[ 'duration' ]
            ] );
        }
        foreach ( $appointmentObj->getGroupedPrices() as $priceKey => $priceInf )
        {
            AppointmentPrice::insert( [
                'appointment_id'       =>  $appointmentObj->appointmentId,
                'unique_key'           =>  $priceKey,
                'price'                =>  Math::abs( $priceInf->getPrice() ),
                'negative_or_positive' =>  $priceInf->getNegativeOrPositive()
            ] );
        }

        Appointment::setData( $appointmentObj->appointmentId, 'price_updated', 0 );
    }

	public static function deleteAppointment( $appointmentsIDs )
	{
		$appointmentsIDs = is_array( $appointmentsIDs ) ? $appointmentsIDs : [ $appointmentsIDs ];

		foreach ( $appointmentsIDs as $appointmentId )
		{
            do_action('bkntc_appointment_before_mutation', $appointmentId);
            do_action('bkntc_appointment_after_mutation', null);

		    do_action('bkntc_appointment_deleted', $appointmentId );

            AppointmentExtra::where( 'appointment_id', $appointmentId )->delete();
            AppointmentPrice::where('appointment_id', $appointmentId)->delete();
			Appointment::where('id', $appointmentId)->delete();
		    Data::where('row_id', $appointmentId )->where('table_name', Appointment::getTableName())->delete();
        }
	}

    /**
     * @throws Exception
     */
    public static function reschedule( $appointmentId, $date, $time, $sendNotifications = true, $resetStatus = true, $staffChanged = false )
	{
        $appointmentInfo			= Appointment::get( $appointmentId );
		$customer_id				= $appointmentInfo->customer_id;

        if ( ! $appointmentInfo ) {
			throw new Exception('');
		}

        $serviceInf = apply_filters( 'bkntc_set_service_duration_frontend', Service::get( $appointmentInfo->service_id ), $appointmentId );

        $staff = $staffChanged ?: $appointmentInfo->staff_id;

		$extras_arr = [];
		$appointmentExtras = AppointmentExtra::where('appointment_id', $appointmentId)->fetchAll();

        /**
         * todo:// bir left join-i çox görməyin bura :D
         * */
		foreach ( $appointmentExtras AS $extra )
		{
			$extra_inf = $extra->extra()->fetch();
			$extra_inf['quantity'] = $extra['quantity'];
			$extra_inf['customer'] = $customer_id;

			$extras_arr[] = $extra_inf;
		}

		$date = Date::dateSQL( $date );
		$time = Date::timeSQL( $time );

        $timeslot = new TimeSlotService( $date, $time );

        $timeslot->setStaffId( $staff )
            ->setServiceInf( $serviceInf )
			->setServiceExtras( $extras_arr )
            ->setLocationId( $appointmentInfo->location_id )
			->setExcludeAppointmentId( $appointmentInfo->id )
			->setCalledFromBackEnd( false )
			->setShowExistingTimeSlots( true );

        /**
         * @var $timeslot TimeSlotService
         */
        $timeslot = apply_filters( 'bkntc_selected_time_slot_info', $timeslot );

        /**
         * todo://niyə?
         */
        $timeslot->setCalledFromBackEnd( Permission::isBackend() );

        if ( ! $timeslot->isBookable() )
		{
			throw new Exception( bkntc__('Please select a valid time! ( %s %s is busy! )', [$date, $time]) );
		}

        $appointmentStatus = $resetStatus ? Helper::getDefaultAppointmentStatus() : $appointmentInfo->status;

        $duration = ($serviceInf->duration + ExtrasService::calcExtrasDuration( $extras_arr )) * 60;

        if ( $sendNotifications ) {
            do_action('bkntc_appointment_before_mutation', $appointmentId);
        }

        $updateData = apply_filters( 'bkntc_appointment_reschedule', [
            'status'     =>  $appointmentStatus,
            'staff_id'   =>  $staff,
            'starts_at' => $timeslot->getTimestamp(),
            'ends_at' => $timeslot->getTimestamp() + $duration,
            'busy_from' => $timeslot->getTimestamp() + (int) $serviceInf->buffer_before * 60,
            'busy_to' => $timeslot->getTimestamp() + $duration + (int) $serviceInf->buffer_after * 60,
        ] );

        do_action( 'bkntc_validate_appointment_reschedule', [
            'appointmentInfo' => $appointmentInfo,
            'staffId' => $staff,
            'starts_at' => $timeslot->getTimestamp()
        ] );

        Appointment::where( 'id', $appointmentId )->update($updateData);

        if ( $sendNotifications )
            do_action('bkntc_appointment_after_mutation', $appointmentId);

        return [
            'appointment_status' => $appointmentStatus
        ];
	}

	public static function setStatus( $id, $status ): void
	{
        $appointment = Appointment::get( $id );

        if ( empty($appointment) || $appointment->status == $status )
            return;

        self::set($id, ['status' =>	$status]);
	}

    public static function set($id, $data): void
    {
        do_action('bkntc_appointment_before_mutation', $id);

		Appointment::whereId($id)->update($data);

        do_action('bkntc_appointment_after_mutation', $id);
    }

	/**
	 * Mushterilere odenish etmeleri uchun 10 deqiqe vaxt verilir.
	 * 10 deqiqe erzinde sechdiyi timeslot busy olacaq ki, odenish zamani diger mushteri bu timeslotu seche bilmesin.
	 * Eger 10 deqiqeden chox kechib ve odenish helede olunmayibsa o zaman avtomatik bu appointmente cancel statusu verir.
	 */
	public static function cancelUnpaidAppointments()
	{
        $failedStatus = Helper::getOption('failed_payment_status');
        if (empty($failedStatus))
            return;

		$timeLimit = Helper::getOption( 'max_time_limit_for_payment', '10' );
		$timestamp = Date::epoch( sprintf('-%s minutes', $timeLimit));

        $appointments = Appointment::where('payment_method', '<>', 'local')
            ->where('payment_status', AppointmentPaymentStatus::PENDING)
            ->where('created_at', '<', $timestamp)
            ->where('status', '<>', $failedStatus)
            ->fetchAll();
        $updateData = [
            'status' => $failedStatus,
            'payment_status' => AppointmentPaymentStatus::CANCELED
        ];

        foreach ( $appointments as $appointment )
            self::set( $appointment->id, $updateData );
	}

    private static function getCustomerTimezone( $customerId )
    {
        $requests = AppointmentRequests::self();

        if( ! $requests->calledFromBackend )
            return $requests->currentRequest()->clientTimezone;

        return CustomerService::findCustomerTimezone( $customerId );
    }

    private static function getCustomerLocale( $customerId )
    {
        $requests = AppointmentRequests::self();

        if( ! $requests->calledFromBackend )
            return get_locale();

        return CustomerService::findCustomerLocale( $customerId );
    }

}