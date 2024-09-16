<?php
function icsToArray($paramUrl){
    $icsFile = file_get_contents($paramUrl);
    echo "<pre>";
    $icsData = explode("BEGIN:", $icsFile);
    foreach($icsData as $key => $value){
        $icsDatesMeta[$key] = explode("\n", $value);
    }
    foreach($icsDatesMeta as $key => $value){
        foreach($value as $subKey => $subValue){
            if($subValue != ""){
                if($key != 0 && $subKey == 0){
                    $icsDates[$key]["BEGIN"] = trim($subValue);
                }else{
                    $subValueArr = explode(":", $subValue, 2);
                    $icsDates[$key][$subValueArr[0]] = trim($subValueArr[1]);
                    $subValueKey = explode(";", $subValue, 2);
                    $icsDates[$key][$subValueKey[0]] = trim($subValueArr[1]);
                }
            }
        }
    }
    print_r($icsDates);
    return $icsDates;
}
function dateToCal($time) { return date('Ymd\This', strtotime($time)) . 'Z'; }
$icspath = "https://www.airbnb.com/calendar/ical/17443256.ics?s=ceb268d8944375d50323bbc3fbd4172b";
$ics = icsToArray($icspath);
$listings_cal = array();
$i=0;
foreach($ics as $a){
    if($a['BEGIN'] == "VEVENT"){
        $a['DTSTART'] = date_format(date_create_from_format('Ymd', $a['DTSTART']), 'Y-m-d');
        $a['DTEND'] = date_format(date_create_from_format('Ymd', $a['DTEND']), 'Y-m-d');
        $listings_cal[$i]['start_date'] = date("Y-m-d", strtotime($a['DTSTART']));
        $listings_cal[$i]['end_date']  = date("Y-m-d", strtotime($a['DTEND']));
        $listings_cal[$i]['summary'] = $a['SUMMARY'];
        $i++;
    }
}

echo "<pre>";
print_r($listings_cal);
?>
