<?php

namespace BookneticApp\Backend\Staff;

use BookneticApp\Models\Holiday;
use BookneticApp\Models\Location;
use BookneticApp\Models\ServiceStaff;
use BookneticApp\Models\SpecialDay;
use BookneticApp\Models\Staff;
use BookneticApp\Models\Timesheet;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Helpers\Math;
use BookneticSaaS\Models\Tenant;
use Exception;
use WP_User;

class StaffObject
{
    private int $id;
    private int $wpUser;
    private string $name;
    private string $profession;
    private string $phone;
    private string $email;
    private int $allowToLogin;
    private string $useExistingWpUser;
    private string $wpUserPassword;
    private int $updateWpUser;
    private string $note;
    private array $locations = [];
    private array $services = [];
    private array $weeklySchedule = [];
    private array $oldInfo = [];
    private int $oldWpUser;
    private string $profileImage = '';

    private bool $isEdit;
    private array $data;
    private array $serviceStaff = [];

    public function __construct()
    {
        $this->id = Helper::_post( 'id', '0', 'integer' );
        $this->wpUser = Helper::_post( 'wp_user', '0', 'integer' );
        $this->name = Helper::_post( 'name', '', 'string' );
        $this->profession = Helper::_post( 'profession', '', 'string' );
        $this->phone = Helper::_post( 'phone', '', 'string' );
        $this->email = Helper::_post( 'email', '', 'email' );
        $this->allowToLogin = Helper::_post( 'allow_staff_to_login', '0', 'int', [ '0', '1' ] );
        $this->useExistingWpUser = Helper::_post( 'wp_user_use_existing', 'yes', 'string', [ 'yes', 'no' ] );
        $this->wpUserPassword = Helper::_post( 'wp_user_password', '', 'string' );
        $this->updateWpUser = Helper::_post( 'update_wp_user', '0', 'int', [ '0', '1' ] );
        $this->note = Helper::_post( 'note', '', 'string' );

        $this->data = [
            'name' => $this->name,
            'profession' => $this->profession,
            'phone_number' => $this->phone,
            'about' => $this->note,
            'email' => $this->email
        ];

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
            Capabilities::must( 'staff_edit' );
        } else {
            Capabilities::must( 'staff_add' );
        }
    }

    /**
     * @throws Exception
     */
    public function validate(): void
    {
        if ( ! empty( $this->name ) && ! empty( $this->email ) ) {
            return;
        }

        throw new Exception( bkntc__( 'Please fill in all required fields correctly!' ) );
    }

    public function isEdit(): bool
    {
        return $this->isEdit;
    }

    /**
     * @throws Exception
     */
    public function checkIfUserCanCreateNewStaff()
    {
        if ( $this->isEdit ) {
            return;
        }

        if ( Permission::isAdministrator() ) {
            return;
        }

        if ( ! ( Permission::isAdministrator() || Capabilities::userCan( 'staff_add' ) ) && ! in_array( $this->id, Permission::myStaffId() ) ) {
            throw new Exception( bkntc__( 'You do not have sufficient permissions to perform this action' ) );
        }

    }

    /**
     * @throws Exception
     */
    public function fetchOldStaffInfo(): void
    {
        if ( ! $this->isEdit ) {
            return;
        }

        $this->oldInfo = Staff::get( $this->id )->toArray();

        if ( ! $this->oldInfo ) {
            throw new Exception( bkntc__( 'Staff not found or permission denied!' ) );
        }

        $this->oldWpUser = $this->oldInfo[ 'user_id' ] ?? 0;
    }

    /**
     * @throws Exception
     */
    public function fetchLocations(): void
    {
        $locations = Helper::_post( 'locations', '', 'string' );

        if ( ! Capabilities::tenantCan( 'locations' ) ) {
            $locations = $this->isEdit ? $this->oldInfo[ 'locations' ] : Location::limit( 1 )->fetch()->id;
        }

        $locations = explode( ',', $locations );

        foreach ( $locations as $location ) {
            if ( is_numeric( $location ) && $location > 0 ) {
                $this->locations[] = (int) $location;
            }
        }

        if ( empty( $this->locations ) ) {
            throw new Exception( bkntc__( 'Please select a location!' ) );
        }

        $this->data[ 'locations' ] = implode( ',', $this->locations );
    }

    public function fetchServices(): void
    {
        $services = Helper::_post( 'services', '', 'string' );
        $services = explode( ',', $services );

        foreach ( $services as $service ) {
            if ( is_numeric( $service ) && $service > 0 ) {
                $this->services[] = (int) $service;
            }
        }
    }

    /**
     * @throws Exception
     */
    public function checkAllowedStaffLimit(): void
    {
        if ( $this->isEdit ) {
            return;
        }

        $allowedLimit = Capabilities::getLimit( 'staff_allowed_max_number' );

        if ( $allowedLimit > -1 && Staff::count() >= $allowedLimit ) {
            throw new Exception( bkntc__( 'You can\'t add more than %d Staff. Please upgrade your plan to add more Staff.', [ $allowedLimit ] ) );
        }
    }

    /**
     * @throws Exception
     */
    public function parseWeeklySchedule(): void
    {
        $schedule = Helper::_post( 'weekly_schedule', '', 'string' );

        // check a weekly schedule array
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

            $this->weeklySchedule[] = [
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
    public function handleStaffLogin(): void
    {
        if ( ! Permission::isAdministrator() && ! Capabilities::userCan( 'staff_allow_to_login' ) ) {
            return;
        }

        if ( $this->allowToLogin != 1 ) {
            $this->loginDisallowed();
            return;
        }

        if ( $this->useExistingWpUser == 'yes' ) {
            $this->wpUserSpecified();
        } else if ( $this->useExistingWpUser == 'no' ) {
            $this->wpUserNotSpecified();
        }

        $this->data[ 'user_id' ] = $this->wpUser;
    }

    private function loginDisallowed(): void
    {
        $this->removeRoleFromOldUser();

        $this->wpUser = 0;
        $this->data[ 'user_id' ] = 0;
    }

    /**
     * @throws Exception
     */
    private function wpUserSpecified(): void
    {
        if ( ! ( $this->wpUser > 0 ) ) {
            throw new Exception( bkntc__( 'Please select WordPress user!' ) );
        }

        if ( isset( $this->oldWpUser ) && $this->wpUser !== $this->oldWpUser ) {
            $this->removeRoleFromOldUser();
        }

        $this->addRoleToNewUser();


        if ( ! $this->isEdit || ! $this->updateWpUser ) {
            return;
        }

        $this->wpUpdateUser( [
            'user_email' => $this->email,
            'display_name' => $this->name,
            'first_name' => $this->name,
        ] );

        DB::DB()->update( DB::DB()->users, [
            'user_login' => $this->email
        ], [
            'ID' => $this->wpUser
        ] );
    }

    /**
     * @throws Exception
     */
    private function wpUserNotSpecified(): void
    {
        $emailExists = email_exists( $this->email );
        $userNameExists = username_exists( $this->email );
        $wpUserExists = $emailExists !== false || $userNameExists !== false;

        if ( ! ( $this->isEdit && $this->oldWpUser > 0 ) && empty( $this->wpUserPassword ) ) {
            throw new Exception( bkntc__( 'Please type the password of the WordPress user!' ) );
        } else if ( ( ! $this->isEdit || $this->email != $this->oldInfo[ 'email' ] ) && $wpUserExists ) {
            throw new Exception( bkntc__( 'The WordPress user with the same email address already exists!' ) );
        }

        if ( $wpUserExists ) {
            $checkTenantExistWithSameEmail = Tenant::where('email', $this->email)->fetch();

            if( $checkTenantExistWithSameEmail )
                throw new Exception( bkntc__( 'You can not add tenant email to a staff!' ) );

            $wpUser = empty( $emailExists ) ? $userNameExists : $emailExists;
            $userToBeUpdated = get_userdata( $wpUser );
            $isUserLoginEmail = filter_var( $userToBeUpdated->user_login, FILTER_VALIDATE_EMAIL );

            $userUpdateInfo = [
                'ID' => $wpUser,
                'user_email' => $this->email,
                'display_name' => $this->name,
                'role' => 'booknetic_staff',
                'first_name' => $this->name
            ];

            if ( $isUserLoginEmail )
                $userUpdateInfo[ 'user_login' ] = $this->email;

            if ( ! empty( $this->wpUserPassword ) ) {
                $userUpdateInfo[ 'user_pass' ] = $this->wpUserPassword;
            }

            $wpUser = wp_update_user( $userUpdateInfo );
        } else {
            $wpUser = wp_insert_user( [
                'user_login' => $this->email,
                'user_email' => $this->email,
                'display_name' => $this->name,
                'first_name' => $this->name,
                'last_name' => '',
                'role' => 'booknetic_staff',
                'user_pass' => $this->wpUserPassword
            ] );
        }

        if ( is_wp_error( $wpUser ) ) {
            throw new Exception( $wpUser->get_error_message() );
        }

        $this->wpUser = $wpUser;
    }

    /**
     * @throws Exception
     */
    private function wpUpdateUser( $data ): void
    {
        $data[ 'ID' ] = $this->wpUser;

        $wpError = wp_update_user( $data );

        if ( is_wp_error( $wpError ) ) {
            throw new Exception( $wpError->get_error_message() );
        }
    }

    private function removeRoleFromOldUser(): void
    {
        if ( ! $this->isEdit || $this->oldWpUser <= 0 ) {
            return;
        }

        $userData = get_userdata( $this->oldWpUser );

        if ( ! $userData || ! in_array( 'booknetic_staff', $userData->roles ) ) {
            return;
        }

        $user = new WP_User( $this->oldWpUser );
        $user->remove_role( 'booknetic_staff' );
    }

    private function addRoleToNewUser(): void
    {
        $user = new WP_User( $this->wpUser );
        $user->add_role( 'booknetic_staff' );
    }

    /**
     * @throws Exception
     */
    public function handleProfileImage(): void
    {
        if ( ! isset( $_FILES[ 'image' ] ) || ! is_string( $_FILES[ 'image' ][ 'tmp_name' ] ) ) {
            return;
        }

        $pathInfo = pathinfo( $_FILES[ "image" ][ "name" ] );
        $extension = strtolower( $pathInfo[ 'extension' ] );

        if ( ! in_array( $extension, [ 'jpg', 'jpeg', 'png' ] ) ) {
            throw new Exception( bkntc__( 'Only JPG and PNG images allowed!' ) );
        }

        $this->profileImage = md5( base64_encode( rand( 1, 9999999 ) . microtime( true ) ) ) . '.' . $extension;
        $fileName = Helper::uploadedFile( $this->profileImage, 'Staff' );

        move_uploaded_file( $_FILES[ 'image' ][ 'tmp_name' ], $fileName );

        $this->data[ 'profile_image' ] = $this->profileImage;
    }

    public function applySqlDataFilters(): void
    {
        if ( $this->isEdit && $this->oldInfo[ 'user_id' ] > 0 && ! Permission::isAdministrator() ) {
            $this->data[ 'email' ] = $this->oldInfo[ 'email' ];
        }

        $this->data = apply_filters( 'staff_sql_data', $this->data );
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
        do_action( 'bkntc_staff_created', $this->getId() );
    }

    /**
     * Update the staff information and delete related timesheets and service staff records.
     */
    private function update(): void
    {
        if ( empty( $this->profileImage ) ) {
            unset( $this->data[ 'profile_image' ] );
        } else if ( ! empty( $this->oldInfo[ 'profile_image' ] ) ) {
            $this->removePreviousImage();
        }

        Staff::whereId( $this->id )->update( $this->data );
        Timesheet::where( 'staff_id', $this->id )->delete();

        $this->serviceStaff = ServiceStaff::where( 'staff_id', $this->id )->fetchAll();

        ServiceStaff::where( 'staff_id', $this->id )->delete();
    }

    /**
     * Inserts the staff data into the database.
     */
    private function insert(): void
    {
        $this->data[ 'is_active' ] = 1;

        Staff::insert( $this->data );

        $this->id = DB::lastInsertedId();
    }

    private function removePreviousImage(): void
    {
        $filePath = Helper::uploadedFile( $this->oldInfo[ 'profile_image' ], 'Staff' );

        if ( is_file( $filePath ) && is_writable( $filePath ) ) {
            unlink( $filePath );
        }
    }

    public function saveServiceStaff(): void
    {
        foreach ( $this->services as $serviceId ) {
            $serviceStaffData = [
                'staff_id' => $this->id,
                'service_id' => $serviceId,
                'price' => Math::floor( -1 ),
                'deposit' => Math::floor( -1 ),
                'deposit_type' => 'percent'
            ];

            if ( ! empty( $this->serviceStaff ) ) {
                foreach ( $this->serviceStaff as $row ) {
                    if ( $row->service_id == $serviceId && $row->staff_id == $this->id ) {
                        unset( $row[ 'id' ] );
                        $serviceStaffData = $row->toArray();
                    }
                }
            }

            ServiceStaff::insert( $serviceStaffData );
        }
    }

    public function saveWeeklySchedule(): void
    {
        if ( empty( $this->weeklySchedule ) ) {
            return;
        }

        Timesheet::insert( [
            'timesheet' => json_encode( $this->weeklySchedule ),
            'staff_id' => $this->id
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
                    $newBreaks[] = [ Date::timeSQL( $break[ 0 ] ), $break[ 1 ] == '24:00' ? '24:00' : Date::timeSQL( $break[ 1 ] ) ];
                }
            }

            $timesheet = json_encode( [
                'day_off' => 0,
                'start' => Date::timeSQL( $day[ 'start' ] ),
                'end' => ( $day[ 'end' ] == "24:00" ? "24:00" : Date::timeSQL( $day[ 'end' ] ) ),
                'breaks' => $newBreaks,
            ] );

            if ( $spId > 0 ) {
                SpecialDay::where( 'id', $spId )
                    ->where( 'staff_id', $this->id )
                    ->update( [
                        'timesheet' => $timesheet,
                        'date' => $date
                    ] );

                $specialDayIds[] = $spId;
            } else {
                SpecialDay::insert( [
                    'timesheet' => $timesheet,
                    'date' => $date,
                    'staff_id' => $this->id
                ] );

                $specialDayIds[] = DB::lastInsertedId();
            }
        }

        if ( ! $this->isEdit ) {
            return;
        }

        $oldDays = SpecialDay::where( 'staff_id', $this->id );

        if ( ! empty( $specialDayIds ) ) {
            $oldDays = $oldDays->where( 'id', 'not in', $specialDayIds );
        }

        $oldDays->delete();
    }

    public function saveHolidays(): void
    {
        $holidays = Helper::_post( 'holidays', '', 'string' );
        $holidays = json_decode( $holidays, true );
        $holidays = is_array( $holidays ) ? $holidays : [];

        $holidayIds = [];

        foreach ( $holidays as $holiday ) {
            if ( ! is_numeric( $holiday[ 'id' ] ) ) {
                continue;
            }

            if ( empty( $holiday[ 'date' ] ) || ! is_string( $holiday[ 'date' ] ) ) {
                continue;
            }

            $holidayId = (int) $holiday[ 'id' ];
            $holidayDate = Date::dateSQL( $holiday[ 'date' ] );

            if ( $holidayId === 0 ) {
                Holiday::insert( [
                    'date' => $holidayDate,
                    'staff_id' => $this->id
                ] );

                $holidayIds[] = DB::lastInsertedId();
            } else {
                $holidayIds[] = $holidayId;
            }
        }

        if ( ! $this->isEdit ) {
            return;
        }

        $oldDays = Holiday::where( 'staff_id', $this->id );

        if ( ! empty( $holidayIds ) ) {
            $oldDays = $oldDays->where( 'id', 'not in', $holidayIds );
        }

        $oldDays->delete();
    }

    public function saveTranslations(): void
    {
        Staff::handleTranslation( $this->id );
    }
}