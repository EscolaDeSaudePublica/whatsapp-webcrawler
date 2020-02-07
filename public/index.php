<?php

$parsedData = [];
$path = './exemplo_conversa.txt';
$txt = fopen($path, "r");
$date = null;
$author = null;
$message = null;
if ($txt) {
    $parsedData = getTxtData($txt, $path);
    fclose($txt);
    $csv = fopen('./exemplo_conversa.csv', 'w');
    if(!flock($csv, LOCK_EX)) {
        error_log('Cannot get lock!');
    } else {
        fillcsv($csv, $parsedData);
        fclose($csv);
    }
} else {
    // error opening the file.
}

function startWithDate($message) {
    $matches = [];
    $pattern = "/^([0-2][0-9]|(3)[0-1])(\/)(((0)[0-9])|((1)[0-2]))(\/)(\d{2}|\d{4}) ([0-9][0-9]):([0-9][0-9]) -/";
    preg_match($pattern, $message, $matches);
    if(count($matches) == 0) {
        return false;
    } else {
        return true;
    }
    
}

function startWithAuthor($message) {
    $matches = [];
    $patterns = [
        '([\w]+):',              
        '([\w]+[\s]+[\w]+):',    
        '([\w]+[\s]+[\w]+[\s]+[\w]+):',
        '([+]\d{2} \d{5} \d{5}):',
        '([+]\d{2} \d{2} \d{4}-\d{4}):',
        '([+]\d{2} \d{2} \d{8}):',
        '([+]\d{2} \d{2} \d{1} \d{4}-\d{4}):',
        '([+]\d{2} \d{2} \d{9}):',  
        '([+]\d{2} \d{3} \d{3} \d{4}):',
        '([+]\d{2} \d{4} \d{7})'
    ];
    $pattern = implode('|', $patterns);
    $pattern = "/^".$pattern."/";
    $result = preg_match($pattern, $message, $matches);
    if(count($matches) == 0) {
        return false;
    }
    return true;

}

function hasQuestion($message){
    $pattern = "/^[^.?!]*?\?/";
    preg_match($pattern, $message, $matches);
    if(count($matches) == 0) {
        return false;
    }
    return true;
}

function getDataPoint($line){
    $isQuestion = false;
    $splitLine = explode(' - ', $line);
    $dateTime = $splitLine[0];
    $dateTimeArr = explode(' ', $dateTime);
    $date = $dateTimeArr[0];
    // $time = $dateTimeArr[1];
    $date = transformDate($date);
    // $dateTime = $date.' '.$time;
    
    $message = implode(': ', array_slice($splitLine, 1));    
    
    if(startWithAuthor($message)) {
        $splitMessage = explode(': ', $message);
        $author = $splitMessage[0];
        $message = implode(' ', array_slice($splitMessage, 1));  
        if(hasQuestion($message)) {
            $isQuestion = true;
        }

    } else {
        $author = null;
    }

    return [
        'date' => $date,
        'author' => $author,
        'message' => $message,
        'isQuestion' => $isQuestion
    ];
}

function transformDate($date) {
    return implode('-', array_reverse(explode('/', $date)));
}

function getTxtData($txt, $path){
    $messageBuffer = [];
    $parsedData = [];
    $date = null;
    $author = null;
    $message = null;
    $isQuestion = false;
    while(($line = fgets($txt, filesize($path))) !== false) {
        $line = trim($line);
        if(startWithDate($line)) {
            if(count($messageBuffer) > 0) {
                array_push($parsedData, [$date, $author, $isQuestion, implode(' ', $messageBuffer)]);        
            }
            $messageBuffer = [];
            $dataPoint = getDataPoint($line);
            $date = $dataPoint['date'];
            $author = $dataPoint['author'];
            $message = $dataPoint['message'];
            $isQuestion = $dataPoint['isQuestion'];
            array_push($messageBuffer, $message);
        } else {
            array_push($messageBuffer, $line);
        }
    };
    return $parsedData;
}

function fillcsv($csv, $parsedData){
    fputcsv($csv, ['date', 'author', 'isQuestion', 'message']);
    foreach($parsedData as $row) {
        fputcsv($csv, $row);
    }
}
