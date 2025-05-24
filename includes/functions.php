<?php
function isOutdated($last_updated, $interval = '1 day') {
    if (!$last_updated){
        return true;
    }
    return strtotime($last_updated) < strtotime("-$interval");
}
