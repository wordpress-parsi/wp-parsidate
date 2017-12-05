<?php
add_filter( 'wp_insert_post_data', 'wpp_wc_save_post', '9999', 2 );

function wpp_wc_save_post($data ,$postarr){

    if($data['post_type']=='shop_order')
        $_POST['order_date'] = gregdate('Y-m-d',$_POST['order_date']);
    if($data['post_type']=='product'){
        $_POST['_sale_price_dates_from'] = gregdate('Y-m-d',$_POST['_sale_price_dates_from']);
        $_POST['_sale_price_dates_to']   = gregdate('Y-m-d',$_POST['_sale_price_dates_to']);
    }
    return $data;
}

function getqtlangcustomfieldvalue($metadata, $object_id, $meta_key, $single) {

    if($meta_key=='_sale_price_dates_from'){
        var_dump($_POST);
        var_dump(func_get_args());//die;
    }
    if($meta_key=='_sale_price_dates_to'){
        var_dump($_POST);
        var_dump(func_get_args());//die;
    }

}
add_filter('update_post_metadata', 'getqtlangcustomfieldvalue', 10, 4);
?>