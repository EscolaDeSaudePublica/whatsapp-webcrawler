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

/**
 * Verifica se a linha de texto é iniciada com a Data
 *
 * @param String $message
 *
 * @return bool
 */
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

/**
 * Verifica se a linha de texto é iniciada com um Autor
 *
 * @param  String $message
 *
 * @return bool
 */
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

/**
 * Verifica se há perguntas na mensagem.
 *
 * @param  string $message
 *
 * @return bool
 */
function hasQuestion($message){
    $pattern = "/^(\b(?:qual|quais|quando|onde|como|quem*)\b)|(\?)/i"; 
    preg_match($pattern, $message, $matches);
    if(count($matches) == 0) {
        return false;
    }
    return true;
}

/**
 * Divide a linha nos elementos solicitados para o CSV
 *
 * @param String $line
 *
 * @return array
 */
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
                $buffer = implode(' ', $messageBuffer); 
                if(hasQuestion($buffer)) {
                    $isQuestion = true;
                } 
                array_push($parsedData, [$date, $author, $isQuestion, $buffer]);                      
            }
            $messageBuffer = [];
            $isQuestion = false;
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
