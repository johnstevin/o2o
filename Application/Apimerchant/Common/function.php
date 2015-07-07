<?php

function _merchant_roleName ( $id ) {
    is_numeric($id) ? '' : E('请传递正确的类型');
    switch ( $id ) {
        case C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_COMMITINFO')      : return 1000;     break;
        case C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_SHOP_BOSS')       : return 1001;     break;
        case C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_SHOP_MANAGER')    : return 1002;     break;
        case C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_SHOP_STAFF')      : return 1003;     break;
        case C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_VEHICLE_MANAGER') : return 1004;     break;
        case C('AUTH_ROLE_ID.ROLE_ID_MERCHANT_VEHICLE_WORKER')  : return 1005;     break;
        case C('AUTH_ROLE_ID.ROLE_ID_MEMBER_CLIENT')            : return 1006;     break;
        default : return 9999;
    }
}