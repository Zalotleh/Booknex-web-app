<?php

namespace BookneticApp\Backend\Settings\Helpers;

use BookneticApp\Providers\Core\Bootstrap;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\Core\Permission;
use BookneticVendor\Gettext\Translation;
use BookneticVendor\Gettext\Translations;

class LocalizationService
{

    private static $textdomains = [];

    public static function getPoFile( $language, $is_save_action = false , $slug = 'booknetic' )
    {
        return self::languagesPath( $slug . '-' . $language . '.po', $is_save_action , $slug );
    }

    public static function getMoFile( $language, $is_save_action = false , $slug = 'booknetic' )
    {
        return self::languagesPath( $slug . '-' . $language . '.mo', $is_save_action , $slug);
    }

    public static function getPotFile( $slug )
    {
        return WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . $slug . '.pot';
    }

    public static function saveFiles( $language, $array , $slug = 'booknetic' )
    {
        global $wp_textdomain_registry;

        /**
         * Tercume olunan dil`i aktivleshdirim, textdomaini o dile uygun tezeden load edir.
         */
        LocalizationService::setLanguage( $language );
        LocalizationService::loadTextdomain();
        foreach ( Bootstrap::$addons AS $addon )
        {
            LocalizationService::loadTextdomain( $addon->getAddonSlug() );
        }
        $textDomain = LocalizationService::getTextdomain( $slug );

        /**
         * Bu IF sonradan elave edildi. .l10n.php yaratsin deye bu IF yazildi,
         * amma nezere almag lazimdiki bu Classlar 6.1/6.5 versiyalardan sonrani destekleyirler.
         * IF-den sonraki block ile diger butun alqoritmalar eyni olmalidi.
         */
        if (class_exists('\WP_Translation_File') && class_exists('\WP_Translation_File_PHP'))
        {
            $moFilePath = self::getMoFile( $language,false , $slug );
            $phpFilePath = str_replace('.mo', '.l10n.php', $moFilePath);

            if( isset( $wp_textdomain_registry ) && ! empty( $wp_textdomain_registry->get($textDomain, $language) ) && file_exists( $wp_textdomain_registry->get($textDomain, $language) . $slug . '-' . $language . '.mo' ) )
            {
                $transFile = \WP_Translation_File::create( $wp_textdomain_registry->get($textDomain, $language) . $slug . '-' . $language . '.mo' );
            }
            else if (is_readable($phpFilePath))
            {
                $transFile = \WP_Translation_File::create($phpFilePath);
            }
            else if (is_readable($moFilePath))
            {
                $transFile = \WP_Translation_File::create($moFilePath);
            }
            else
            {
                $translations = Translations::fromPoFile( self::getPotFile( $slug ) );
                $translations->toMoFile( self::getMoFile( $language,true , $slug ) );
                $transFile = \WP_Translation_File::create(self::getMoFile( $language,true , $slug ));
            }
            $mutableTransFile = WP_Translation_File_Booknetic::cloneFrom($transFile);

            foreach ( $array AS $msgId => $msgStr )
            {
                $mutableTransFile->setEntry($msgId, $msgStr);
            }

            $moFilePath = self::getMoFile( $language,true , $slug );
            $mutableTransFile->saveToDisk(str_replace('.mo', '', $moFilePath));

            $translations = Translations::fromMoFile($moFilePath);
            $translations->toPoFile( self::getPoFile( $language, true , $slug ) );

            return true;
        }
        else
        {
            if( isset( $wp_textdomain_registry ) && ! empty( $wp_textdomain_registry->get($textDomain, $language) ) && file_exists( $wp_textdomain_registry->get($textDomain, $language) . $slug . '-' . $language . '.po' ) )
            {
                $translations = Translations::fromPoFile( $wp_textdomain_registry->get($textDomain, $language) . $slug . '-' . $language . '.po' );
            }
            else if( file_exists( self::getPoFile( $language,false , $slug ) ) )
            {
                $translations = Translations::fromPoFile( self::getPoFile( $language,false , $slug ) );
            }
            else
            {
                $translations = Translations::fromPoFile( self::getPotFile( $slug ) );
            }

            foreach ( $array AS $msgId => $msgStr )
            {
                $find = $translations->find( null, $msgId );

                if( $find )
                {
                    $find->setTranslation( $msgStr );
                }else{
                    $translation = Translation::create(null, $msgId);
                    $translation->setTranslation( $msgStr );
                    $translations->offsetSet( $translation->getId() , $translation);
                }
            }

            $translations->toPoFile( self::getPoFile( $language, true , $slug ) );
            $translations->toMoFile( self::getMoFile( $language, true , $slug ) );

            return true;
        }
    }

    public static function availableLanguages()
    {
        require_once ABSPATH . 'wp-admin/includes/translation-install.php';
        return wp_get_available_translations();
    }

    public static function isLngCorrect( $lng_name )
    {
        if( $lng_name == 'en_US' )
            return true;

        $available_translations = self::availableLanguages();

        return isset( $available_translations[ $lng_name ] );
    }

    public static function getLanguageName( $lng )
    {
        if( $lng == 'en_US' )
            return 'English';

        $available_translations = self::availableLanguages();

        return isset( $available_translations[ $lng ] ) ? $available_translations[ $lng ]['native_name'] : $lng;
    }

    public static function languagesPath( $lang_name , $is_save_action , $slug )
    {
        $pluginLanguagePath = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR;
        $wpContentLanguagePath = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR;

        if( Helper::isSaaSVersion() && Permission::tenantId() > 0 )
        {
            $tenantPluginLanguageFullPath = $pluginLanguagePath . Permission::tenantId() . DIRECTORY_SEPARATOR . $lang_name;
            $tenantWpContentLanguageFullPath = $wpContentLanguagePath . Permission::tenantId() . DIRECTORY_SEPARATOR . $lang_name;

            if( $is_save_action )
            {
                if( ! file_exists( $tenantPluginLanguageFullPath ) && file_exists( $tenantWpContentLanguageFullPath ) )
                {
                    return $tenantWpContentLanguageFullPath;
                }
                if( ! file_exists( $pluginLanguagePath . Permission::tenantId() ) )
                {
                    mkdir( $pluginLanguagePath . Permission::tenantId(), 0777 );
                }
                return $tenantPluginLanguageFullPath;
            }
            else
            {
                if( file_exists( $tenantPluginLanguageFullPath ) )
                    return $tenantPluginLanguageFullPath;

                if( file_exists( $tenantWpContentLanguageFullPath ) )
                    return $tenantWpContentLanguageFullPath;
            }

        }

        if( ! file_exists( $pluginLanguagePath . $lang_name ) && file_exists( $wpContentLanguagePath . $lang_name ) )
        {
            return $wpContentLanguagePath . $lang_name;
        }
        return $pluginLanguagePath . $lang_name;
    }

    /**
     * SaaS versiyada Front-end booking panel uchun nezerde tutulub;
     * Tenant default langugageni deyishe biler. Meselen ola biler ki, sayt en_US-dir, tenant backendi en_US ishledir;
     * Amma isteyr ki, mushterileri booking eden zaman az dilinde gorsunler paneli.
     *
     * @return void
     */
    public static function changeLanguageIfNeed()
    {
        if( !Helper::isSaaSVersion() )
            return;

        $defaultLng = Helper::getOption('default_language', '');

        if( ! self::setLanguage( $defaultLng ) )
            return;

        self::loadTextdomain();
        foreach ( Bootstrap::$addons AS $addon )
        {
            self::loadTextdomain( $addon->getAddonSlug() );
        }
    }

    public static function setLanguage( $language )
    {
        if( empty( $language ) || !self::isLngCorrect( $language ) )
            return false;

        add_filter( 'locale', fn( $locale ) => $language );
        add_filter( 'determine_locale', fn( $locale ) => $language );
        add_filter( 'plugin_locale', fn( $locale ) => $language );

        return true;
    }

    /**
     * Hem Core hem de Addonlar bu methodun komekliyi ile Textdomain yaradirlar.
     * Normalda textdomain pluginin slug`i olmalidi. Burda biraz ferqli mexanizm tedbiq etmishik.
     * Loco ve diger tercume pluginleri ile uyumlu ishlesin deye workaround kimi yazilib bu alqoritm.
     * Bir case olur ki, mushteriler Locoda Booknetici tercume edirler ve
     * Locoda settingsi ele qururlar ki, langugage fayllari loconun oz folderinde save edilir;
     * Bu halda Tenant girse eger Settings>Front-end settings>Labels`e ki, tercume elesin, bug yaranir.
     * Bele ki, onun deyishiklik edib save etdiyi fayl gedir bizim folderde save olur (booknetic/langugages/TENANT_ID/booknetic-en_US.mo)
     * Biz sonra ceht edirik ki, textdomain load olanda bu folderde save edilmish .mo fayli yuklensin.
     * Amma biz textdomaini load eden kimi, loco onu unload edir ve neticede tenantin elediyi tercume hechbir halda ishe dushmur.
     * Workaround olarag bu alqoritmada eger tenantin tercume fayli varsa, textdomaini goturub deyishirik. "booknetic" evezine "TENANT_ID/booknetic" edirik;
     * "ID/booknetic" strukturunda olmasinin sebebi ise WordPress textdomainin dogrulugunu yoxlayir.
     * Bu formada yazilmadigda, meselen "booknetic-TENANT_ID" yazmag istesez textdomaini, WP lazimi folderdeki fayl movcud olmadigi uchun textdomaini umumiyyetle load etmeyecek.
     * Ona gore bu formati deyishdirmeye ceht etmeyin!
     *
     * @param $slug
     * @param $locale
     *
     * @return string
     */
    public static function getTextdomain( $slug = 'booknetic', $locale = null ) : string
    {
        if( ! isset( self::$textdomains[ $slug ] ) )
        {
            $locale = $locale ?: get_locale();
            $textDomain = $slug;

            if ( Helper::isSaaSVersion() && ! Permission::isSuperAdministrator() )
            {
                $tenantId = Permission::tenantId();

                if( $tenantId > 0 && file_exists( WP_PLUGIN_DIR.'/'.$slug.'/languages/'.$tenantId.'/'.$slug.'-'.$locale.'.mo' ) )
                {
                    $textDomain = $tenantId . '/' . $textDomain;
                }
            }

            self::$textdomains[ $slug ] = $textDomain;
        }

        return self::$textdomains[ $slug ];
    }

    public static function loadTextdomain ( $slug = 'booknetic' )
    {
        self::unloadTextdomain( $slug );

        $path = $slug . '/languages';
        $textDomain = LocalizationService::getTextdomain( $slug );
        load_plugin_textdomain( $textDomain, false, $path );

        /**
         * WP 6.7 versiyasi load_plugin_textdomain() funksiyasini deyishdi;
         * Artig 6.7 versiyasindan sonra o funksya direk load elemir textdomaini, sadece pathi elave edir liste;
         * Ashagidaki if-in icherisindeki kod kohne funksiyadan kopyalanib atilib ora.
         * Neticede l10n`de eger textdomain load olmayibsa, demek ki, yeni versiyadiki load olmayib, onda o kodlari run edib load edirik manual.
        */
        global $l10n;
        if( ! isset( $l10n[$textDomain] ) )
        {
            $locale = apply_filters( 'plugin_locale', determine_locale(), $textDomain );

            $mofile = $textDomain . '-' . $locale . '.mo';
            // Try to load from the languages directory first.
            if ( ! load_textdomain( $textDomain, WP_LANG_DIR . '/plugins/' . $mofile, $locale ) ) {
                $fullPath = WP_PLUGIN_DIR . '/' . trim( $path, '/' );
                load_textdomain( $textDomain, $fullPath . '/' . $mofile, $locale );
            }
        }
        /** 6.7 versiyadan sonralari uchun olan kod bitdi: endsection */
    }

    public static function unloadTextdomain ( $slug = 'booknetic' )
    {
        $textDomain = LocalizationService::getTextdomain( $slug );

        unload_textdomain( $textDomain );
        unset( self::$textdomains[$slug] );
    }

}