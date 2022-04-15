<?php

namespace makeandship\dmd;

class Constants
{

    // index
    const DEFAULT_SHARDS   = 1;
    const DEFAULT_REPLICAS = 1;

    // elastica
    const SETTING_URL          = 'url';
    const SETTING_TIMEOUT      = 'timeout';
    const SETTING_USERNAME     = 'username';
    const SETTING_PASSWORD     = 'password';
    const DEFAULT_READ_TIMEOUT = 30;

    // plugin
    const VERSION = '1.0.4';

    const OPTION_SERVER                = 'DMD_CLUSTER';
    const OPTION_INDEX_NAME            = 'DMD_INDEX_NAME';
    const OPTION_USERNAME              = 'DMD_USERNAME';
    const OPTION_PASSWORD              = 'DMD_PASSWORD';
    const OPTION_ELASTICSEARCH_VERSION = 'DMD_ES_VERSION';
    const OPTION_READ_TIMEOUT          = 'OPTION_READ_TIMEOUT';

    const ENV_DMD_ES_SCHEME   = "DMD_ES_SCHEME";
    const ENV_DMD_ES_HOST     = "DMD_ES_HOST";
    const ENV_DMD_ES_PORT     = "DMD_ES_PORT";
    const ENV_DMD_ES_INDEX    = "DMD_ES_INDEX";
    const ENV_DMD_ES_USERNAME = "DMD_ES_USERNAME";
    const ENV_DMD_ES_PASSWORD = "DMD_ES_PASSWORD";

    const INDEX_NAME = 'dmd-data';

    // no instantiation
    protected function __construct()
    {
    }
}
