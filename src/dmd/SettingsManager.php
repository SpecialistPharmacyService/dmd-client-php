<?php

namespace makeandship\dmd;

require_once dirname(__FILE__) . '/Constants.php';
require_once dirname(__FILE__) . '/Util.php';

use makeandship\dmd\Constants;
use makeandship\dmd\Util;
use \Elastica\Client;

class SettingsManager
{
    protected static $instance = null;

    // can't be instantiated externally
    protected function __construct()
    {
    }
    protected function __clone()
    {
    } // no clone

    public static function get_instance()
    {
        if (SettingsManager::$instance === null) {
            SettingsManager::$instance = new SettingsManager();
            SettingsManager::$instance->initialize();
        }
        return SettingsManager::$instance;
    }

    protected function initialize()
    {
        $this->get_settings(true);
    }

    /**
     * Get the current configuration.  Configuration values
     * are cached.  Use the $fresh parameter to get an updated
     * set
     *
     * @param $fresh - true to get updated values
     * @return array of settings
     */
    public function get_settings($fresh = false)
    {
        if (!isset($this->settings) || $fresh) {
            $this->settings = array();

            $scheme   = $this->get_setting(Constants::ENV_DMD_ES_SCHEME);
            $host     = $this->get_setting(Constants::ENV_DMD_ES_HOST);
            $port     = $this->get_setting(Constants::ENV_DMD_ES_PORT);
            $index    = $this->get_setting(Constants::ENV_DMD_ES_INDEX);
            $username = $this->get_setting(Constants::ENV_DMD_ES_USERNAME);
            $password = $this->get_setting(Constants::ENV_DMD_ES_PASSWORD);

            $this->settings[Constants::OPTION_SERVER]                = $scheme . '://' . $host . ':' . $port . '/';
            $this->settings[Constants::OPTION_INDEX_NAME]            = $index;
            $this->settings[Constants::OPTION_READ_TIMEOUT]          = Constants::DEFAULT_READ_TIMEOUT;
            $this->settings[Constants::OPTION_USERNAME]              = $username;
            $this->settings[Constants::OPTION_PASSWORD]              = $password;
            $this->settings[Constants::OPTION_ELASTICSEARCH_VERSION] = $this->get_elasticseach_version();
        }

        return $this->settings;
    }

    public function get_setting($name)
    {
        // constant
        if ($name) {
            if (defined($name)) {
                $value = constant($name);
                if ($value) {
                    return $value;
                }
            }
        }

        // environment variable
        $value = getenv($name);
        if ($value) {
            return $value;
        }

        // get the value from a file that the envronment variable points to
        $filename_env = $name . '_FILE';
        $filename     = getenv($filename_env);
        if (file_exists($filename)) {
            $value = file_get_contents($filename);
            if ($value) {
                return $value;
            }
        }

        return null;
    }

    public function get($name)
    {
        $settings = $this->get_settings();

        return Util::safely_get_attribute($settings, $name);
    }

    public function set($name, $value)
    {
        if ($this->valid_setting($name)) {
            $this->set_option($name, $value);

            if ($this->settings) {
                $this->settings[$name] = $value;
            }
        }
    }

    private function valid_setting($name)
    {
        if ($name) {
            if (in_array($name, [
                Constants::OPTION_SERVER,
                Constants::OPTION_INDEX_NAME,
                Constants::OPTION_READ_TIMEOUT,
                Constants::OPTION_USERNAME,
                Constants::OPTION_PASSWORD,
                Constants::OPTION_ELASTICSEARCH_VERSION,
            ])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return settings to connect to the configured elasticsearch instance
     *
     * @return array with options set
     */
    public function get_client_settings()
    {
        $settings = array();

        $settings[Constants::SETTING_URL] = $this->get(Constants::OPTION_SERVER);

        $username = $this->get(Constants::OPTION_USERNAME);
        if ($username) {
            $settings[Constants::SETTING_USERNAME] = $username;
        }

        $password = $this->get(Constants::OPTION_PASSWORD);
        if ($password) {
            $settings[Constants::SETTING_PASSWORD] = $password;
        }

        return $settings;
    }

    /**
     * Set an option for a given key.  If this is a
     * network installation, sets a network site option otherwise
     * sets a local site option
     *
     * @param $key the name of the option
     * @param $value to store
     */
    public function set_option($key, $value)
    {
        if (is_multisite()) {
            return update_site_option($key, $value);
        } else {
            return update_option($key, $value);
        }
    }

    private function get_elasticseach_version()
    {
        $client_settings = $this->get_client_settings();
        $client          = new Client($client_settings);
        return $client->getVersion();
    }
}