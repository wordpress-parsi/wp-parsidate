<?php

/**
 * Create Persian Archives
 *
 * @param string $args
 *
 * @return          string
 */
function wpp_get_archives($args = '')
{
    global $wpdb, $persian_month_names;
    $defaults = array(
        'type' => 'monthly',
        'limit' => '',
        'format' => 'html',
        'before' => '',
        'after' => '',
        'show_post_count' => false,
        'echo' => 1,
        'order' => 'DESC',
        'post_type' => 'post'
    );
    $r = wp_parse_args($args, $defaults);

    $post_type_object = get_post_type_object($r['post_type']);
    if (!is_post_type_viewable($post_type_object)) {
        return;
    }
    $r['post_type'] = $post_type_object->name;

    $results = $wpdb->get_results("SELECT date( post_date )as date,count(ID)as count FROM $wpdb->posts WHERE post_date < NOW() AND post_type = '{$r['post_type']}' AND post_status = 'publish' group by date ORDER BY post_date DESC");

    if (!empty($results))
        wpp_print_archive($results, $r);
}

function echo_yarchive($year, $format, $before, $count, $show_post_count, $r)
{
    if ($show_post_count) {
        $count = '&nbsp;(' . fixnumber($count) . ')';
    } else {
        $count = '';
    }
    $url = get_year_link($year);
    if ('post' !== $r['post_type'])
        $url = add_query_arg('post_type', $r['post_type'], $url);
    echo get_archives_link($url, fixnumber($year), $format, $before, $count);
}

function echo_marchive($old_date, $format, $before, $count, $show_post_count, $r)
{
    global $persian_month_names;
    $year = substr($old_date, 0, 4);
    $month = substr($old_date, 4, 2);
    if ($show_post_count) {
        $count = '&nbsp;(' . fixnumber($count) . ')';
    } else {
        $count = '';
    }
    $url = get_month_link($year, $month);
    if ('post' !== $r['post_type'])
        $url = add_query_arg('post_type', $r['post_type'], $url);

    echo get_archives_link($url, $persian_month_names[intval($month)] . ' ' . fixnumber($year), $format, $before, $count);
}

function wp_get_parchives($args = '')
{
    global $wpdb, $persian_month_names;
    $defaults = array(
        'type' => 'monthly',
        'limit' => '',
        'format' => 'html',
        'before' => '',
        'after' => '',
        'show_post_count' => false,
        'echo' => 1,
        'order' => 'DESC'
    );

    $r = wp_parse_args($args, $defaults);

    $results = $wpdb->get_results("SELECT date( post_date )as date,count(ID)as count FROM $wpdb->posts WHERE post_date < NOW() AND post_type = 'post' AND post_status = 'publish' group by date ORDER BY post_date DESC");

    if (!empty($results))
        wpp_print_archive($results, $r);
}

function wpp_print_archive($results, $args)
{
    global $persian_month_names;
    if ($args['type'] == 'yearly') {
        $old_date = parsidate('Y', $results[0]->date, 'eng');
        $count = $results[0]->count;
        $c = count($results);
        for ($i = 1; $i < $c; $i++) {
            $dt = $results[$i];
            $date = parsidate('Y', $dt->date, 'eng');
            if ($date === $old_date) {
                $count += $dt->count;
            } else {
                echo_yarchive($old_date, $args['format'], $args['before'], $count, $args['show_post_count'], $args);
                $old_date = $date;
                $count = $dt->count;
            }
        }
        echo_yarchive($old_date, $args['format'], $args['before'], $count, $args['show_post_count'], $args);
    } elseif ($args['type'] == 'monthly') {
        $old_date = parsidate('Ym', $results[0]->date, 'eng');
        $count = $results[0]->count;
        $c = count($results);
        for ($i = 1; $i < $c; $i++) {
            $dt = $results[$i];
            $date = parsidate('Ym', $dt->date, 'eng');
            if ($date === $old_date) {
                $count += $dt->count;
            } else {
                echo_marchive($old_date, $args['format'], $args['before'], $count, $args['show_post_count'], $args);
                $old_date = $date;
                $count = $dt->count;
            }
        }
        echo_marchive($old_date, $args['format'], $args['before'], $count, $args['show_post_count'], $args);
    } elseif ($args['type'] == 'daily') {
        foreach ($results as $row) {
            $date = parsidate('Y,m,d', $row->date, 'eng');
            $date = explode(',', $date);
            if ($args['show_post_count']) {
                $count = '&nbsp;(' . fixnumber($row->count) . ')';
            } else {
                $count = '';
            }
            $text = fixnumber($date[2]) . ' ' . $persian_month_names[intval($date[1])] . ' ' . fixnumber($date[0]);
            echo get_archives_link(get_day_link($date[0], $date[1], $date[2]), $text, $args['format'], $args['before'], $count);
        }
    }
}
