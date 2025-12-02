<?php if (!defined('ABSPATH')) exit;
add_filter('fasp_platform_bulk_columns', function($cols){
    return [
        '__slug'        => ['label'=>'Platform','type'=>'text'],
        'method'        => ['label'=>'Method','type'=>'select','options'=>['none'=>'None','oauth'=>'OAuth','link'=>'Direct Link']],
        'app_id'        => ['label'=>'App ID','type'=>'text'],
        'client_secret' => ['label'=>'Client Secret','type'=>'text'],
        'webhook_url'   => ['label'=>'Webhook URL','type'=>'url'],
        'webhook_auth'  => ['label'=>'Webhook Auth','type'=>'text'],
    ];
});