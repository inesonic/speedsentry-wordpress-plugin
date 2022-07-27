<?php
/***********************************************************************************************************************
 * Inesonic SpeedSentry - Site Performance Monitoring For Wordpress
 *
 * Copyright 2021, Inesonic, LLC
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with this program.  If not, see
 * <https://www.gnu.org/licenses/>.
 ***********************************************************************************************************************
 * \file uninstall.php
 *
 * Uninstaller.
 */

/**
 * Small class that uninstalls this plug-in.  Coded as a class to help manage the use of the SPL autoloader.
 */
class InesonicUninstaller {
    /**
     * The namespace that we need to perform auto-loading for.
     */
    const PLUGIN_NAMESPACE = 'Inesonic\\SpeedSentry\\';

    /**
     * The plug-in include path.
     */
    const INCLUDE_PATH = __DIR__ . '/include/';
    
    /**
     * Options prefix.
     */
    const OPTIONS_PREFIX = 'inesonic_speedsentry';

    /**
     * Static method triggered to uninstall this plug-in.
     */
    public static function uninstall() {
        spl_autoload_register(array(self::class, 'autoloader'));
            
        $slug = dirname(plugin_basename(__FILE__));            
        $options = new Inesonic\SpeedSentry\Options(self::OPTIONS_PREFIX, $slug);
        $options->plugin_uninstalled();
    }

    /**
     * Constructor
     */
    public function __construct() {
    }

    /**
     * Autoloader callback.
     *
     * \param[in] class_name The name of this class.
     */
    static public function autoloader($class_name) {
        if (!class_exists($class_name) && str_starts_with($class_name, self::PLUGIN_NAMESPACE)) { 
            $class_basename = str_replace(self::PLUGIN_NAMESPACE, '', $class_name);
            $filepath = self::INCLUDE_PATH;
            $last_was_lower = false;
            for ($i=0 ; $i<strlen($class_basename) ; ++$i) {
                $c = $class_basename[$i];
                if (ctype_upper($c)) {
                    if ($last_was_lower) {
                        $filepath .= '-' . strtolower($c);
                        $last_was_lower = false;
                    } else {
                        $filepath .= strtolower($c);
                    }
                } else {
                    $filepath .= $c;
                    $last_was_lower = true;
                }
            }

            $filepath .= '.php';

            if (file_exists($filepath)) {
                include $filepath;
            }
        }
    }
};

if (defined('WP_UNINSTALL_PLUGIN')) {
    InesonicUninstaller::uninstall();
}
