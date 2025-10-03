<?php

namespace BookneticApp\Providers\Common;

use BookneticApp\Backend\Appointments\Helpers\AppointmentRequests;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\AppointmentPrice;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Math;

class PaymentGatewayService
{

	/**
	 * @var PaymentGatewayService[]
	 */
	public static $gateways = [];

	protected $slug;
    protected $defaultTitle;
    protected $defaultIcon;
    protected $title;
    protected $settingsView;

    protected $supportsPackage = true;

    protected $_items = [];

	final public static function load()
	{
		$gatewayInstance = new static();
		$gatewayInstance->init();
	}

    public function setDefaultTitle ( $title )
    {
        $this->defaultTitle = $title;

        return $this;
    }

    public function setTitle( $title )
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle()
    {
        $title = Helper::getOption($this->slug . '_label' );

        return ! empty( $title ) ? htmlspecialchars( $title ) : $this->defaultTitle;
    }

    public function setSlug( $slug )
    {
        $this->slug = $slug;

        return $this;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @deprecated
     */
    public function setIcon ( $icon ) {
        return;
    }

    public function setDefaultIcon( $icon )
    {
        $this->defaultIcon = $icon;

        return $this;
    }

    public function setSettingsView( $view )
    {
        $this->settingsView = $view;

        return $this;
    }

    public function getIcon()
    {
        $icon = Helper::getOption($this->slug . '_icon', '');

        return ! empty($icon) ? Helper::uploadedFileURL($icon, 'Settings') : $this->getDefaultIcon();
    }

    public function getDefaultIcon()
    {
        return $this->defaultIcon;
    }

    public function getSettingsView()
	{
		if( empty( $this->settingsView ) )
			return false;

        return $this->settingsView;
	}

    public function getPriority()
    {
        $gateways_order = Helper::getOption('payment_gateways_order', 'local');
        $gateways_order = explode(',', $gateways_order);
        
        if( in_array( $this->slug, $gateways_order ) )
        {
            return array_search( $this->slug, $gateways_order );
        }

        $gateways_order[] = $this->slug;

        Helper::setOption('payment_gateways_order',  implode( ',', $gateways_order ) );

        return $this->getPriority();
    }

    public function showSettings()
	{
		$view = $this->getSettingsView();

		if( !file_exists( $view ))
		{
			echo htmlspecialchars( $view ) . ' - view not exists!';
			return false;
		}

		include( $view );
	}

    public function isEnabled( $appointmentRequests = null )
    {
    	$enabled = Helper::getOption( $this->slug . '_payment_enabled', 'off') === 'on';

        if ( ! empty( $appointmentRequests ) )
        {
            foreach ($appointmentRequests->appointments as $appointmentRequestData)
            {
                $serviceCustomPaymentMethods = $appointmentRequestData->serviceInf->getData( 'custom_payment_methods', '[]' );
                $serviceCustomPaymentMethods = json_decode( $serviceCustomPaymentMethods, true );
                $serviceCustomPaymentMethods = empty( $serviceCustomPaymentMethods ) ? PaymentGatewayService::getEnabledGatewayNames() : $serviceCustomPaymentMethods;

                if ( ! in_array( $this->slug, $serviceCustomPaymentMethods ) )
                {
                    $enabled = false;

                    break;
                }
                else
                {
                    $enabled = true;
                }
            }
        }

    	if( method_exists( $this, 'when' ) )
	    {
	    	return $this->when( $enabled, $appointmentRequests );
	    }

        return $enabled;
    }

    public function init()
    {
        self::$gateways[ $this->slug ] = $this;
    }

    /**
     * Override this method to accept incoming payment requests
     * @param AppointmentRequests $appointmentRequests
     * @return mixed
     */
    public function doPayment( $appointmentRequests )
    {
        return null;
    }

    public function createPaymentLink( $appointments )
    {
        return null;
    }

    public function createPayment( array $items, array $customData )
    {
        return null;
    }

    public static function paymentCompleted( bool $status, array $customData, string $paymentMethod )
    {
        do_action('bkntc_payment_completed', $status, $customData, $paymentMethod);
    }

    public static function find( $slug )
    {
        if( isset( self::$gateways[ $slug ] ) )
        {
            return self::$gateways[ $slug ];
        }

        return false;
    }

    public static function getEnabledGatewayNames()
    {
        $gateways = self::$gateways;
        $names    = [];

        if( count( $gateways ) > 0 )
        {
            foreach( $gateways as $slug => $gateway )
            {
                if( $gateway->isEnabled() )
                {
                    $names[] = $slug;
                }
            }

            if ( empty( $names ) )
            {
            	$names[] = 'local';
            }
        }

        return $names;
    }

    public static function getInstalledGatewayNames()
    {
        $gateways = self::$gateways;
        $names    = [];

        if( count( $gateways ) > 0 )
        {
            foreach( $gateways as $slug => $gateway )
            {
                $names[] = $slug;
            }
        }

        return $names;
    }

    /**
     * @param $getOnlyEnabledPaymentGateways
     * @param $getGatewaysForSettings
     * @return PaymentGatewayService[]
     */
    public static function getGateways ( $getOnlyEnabledPaymentGateways = false, $getGatewaysForSettings = false )
	{
        uasort( self::$gateways , function($g1, $g2)
        {
            return ( $g1->getPriority() == $g2->getPriority() ? 0 : ( $g1->getPriority() > $g2->getPriority() ? 1 : -1 ) );
        });

        $returnList = [];

        foreach ( self::$gateways AS $slug => $gateway )
        {
        	if( $getOnlyEnabledPaymentGateways && ! $gateway->isEnabled() )
		        continue;

        	if( $getGatewaysForSettings && $gateway->getSettingsView() === false )
        		continue;

	        $returnList[ $slug ] = $gateway;
        }

        if ( empty( $returnList ) && ! empty( self::$gateways[ 'local' ] ) )
        {
        	$returnList[ 'local' ] = self::$gateways[ 'local' ];
        }

        return $returnList;
	}


	public static function confirmPayment( $paymentId )
	{
        $successAppointmentStatus   =   Helper::getOption('successful_payment_status');

        $updateData  = [ 'payment_status' => 'paid' ];

        $appointmentsAll = Appointment::where('payment_id',$paymentId)->fetchAll();
        foreach ($appointmentsAll as $appointment)
        {
            if($appointment->payment_status  == $updateData['payment_status'] )
                return;
        }

        if (!empty($successAppointmentStatus))
        {
            $updateData['status'] = $successAppointmentStatus;
        }

        $recIdList = [];
        $appointments = [];

        foreach ( $appointmentsAll as $appointment )
        {
            if( $appointment->recurring_id == null || ! in_array( $appointment->recurring_id, $recIdList ) )
            {
                $appointments[] = $appointment;
                $recIdList[] = $appointment->recurring_id;
            }

            do_action( 'bkntc_appointment_before_mutation', $appointment->id );

            Appointment::where( 'id', $appointment->id )->update( $updateData );

            do_action( 'bkntc_appointment_after_mutation', $appointment->id );
        }

        foreach ($appointments as $appointment)
        {
            do_action( 'bkntc_payment_confirmed', $appointment->id );
        }
	}

    public static function confirmPaymentLink( $appointmentId , $amountTotal , $gateway )
    {
        $updateData  = [
            'payment_status' => 'paid',
            'payment_method' => $gateway
        ];

        $allowedOldStatus = [ 'pending' ,'paid','not_paid' ];

        $appointment = Appointment::get( $appointmentId );

        $appointmentPrice = AppointmentPrice::where('appointment_id',  $appointmentId)
                                            ->select('sum(price * negative_or_positive) as total_amount', true)
                                            ->fetch();

        if( $appointmentPrice->total_amount == $appointment->paid_amount )
            return;

        $successAppointmentStatus = Helper::getOption('successful_payment_status');

        if ( ! empty( $successAppointmentStatus ) )
        {
            $updateData['status'] = $successAppointmentStatus;
        }

        do_action( 'bkntc_appointment_before_mutation', $appointment->id );

        Appointment::where('id' , $appointmentId )
            ->where( 'payment_status', $allowedOldStatus )
            ->update( $updateData );

        Appointment::where('id' , $appointment->id)->update([
            'paid_amount' => Math::add( $appointment->paid_amount , $amountTotal )
        ]);

        do_action( 'bkntc_appointment_after_mutation', $appointment->id );

        do_action( 'bkntc_payment_confirmed', $appointment->id, 'payment_link' );
    }

	public static function cancelPayment( $paymentId )
	{

        $updateData = [
            'payment_status'    =>  'canceled'
        ];

        $failedStatus = Helper::getOption('failed_payment_status');
        if (!empty($failedStatus))
        {
            $updateData['status'] = $failedStatus;
        }

        Appointment::where('payment_id', $paymentId )
                           ->where('payment_status' , 'pending')
		                   ->update($updateData);

		/**
		 * @doc bkntc_payment_confirmed Trigger events when payment canceled
		 */
		do_action( 'bkntc_payment_canceled', Appointment::where('payment_id',$paymentId)->fetch()->id );
	}

    public function isSupportsPackage(): bool
    {
        return $this->supportsPackage;
    }

    /**
     * We need to reset items before start to add new items. Otherwise in some cases,
     * for example when we call createPaymentLink inside loop it reserves items for each next iteration and creates incorrect payment.
     *
     * @return void
     */
    protected function resetItems () {
        $this->_items = [];
    }
}