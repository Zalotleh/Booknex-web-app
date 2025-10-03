<?php

namespace BookneticApp\Backend\Services;

use BookneticApp\Models\Service;
use BookneticApp\Models\ServiceExtra;
use BookneticApp\Models\ServiceStaff;
use BookneticApp\Models\SpecialDay;
use BookneticApp\Models\Staff;
use BookneticApp\Models\Timesheet;
use BookneticApp\Providers\Common\PaymentGatewayService;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Math;
use Exception;

class ServiceObject
{
    private int $id;
    private string $name;
    private int $category;
    private int $duration;
    private $hide_duration;
    private $timeslot_length;
    private $price;
    private $deposit_enabled;
    private $deposit;
    private $deposit_type;
    private $hide_price;
    private $buffer_before;
    private $buffer_after;
    private $repeatable;
    private $fixed_full_period;
    private $full_period_type;
    private $full_period_value;
    private $repeat_type;
    private $recurring_payment_type;
    private $fixed_frequency;
    private $repeat_frequency;
    private $max_capacity;
    private array $employees;
    private $note;
    private $color;
    private bool $paymentMethodsEnabled = false;
    private array $paymentMethods = [];
    private bool $only_visible_to_staff;
    private $bring_people;
    private $minimum_time_required_prior_booking;
    private $enable_limited_booking_days;
    private $available_days_for_booking;
    private array $serviceExtraLimitations = [];
    private string $image = '';
    private array $schedule = [];

    private bool $isEdit;
    private array $data = [];

    private string $selectedServiceId = '';

    public function __construct()
    {
        $this->id = Helper::_post( 'id', '0', 'int' );
        $this->name = Helper::_post( 'name', '', 'string' );
        $this->category = Helper::_post( 'category', '', 'int' );
        $this->duration = Helper::_post( 'duration', '0', 'int' );
        $this->hide_duration = Helper::_post( 'hide_duration', '0', 'int', [ '1' ] );
        $this->timeslot_length = Helper::_post( 'timeslot_length', '0', 'int' );
        $this->price = Helper::_post( 'price', null, 'price' );
        $this->deposit_enabled = Helper::_post( 'deposit_enabled', '0', 'int', [ 0, 1 ] );
        $this->deposit = Helper::_post( 'deposit', null, 'float' );
        $this->deposit_type = Helper::_post( 'deposit_type', null, 'string', [ 'percent', 'price' ] );
        $this->hide_price = Helper::_post( 'hide_price', '0', 'int', [ '1' ] );
        $this->buffer_before = Helper::_post( 'buffer_before', '0', 'int' );
        $this->buffer_after = Helper::_post( 'buffer_after', '0', 'int' );
        $this->repeatable = Helper::_post( 'repeatable', '0', 'int', [ '0', '1' ] );
        $this->fixed_full_period = Helper::_post( 'fixed_full_period', '0', 'int', [ '0', '1' ] );
        $this->full_period_type = Helper::_post( 'full_period_type', '', 'string', [ 'month', 'week', 'day', 'time' ] );
        $this->full_period_value = Helper::_post( 'full_period_value', '0', 'int' );
        $this->repeat_type = Helper::_post( 'repeat_type', '', 'string', [ 'monthly', 'weekly', 'daily' ] );
        $this->recurring_payment_type = Helper::_post( 'recurring_payment_type', 'first_month', 'string', [ 'first_month', 'full' ] );
        $this->fixed_frequency = Helper::_post( 'fixed_frequency', '0', 'int', [ '0', '1' ] );
        $this->repeat_frequency = Helper::_post( 'repeat_frequency', '0', 'int' );
        $this->max_capacity = Helper::_post( 'max_capacity', '0', 'int' );
        $this->note = Helper::_post( 'note', '', 'string' );
        $this->color = Helper::_post( 'color', '', 'string' );
        $this->only_visible_to_staff = Helper::_post( 'only_visible_to_staff', 0, 'int', [ 0, 1 ] ) === 1;
        $this->bring_people = Helper::_post( 'bring_people', '1', 'int', [ 0, 1 ] );
        $this->minimum_time_required_prior_booking = Helper::_post( 'minimum_time_required_prior_booking', '0', 'int' );
        $this->enable_limited_booking_days = Helper::_post( 'enable_limited_booking_days', '0', 'int' );
        $this->available_days_for_booking = Helper::_post( 'available_days_for_booking', '0', 'int' );

        $this->isEdit = $this->id > 0;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @throws CapabilitiesException
     */
    public function hasCapability(): void
    {
        if ( $this->isEdit ) {
            Capabilities::must( 'services_edit' );
        } else {
            Capabilities::must( 'services_add' );
        }
    }

    /**
     * @throws Exception
     */
    public function validate(): void
    {
        if ( empty( $this->name ) ) {
            throw new Exception( bkntc__( 'Please fill in the "name" field correctly!' ) );
        }

        if ( is_null( $this->price ) ) {
            throw new Exception( bkntc__( 'Price field is required!' ) );
        }

        if ( ! ( $this->duration > 0 ) ) {
            throw new Exception( bkntc__( 'Duration field must be greater than zero!' ) );
        }

        if ( $this->max_capacity < 0 ) {
            throw new Exception( bkntc__( 'Capacity field is wrong!' ) );
        }

        $this->validateDeposit();
        $this->validateName();
    }

    /**
     * @throws Exception
     * */
    public function validateName(): void
    {
        $check = Service::where( 'name', $this->name )
            ->where( 'category_id', $this->category )
            ->where( 'id', '!=', $this->id )
            ->count();

        if ( $check ) {
            throw new Exception( bkntc__( 'This service name is already exist! Please choose an other name.' ) );
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function validateDeposit()
    {
        if ( ! $this->deposit_enabled || ( Helper::isSaaSVersion() && ! Capabilities::tenantCan( 'disable_deposit_payments' ) ) ) {
            return;
        }

        if ( is_null( $this->deposit ) ) {
            throw new Exception( bkntc__( 'Deposit field is required!' ) );
        }

        if ( is_null( $this->deposit_type ) ) {
            throw new Exception( bkntc__( 'Deposit type field is required!' ) );
        }

        if ( ( $this->deposit_type == 'percent' && $this->deposit > 100 ) || ( $this->deposit_type == 'price' && $this->deposit > $this->price ) ) {
            throw new Exception( bkntc__( 'Deposit can not exceed the price!' ) );
        }
    }

    public function isEdit(): bool
    {
        return $this->isEdit;
    }

    public function initData(): void
    {
        $this->data = [
            'name' => $this->name,
            'price' => Math::floor( $this->price ),
            'deposit' => $this->deposit_enabled === 1 ? Math::floor( $this->deposit ) : Math::floor( 0 ),
            'deposit_type' => $this->deposit_type,
            'hide_price' => $this->hide_price,
            'hide_duration' => $this->hide_duration,

            'category_id' => $this->category,
            'duration' => $this->duration,
            'timeslot_length' => $this->timeslot_length,
            'buffer_before' => $this->buffer_before,
            'buffer_after' => $this->buffer_after,

            'is_recurring' => $this->repeatable,

            'full_period_type' => $this->fixed_full_period ? $this->full_period_type : null,
            'full_period_value' => $this->fixed_full_period ? $this->full_period_value : 0,

            'repeat_type' => $this->repeat_type,
            'recurring_payment_type' => $this->recurring_payment_type,

            'repeat_frequency' => $this->fixed_frequency ? $this->repeat_frequency : 0,

            'max_capacity' => $this->max_capacity,

            'notes' => $this->note,
            'color' => $this->color,

            'is_visible' => 1
        ];
    }

    /**
     * @throws Exception
     */
    public function checkAllowedServiceLimit(): void
    {
        if ( $this->isEdit ) {
            return;
        }

        $allowedLimit = Capabilities::getLimit( 'services_allowed_max_number' );

        if ( $allowedLimit > -1 && Service::count() >= $allowedLimit ) {
            throw new Exception( bkntc__( 'You can\'t add more than %d Service. Please upgrade your plan to add more Service.', [ $allowedLimit ] ) );
        }
    }

    /**
     * @throws Exception
     */
    public function checkIfRecurringEnabled(): void
    {
        if ( ! $this->repeatable ) {
            $this->fixed_full_period = 0;
            $this->repeat_type = null;
            $this->fixed_frequency = 0;
            $this->recurring_payment_type = null;

            return;
        }

        if ( $this->fixed_full_period && ( empty( $this->full_period_type ) || empty( $this->full_period_value ) ) ) {
            throw new Exception( bkntc__( 'Please fill "Full period" field!' ) );
        }

        if ( empty( $this->repeat_type ) ) {
            throw new Exception( bkntc__( 'Please fill "Repeat" field!' ) );
        }

        if ( $this->fixed_frequency && empty( $this->repeat_frequency ) ) {
            throw new Exception( bkntc__( 'Please fill "Frequency" field!' ) );
        }
    }

    /**
     * @throws Exception
     */
    public function parseWeeklySchedule(): void
    {
        $schedule = Helper::_post( 'weekly_schedule', '', 'string' );

        // check weekly schedule array
        if ( empty( $schedule ) ) {
            throw new Exception( bkntc__( 'Please fill the weekly schedule correctly!' ) );
        }

        $schedule = json_decode( $schedule, true );

        if ( empty( $schedule ) || ! is_array( $schedule ) || count( $schedule ) !== 7 ) {
            return;
        }

        foreach ( $schedule as $dayInfo ) {
            if ( $this->validateDayInfo( $dayInfo ) ) {
                throw new Exception( bkntc__( 'Please fill the weekly schedule correctly!' ) );
            }

            $isDayOff = $dayInfo[ 'day_off' ];
            $timeEnd = $dayInfo[ 'end' ] == "24:00" ? "24:00" : Date::timeSQL( $dayInfo[ 'end' ] );
            $breaks = $isDayOff ? [] : $dayInfo[ 'breaks' ];

            $newBreaks = [];

            foreach ( $breaks as $break ) {
                if ( is_array( $break )
                    && isset( $break[ 0 ] ) && is_string( $break[ 0 ] )
                    && isset( $break[ 1 ] ) && is_string( $break[ 1 ] )
                    && Date::epoch( $break[ 1 ] ) > Date::epoch( $break[ 0 ] )
                ) {
                    $newBreaks[] = [ Date::timeSQL( $break[ 0 ] ), Date::timeSQL( $break[ 1 ] ) ];
                }
            }

            $this->schedule[] = [
                'day_off' => $isDayOff,
                'start' => $isDayOff ? '' : Date::timeSQL( $dayInfo[ 'start' ] ),
                'end' => $isDayOff ? '' : $timeEnd,
                'breaks' => $newBreaks,
            ];
        }
    }

    private function validateDayInfo( $info ): bool
    {
        return ! (
            isset( $info[ 'start' ] ) && is_string( $info[ 'start' ] )
            && isset( $info[ 'end' ] ) && is_string( $info[ 'end' ] )
            && isset( $info[ 'day_off' ] ) && is_numeric( $info[ 'day_off' ] )
            && isset( $info[ 'breaks' ] ) && is_array( $info[ 'breaks' ] )
        );
    }

    /**
     * @throws Exception
     */
    public function parseStaffList(): void
    {
        $employees = Helper::_post( 'employees', '', 'string' );
        $employees = json_decode( $employees, true );
        $employees = is_array( $employees ) ? $employees : [];

        //todo://bunları string key-lərlə əvəzləmək lazımdı, burdan heç kim heç nə başa düşmür.
        foreach ( $employees as $staff ) {
            if (
                isset( $staff[ 0 ] ) && is_numeric( $staff[ 0 ] ) && $staff[ 0 ] > 0
                && isset( $staff[ 1 ] ) && is_numeric( $staff[ 1 ] ) && $staff[ 1 ] >= -1
                && isset( $staff[ 2 ] ) && is_numeric( $staff[ 2 ] ) && $staff[ 2 ] >= -1
                && isset( $staff[ 3 ] ) && is_string( $staff[ 3 ] ) && in_array( $staff[ 3 ], [ 'percent', 'price' ] ) ) {
                if ( isset( $this->employees[ (int) $staff[ 0 ] ] ) ) {
                    throw new Exception( bkntc__( 'Duplicate Staff selected!' ) );
                }

                if ( $staff[ 1 ] != -1 && ( ( $staff[ 3 ] == 'percent' && $staff[ 2 ] > 100 ) || ( $staff[ 3 ] == 'price' && $staff[ 2 ] > $staff[ 1 ] ) ) ) {
                    throw new Exception( bkntc__( 'Deposit can not exceed the price!' ) );
                }

                $this->employees[ (int) $staff[ 0 ] ] = [
                    Math::floor( $staff[ 1 ] ),
                    Math::floor( $staff[ 2 ] ),
                    $staff[ 3 ]
                ];
            }
        }
    }

    /**
     * @throws Exception
     */
    public function handleImage(): void
    {
        if ( ! isset( $_FILES[ 'image' ] ) || ! is_string( $_FILES[ 'image' ][ 'tmp_name' ] ) ) {
            return;
        }

        $pathInfo = pathinfo( $_FILES[ "image" ][ "name" ] );
        $extension = strtolower( $pathInfo[ 'extension' ] );

        if ( ! in_array( $extension, [ 'jpg', 'jpeg', 'png' ] ) ) {
            throw new Exception( bkntc__( 'Only JPG and PNG images allowed!' ) );
        }

        $this->image = md5( base64_encode( rand( 1, 9999999 ) . microtime( true ) ) ) . '.' . $extension;
        $fileName = Helper::uploadedFile( $this->image, 'Services' );

        move_uploaded_file( $_FILES[ 'image' ][ 'tmp_name' ], $fileName );

        $this->data[ 'image' ] = $this->image;
    }

    /**
     * @throws Exception
     */
    public function handleCustomPaymentMethods(): void
    {
        $this->paymentMethodsEnabled = Helper::_post( 'custom_payment_methods_enabled', 0, 'int', [ 1 ] ) === 1;
        $selectedMethods = explode( ',', Helper::_post( 'custom_payment_methods', '', 'string' ) );
        $this->paymentMethods = array_intersect( $selectedMethods, PaymentGatewayService::getInstalledGatewayNames() );

        if ( $this->paymentMethodsEnabled && empty( $selectedMethods ) ) {
            throw new Exception( bkntc__( 'At least one payment method should be selected!' ) );
        }

    }

    public function applyFilters(): void
    {
        $this->data = apply_filters( 'service_sql_data', $this->data );
    }

    /**
     * Saves the current object.
     */
    public function save(): void
    {
        if ( $this->isEdit ) {
            $this->update();
            return;
        }

        $this->insert();
    }

    private function update(): void
    {
        if ( empty( $this->image ) ) {
            unset( $this->data[ 'image' ] );
        } else {
            $this->removeOldImage();
        }

        Service::where( 'id', $this->id )->update( $this->data );
        ServiceStaff::where( 'service_id', $this->id )->delete();
        Timesheet::where( 'service_id', $this->id )->delete();
    }

    private function insert(): void
    {
        $this->data[ 'is_active' ] = 1;

        Service::insert( $this->data );

        $this->id = DB::lastInsertedId();
    }

    private function removeOldImage(): void
    {
        $oldInfo = Service::get( $this->id );

        if ( empty( $oldInfo[ 'image' ] ) ) {
            return;
        }

        $filePath = Helper::uploadedFile( $oldInfo[ 'image' ], 'Services' );

        if ( is_file( $filePath ) && is_writable( $filePath ) ) {
            unlink( $filePath );
        }
    }

    public function saveOptions(): void
    {
        if ( Helper::getMinTimeRequiredPriorBooking() == $this->minimum_time_required_prior_booking ) {
            $this->minimum_time_required_prior_booking = -1; // it should always be equal to default settings until it is manually changed
        }

        Service::setData( $this->id, 'bring_people', $this->bring_people );
        Service::setData( $this->id, 'only_visible_to_staff', $this->only_visible_to_staff );
        Service::setData( $this->id, 'minimum_time_required_prior_booking', $this->minimum_time_required_prior_booking );
    }

    public function saveSettings(): void
    {
        Service::setData( $this->id, 'enable_limited_booking_days', $this->enable_limited_booking_days );
        Service::setData( $this->id, 'available_days_for_booking', $this->available_days_for_booking );

        $this->savePaymentSettings();
    }

    private function savePaymentSettings(): void
    {
        if ( $this->isEdit && ! $this->paymentMethodsEnabled ) {
            Service::deleteData( $this->id, 'custom_payment_methods' );
        } else if ( $this->paymentMethodsEnabled ) {
            Service::setData( $this->id, 'custom_payment_methods', json_encode( $this->paymentMethods ) );
        }
    }

    public function saveWeeklySchedule(): void
    {
        if ( empty( $this->schedule ) ) {
            return;
        }

        Timesheet::insert( [
            'timesheet' => json_encode( $this->schedule ),
            'service_id' => $this->id
        ] );
    }

    public function saveSpecialDays(): void
    {
        $specialDays = Helper::_post( 'special_days', '', 'string' );

        $specialDays = json_decode( $specialDays, true );
        $specialDays = is_array( $specialDays ) ? $specialDays : [];

        $specialDayIds = [];

        foreach ( $specialDays as $day ) {
            if (
                ! (
                    isset( $day[ 'date' ] ) && is_string( $day[ 'date' ] )
                    && isset( $day[ 'start' ] ) && is_string( $day[ 'start' ] )
                    && isset( $day[ 'end' ] ) && is_string( $day[ 'end' ] )
                    && isset( $day[ 'breaks' ] ) && is_array( $day[ 'breaks' ] )
                )
            ) {
                continue;
            }

            $spId = isset( $day[ 'id' ] ) ? (int) $day[ 'id' ] : 0;
            $date = Date::dateSQL( Date::reformatDateFromCustomFormat( $day[ 'date' ] ) );

            $newBreaks = [];

            foreach ( $day[ 'breaks' ] as $break ) {
                if ( is_array( $break )
                    && isset( $break[ 0 ] ) && is_string( $break[ 0 ] )
                    && isset( $break[ 1 ] ) && is_string( $break[ 1 ] )
                    && Date::epoch( $break[ 1 ] ) > Date::epoch( $break[ 0 ] )
                ) {
                    $newBreaks[] = [ Date::timeSQL( $break[ 0 ] ), $break[ 1 ] == "24:00" ? "24:00" : Date::timeSQL( $break[ 1 ] ) ];
                }
            }

            $timesheet = json_encode( [
                'day_off' => 0,
                'start' => Date::time( $day[ 'start' ] ),
                'end' => $day[ 'end' ] == "24:00" ? "24:00" : Date::timeSQL( $day[ 'end' ] ),
                'breaks' => $newBreaks,
            ] );

            if ( $spId > 0 ) {
                SpecialDay::where( 'id', $spId )
                    ->where( 'service_id', $this->id )
                    ->update( [
                        'timesheet' => $timesheet,
                        'date' => $date
                    ] );

                $specialDayIds[] = $spId;
            } else {
                SpecialDay::insert( [
                    'timesheet' => $timesheet,
                    'date' => $date,
                    'service_id' => $this->id
                ] );

                $specialDayIds[] = DB::lastInsertedId();
            }
        }

        if ( ! $this->isEdit ) {
            return;
        }

        $oldDays = SpecialDay::where( 'service_id', $this->id );

        if ( ! empty( $specialDayIds ) ) {
            $oldDays = $oldDays->where( 'id', 'not in', $specialDayIds );
        }

        $oldDays->delete();
    }

    public function saveServiceStaff(): void
    {
        if ( ! Capabilities::tenantCan( 'staff' ) ) {
            $this->employees = [
                Staff::limit( 1 )->fetch()->id => [ -1, -1, 'percent' ]
            ];
        }

        if ( empty( $this->employees ) ) {
            return;
        }

        foreach ( $this->employees as $staffId => $price ) {
            ServiceStaff::insert( [
                'service_id' => $this->id,
                'staff_id' => $staffId,
                'price' => $price[ 0 ],
                'deposit' => $price[ 1 ],
                'deposit_type' => $price[ 2 ]
            ] );
        }
    }

    public function saveServiceExtras(): void
    {
        if ( $this->isEdit ) {
            return;
        }

        $extras = Helper::_post( 'extras', '', 'string' );
        $extras = json_decode( $extras, true );

        $extras1 = [];

        foreach ( $extras as $extraId ) {
            if ( is_numeric( $extraId ) && $extraId > 0 ) {
                $extras1[] = (int) $extraId;
            }
        }

        if ( empty( $extras1 ) ) {
            return;
        }

        ServiceExtra::where( 'id', $extras1 )->update( [ 'service_id' => $this->id ] );
    }

    /**
     * todo://burda refactoring etmek olar mence
     */
    public function saveOrderOption(): void
    {
        $orderOption = json_decode( Helper::getOption( "services_order", '[]' ), true );

        if ( empty( $orderOption ) || ! is_array( $orderOption ) ) {
            return;
        }

        $savedCategory = $orderOption[ $this->category ] ?? [];

        if ( $this->isEdit ) {
            /**
             * Eger edit edirikse onda baxiriqki servisin kateqoriyasi deyisib ya yox
             * Eger kateqoriya deyismeyibse onda hecne etmirik
             * */
            if ( ! in_array( $this->id, $savedCategory ) ) {
                /** First find service's previous category*/
                $previousCategory = null;

                foreach ( $orderOption as $key => $serviceIDS ) {
                    if ( in_array( $this->id, $serviceIDS ) ) {
                        $previousCategory = $key;
                    }
                }

                /** Eger kateqoriya idsi tapilirsa, servisin kateqoriyasini deyisdiyimiz ucun, serivisin id-ni array dan silirik*/
                if ( ! is_null( $previousCategory ) && isset( $orderOption[ $previousCategory ] ) ) {
                    $previousCategoryData = $orderOption[ $previousCategory ];

                    if ( ( $key = array_search( $this->id, $previousCategoryData ) ) !== false ) {
                        unset( $previousCategoryData[ $key ] );
                    }

                    $orderOption[ $previousCategory ] = $previousCategoryData;
                }

                $savedCategory[] = $this->id;
                $orderOption[ $this->category ] = $savedCategory;
            }
        } else {
            /** If not editing just insert new id*/
            $savedCategory[] = $this->id;
            $orderOption[ $this->category ] = $savedCategory;
        }

        Helper::setOption( "services_order", json_encode( $orderOption ) );
    }

    public function saveTranslations(): void
    {
        Service::handleTranslation($this->id);
    }

    /**
     * @throws Exception
     */
    public function handleServiceExtraLimitations(): void
    {
        $minLimitEnabled = Helper::_post('service_extra_min_limit_enabled', 0, 'int', [1]);
        $maxLimitEnabled = Helper::_post('service_extra_max_limit_enabled', 0, 'int', [1]);

        $minLimit = $minLimitEnabled ? Helper::_post('service_extra_min_limit', 0, 'int') : 0;
        $maxLimit = $maxLimitEnabled ? Helper::_post('service_extra_max_limit', 0, 'int') : 0;
        $this->selectedServiceId = Helper::_post('id', '', 'int');

        $this->serviceExtraLimitations = [
            'min' => $minLimit,
            'max' => $maxLimit,
        ];

        $this->validateAndGenerateServiceExtraLimitationsError();
        $this->saveServiceExtraLimitations();
    }

    private function validateAndGenerateServiceExtraLimitationsError()
    {
        $min = $this->serviceExtraLimitations['min'];
        $max = $this->serviceExtraLimitations['max'];
        $selectedServiceId = $this->selectedServiceId;

        if ($min < 0) {
            throw new Exception(bkntc__("Service extra limiter minimum value must be a positive number"));
        }

        if($max < 0) {
            throw new Exception(bkntc__("Service extra limiter maximum value must be a positive number"));
        }

        if ($min > $max && $max !== 0) {
            throw new Exception(bkntc__("Service extra limiter minimum limit can't exceed the maximum limit."));
        }

        $serviceExtras = ServiceExtra::fetchAll();

        if (is_null($serviceExtras)) {
            return;
        }

        $filteredExtras = array_filter(
            $serviceExtras,
            fn($extra) => $extra['service_id'] == $selectedServiceId
        );

        $serviceExtraCount = count($filteredExtras);

        if (($min !== 0 && $min > $serviceExtraCount) || ($max !== 0 && $max > $serviceExtraCount)) {
            throw new Exception(bkntc__("Invalid service extra limiter value"));
        }

        $totalMinRequired = 0;
        // edge case -> each service extra might have its own limitations
        foreach ($serviceExtras as $extra) {
            if (
                is_null($extra) ||
                $extra['service_id'] !== $selectedServiceId ||
                empty($extra['is_active']) ||
                $extra['is_active'] !== '1'
            ) {
                continue;
            }

            if (!empty($extra['min_quantity']) && $extra['min_quantity'] > 0) {
                $totalMinRequired++;
            }
        }

        if (($min !== 0 && $min < $totalMinRequired) || ($max !== 0 && $max < $totalMinRequired)) {
            throw new Exception(bkntc__("Your service extra count and the quantity of service extras are in conflict!"));
        }
    }

    public function saveServiceExtraLimitations(): void
    {
        $encodedLimitations = json_encode($this->serviceExtraLimitations);
        Service::setData(
            $this->id,
            'service_extra_limitations',
            $encodedLimitations
        );
    }
}
