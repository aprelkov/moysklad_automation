<?php

    /* GET TIME SPAN – LAST 10 MINUTES, MSK */
    date_default_timezone_set("Europe/Moscow");
    $updatedTo = date("Y-m-d+H:i:s");
    $updatedFrom = date("Y-m-d+H:i:s", time()-900);
    $time_span = '?updatedFrom='.$updatedFrom.'&updatedTo='.$updatedTo;

    /* LOGIN */
    $auth = 'login@subdomain:password';

    /* MOYSKLAD GET ARRAY */
    function moysklad_get() {
        global $auth, $request, $time_span, $out;

        $link='https://online.moysklad.ru/api/remap/1.1/entity/'.$request.$time_span;
        $curl=curl_init(); 
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl,CURLOPT_URL,$link);
        curl_setopt($curl,CURLOPT_HEADER,false);
        curl_setopt($curl,CURLOPT_USERPWD,$auth);
        curl_setopt($curl,CURLOPT_HEADER,false);
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
        $out=curl_exec($curl);
        curl_close($curl);
        $out=json_decode($out,true); 
    }

    /* MOYSKLAD POST */
    function moysklad_post() {
        global $auth, $request, $post_array;

        $link='https://online.moysklad.ru/api/remap/1.0/entity/'.$request; 
        $curl=curl_init(); 
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl,CURLOPT_URL,$link);
        curl_setopt($curl,CURLOPT_USERPWD,$auth);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
        curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($post_array));
        curl_setopt($curl,CURLOPT_HEADER,false);
        curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type:application/json'));
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
        $out=curl_exec($curl);
        curl_close($curl);
        $out=json_decode($out,true);
    }

    /* EDIT CUSTOMER ORDER */
    $request = 'customerOrder';
    moysklad_get();
    $rows = $out['rows'];
    foreach($rows as $key => $value_rows) {
        $orderId = $value_rows['id'];
        $request = 'customerOrder/'.$orderId;
        
        $attr = $value_rows['attributes'];
        foreach($attr as $key => $value_attr) {
            $id = $value_attr['id'];

            /* IF PRE-ORDER – EDIT STATUS */
            if ($id == '96c40420-384d-11e6-7a69-971100041d61') {
                $value_state = $value_attr['value'];
                if ($value_state == '1') {
                    global $post_array;
                    $post_array['state'] = array('name'=>'Pre-order', 'stateType'=>'Regular', 'entityType'=>'customerorder');
                };
            };

            /* PREPARE ARRAY */
            $post_array['attributes'][$key]['attributeId'] = $value_attr['id'];
            $post_array['attributes'][$key]['name'] = $value_attr['name'];
            $post_array['attributes'][$key]['type'] = $value_attr['type'];
            $post_array['attributes'][$key]['value'] = $value_attr['value'];

        };

        /* EDIT DEBT */
        $payedSum = $value_rows['payedSum'];
        $sum = $value_rows['sum'];
        $debt = ($sum - $payedSum)/100;
        $debt = round($debt); 
        $debt_array = array('attributeId'=>'443411a5-4435-11e6-7a69-8f55000b298f', 'name'=>'Debt', 'type'=>'double', 'value'=>$debt); 
        array_unshift($post_array['attributes'], $debt_array); 

        /* IF PRE-PAYD – EDIT STATUS */
        if ($payedSum > 1) {
            $post_array['state'] = array('name'=>'Pre-paid', 'stateType'=>'Regular', 'entityType'=>'customerorder');
        };

        /* IF SHIPPED – EDIT STATUS */
        $shippedSum = $value_rows['shippedSum'];
        if ($shippedSum > 1) {
            $post_array['state'] = array('name'=>'Shipped', 'stateType'=>'Regular', 'entityType'=>'customerorder');
        };

        /* MOYSKLAD POST CHANGES */
        moysklad_post();
        sleep(5);

    };

?>
