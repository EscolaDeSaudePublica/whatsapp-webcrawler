import re
import datetime
import calendar

def startsWithDate(s):
    '''
        startsWithDate
        Verifica se a linha de texto é iniciada com a Data
    '''
    pattern = '^([0-2][0-9]|(3)[0-1])(\/)(((0)[0-9])|((1)[0-2]))(\/)(\d{2}|\d{4}) ([0-9][0-9]):([0-9][0-9]) -'
    result = re.match(pattern, s)
    if result:
        return True
    return False

def startsWithAuthor(s):
    '''
        startsWithAuthor
        Verifica se a linha de texto é iniciada com um Autor
    '''
    patterns = [
        "([\w]+[\s]+[\w]+[\s]+[\w]+):",          # First Name + Middle Name + Last Name
        "([\w]+[\s]+[\w]+):",                    # First Name + Last Name
        "([\w]+):",                              # First Name
        "([+]\d{2} \d{2} \d{4}-\d{4}):",         # Mobile Number (Brazil)
        "([+]\d{2} \d{2} \d{1} \d{4}-\d{4}):",   # Mobile Number (Brazil) w/ 9º digit
        "([+]\d{2} \d{2} \d{1} \d{8}):",         # Mobile Number (Brazil) w/ 9º digit without spacing
        "([+]\d{2} \d{2} \d{9}):",                 # Mobile Number (Brazil) w/ 9º digit without spacing in all numbers
        "([+]\d{2} \d{5} \d{5}):",               # Mobile Number (India)
        "([+]\d{2} \d{3} \d{3} \d{4}):",         # Mobile Number (US)
        "([+]\d{2} \d{4} \d{7})"                 # Mobile Number (Europe)
    ]
    pattern = '^' + '|'.join(patterns)
    result = re.match(pattern, s)
    if result:
        return True
    return False

def hasQuestion(s):
    '''
        hasQuestion
        Verifica se há perguntas na mensagem.
        
    '''
    pattern = "^(\b(qual|quais|quando|onde|como|quem*)\b)|(\?)"
    result = re.search(pattern, s) # Para word boundary, só funcionou utilizando o search
    if result:
        return True
    return False

def getDataPoint(line):
    '''
        getDataPoint
        Divide a linha nos elementos solicitados para o CSV
    '''
    splitLine = line.split(' - ')
    dateTime = convertDate(splitLine[0])
    date, time = splitLine[0].split(' ')
    hour = findHour(time)
    weekday = findWeekday(date)
    message = ' '.join(splitLine[1:])
    
    if startsWithAuthor(message):
        splitMessage = message.split(': ')
        author = splitMessage[0]
        message = ' '.join(splitMessage[1:])
    else:
        author = None
    return dateTime, hour, weekday, author, message

def getData(conversationPath):
    '''
        getData
    '''
    parsedData = []
    with open(conversationPath, encoding="utf-8") as fp:
        fp.readline()
        messageBuffer = []
        dateTime, hour, weekday, author = None, None, None, None
        
        while True:
            line = fp.readline() 
            if not line:
                break
            line = line.strip()
            if startsWithDate(line):
                if len(messageBuffer) > 0:
                    buffer = ' '.join(messageBuffer)
                    parsedData.append([dateTime, hour, weekday, author, hasQuestion(buffer), buffer])
                messageBuffer.clear()
                dateTime, hour, weekday, author, message = getDataPoint(line)
                messageBuffer.append(message)
            else:
                messageBuffer.append(line)
    return parsedData

def convertDate(datetime_str):
    return datetime.datetime.strptime(datetime_str, '%d/%m/%Y %H:%M').strftime('%Y-%m-%d %H:%M:%S')

def findWeekday(date):
    weekdayNumber = datetime.datetime.strptime(date, '%d/%m/%Y').weekday()
    return calendar.day_name[weekdayNumber]

def findHour(hour):
    return datetime.datetime.strptime(hour, '%H:%M').replace(minute=0).strftime('%H:%M')