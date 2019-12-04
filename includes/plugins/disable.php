<?php
global $wpp_settings, $dis_hook;
if (isset($wpp_settings['dis_input'])) {

    $dis_hook = array();
    $lists = explode("\n", $wpp_settings['dis_input']);
    foreach ($lists as $list) {
        $list = explode(',', $list);
        if (count($list) < 2)
            continue;
        $dis_hook[$list[0]][] = array('func' => $list[1], 'class' => (isset($list[2]) ? $list[2] : ''));
    }
}

function disable_wpp()
{

    global $dis_hook;
    if (wpp_is_feed())
        return false;
    $calls = debug_backtrace();
    unset($calls[0]);
    unset($calls[1]);
    unset($calls[2]);

    foreach ($calls as $i => $call) {
        unset($calls[$i]);
        if ($call['function'] == 'apply_filters' and empty($call['class']))
            break;
    }
    $func = $calls[++$i]['function'];
    
    if (empty($dis_hook[$func]))
        return true;
    
    $hooks = $dis_hook[$func];
    if (empty($hooks))
        return true;

    unset($calls[$i]);

    foreach ($calls as $i => $call) {
        foreach ($hooks as $hook) {
            $hook['class'] = trim($hook['class']);
            if ((isset($call['class']) and empty($hook['class'])) or (!isset($call['class']) and !empty($hook['class'])))
                continue;
            if (!empty($hook['func']) and ($call['function'] != trim($hook['func'])))
                continue;
            if ((!isset($call['class']) and empty($hook['class'])) or $call['class'] == $hook['class'])
                return false;
        }
    }
    return true;
}


function wpp_woocommerce_admin_report_data($report_data)
{

    $report_data['where'] = preg_replace_callback("/posts.post_date\s.=?\s'([^']+)'/i", 'fix_date_woo_report', $report_data['where']);
    return $report_data;
}

;

function fix_date_woo_report($date)
{

    if (empty($_GET['start_date']) or empty($_GET['end_date']))
        return $date[0];

    if (strpos($date[0], '=') === false) {

        if ((int)$_GET['end_date'] > 1900)
            return $date[0];
        $dt = gregdate('Y-m-d', $_GET['end_date']);
        $dt = date('Y-m-d', strtotime("$dt +1 day"));
    } else {

        if ((int)$_GET['start_date'] > 1900)
            return $date[0];
        $dt = gregdate('Y-m-d', $_GET['start_date']);
    }
    return substr_replace($date[0], $dt, -20, 10);
}

// add the filter
add_filter('woocommerce_reports_get_order_report_query', 'wpp_woocommerce_admin_report_data', 10, 1);


/**
 * Makes EDD compatible with WP-Parsidate plugin
 *
 * @package                 WP-Parsidate
 * @subpackage              Plugins/EDD
 * @author                  Ehsaan
 */
class WPP_Disable
{
    public static $instance = null;

    /**
     * Hooks required tags
     */
    private function __construct()
    {
        global $wpp_settings;
        add_filter('wpp_plugins_compability_settings', array($this, 'add_settings'));

        if (isset($wpp_settings['dis_prices']) && $wpp_settings['dis_prices'] != 'disable') {
            add_filter('dis_rial_currency_filter_after', 'per_number', 10, 2);
        }

        if (isset($wpp_settings['dis_rial_fix']) && $wpp_settings['dis_rial_fix'] != 'disable') {
            add_filter('dis_rial_currency_filter_after', array($this, 'rial_fix'), 10, 2);
        }
    }

    /**
     * Returns an instance of class
     *
     * @return          WPP_Disable
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new WPP_Disable();
        }

        return self::$instance;
    }

    /**
     * RIAL fix for EDD
     */
    public function rial_fix($price, $did)
    {
        return str_replace('RIAL', 'ریال', $price);
    }

    /**
     * Adds settings for toggle fixing
     *
     * @param array $old_settings Old settings
     *
     * @return          array New settings
     */
    public function add_settings($old_settings)
    {
        $options = array(
            'enable' => __('Enable', 'wp-parsidate'),
            'disable' => __('Disable', 'wp-parsidate')
        );
        $settings = array(
            'dis' => array(
                'id' => 'dis',
                'name' => __('Hook deactivator', 'wp-parsidate'),
                'type' => 'header'
            ),
            'dis_prices' => array(
                'id' => 'dis_input',
                'name' => __('Hook list', 'wp-parsidate'),
                'type' => 'textarea',
                'options' => $options,
                'std' => '',
                'desc' => __('Enter hook,class,function to remove parsidate filter from it', 'wp-parsidate')
            )
        );

        return array_merge($old_settings, $settings);
    }
}

return WPP_Disable::getInstance();
