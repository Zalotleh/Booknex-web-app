<?php

namespace
{

    use BookneticApp\Backend\Settings\Helpers\LocalizationService;

    /**
	 * @param $text
	 * @param $params
	 * @param $esc
	 * @param $textdomain
	 *
	 * @return mixed
	 */
	function bkntc__( $text, $params = [], $esc = true, $textdomain = null )
	{
        $textdomain = $textdomain ?: LocalizationService::getTextdomain();

		if( empty( $params ) )
		{
			$result = trim( __($text, $textdomain ) );
		}
		else
		{
			$args = array_merge( [ trim( __($text, $textdomain ) ) ] , (array)$params );
			$result =  call_user_func_array('sprintf', $args );
		}

        return $esc ? htmlspecialchars($result) : $result;
	}
}

namespace BookneticApp\Providers\Core
{

	use BookneticApp\Config;
	use BookneticApp\Providers\Helpers\Helper;

	/**
	 * Class Bootstrap
	 * @package BookneticApp
	 */
	class Bootstrap
	{

        /**
         * @var AddonLoader[]
         */
        public static array $addons = [];

		public function __construct()
		{
            if ( Helper::getOption( 'is_updating', '0', false ) == '1' )
            {
                add_action( 'admin_notices', function () {
                    echo '<div class="notice notice-warning"><p>' . bkntc__( 'Booknetic is updating, please wait.' ) . '</p></div>';
                } );
                return;
            }

			Config::load();

			if( ! $this->isInstalled() )
			{
				add_action('init', [$this, 'initPluginInstallationPage']);
                return;
			}

            if ( LicenseService::checkLicense() === false )
            {
                add_action('init', [$this, 'initPluginDisabledPage']);
                return;
            }

            add_action('plugins_loaded', function ()
            {
                static::$addons = apply_filters( 'bkntc_addons_load', [] );
            });

            add_action('init', [$this, 'initApp'], 10);
		}

		public static function isAddonEnabled( string $slug ): bool {
			return isset( self::$addons[ $slug ] );
		}

		public function initApp()
		{
            Backend::updateAddonsDB();

			do_action( 'bkntc_init' );

			if ( !Helper::isAdmin() || ( Helper::isAjax() && !Helper::isUpdateProcess() ) )
			{
				Frontend::init();
			}
			else if( Helper::isAdmin() )
			{
				Backend::init();
			}

            CronJob::init();
		}

		public function initPluginInstallationPage()
		{
			if( Helper::isAdmin() )
			{
				Backend::initInstallation();
			}
		}

		public function initPluginDisabledPage()
		{
			if( Helper::isAdmin() )
			{
				Backend::initDisabledPage();
			}
		}

		private function isInstalled()
		{
			$purchase_code = Helper::getOption('purchase_code', '', false);
			$version = Helper::getOption('plugin_version', '', false);

			if( empty( $purchase_code ) && empty( $version ) )
				return false;

			return true;
		}

	}

}
