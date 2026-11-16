<?php
if (!defined('ABSPATH')) {
    exit;
}

class Tipax_Package_Builder
{
    public function apply_default_pack_meta($package)
    {
        return tipax_apply_default_pack_meta($package);
    }
}
