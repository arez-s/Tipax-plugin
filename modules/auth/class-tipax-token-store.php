<?php
if (!defined('ABSPATH')) {
    exit;
}

class Tipax_Token_Store
{
    public function get()
    {
        return get_option(Tipax_Settings::ACCESS_TOKEN_OPTION, null);
    }

    public function set($accessToken)
    {
        if (get_option(Tipax_Settings::ACCESS_TOKEN_OPTION) !== false) {
            update_option(Tipax_Settings::ACCESS_TOKEN_OPTION, $accessToken, 'yes');
        } else {
            add_option(Tipax_Settings::ACCESS_TOKEN_OPTION, $accessToken, '', 'yes');
        }

        return $accessToken;
    }

    public function clear()
    {
        delete_option(Tipax_Settings::ACCESS_TOKEN_OPTION);
    }
}
