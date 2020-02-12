import re

def startsWithDate(s):
    pattern = '^([0-2][0-9]|(3)[0-1])(\/)(((0)[0-9])|((1)[0-2]))(\/)(\d{2}|\d{4}) ([0-9][0-9]):([0-9][0-9]) -'
    result = re.match(pattern, s)
    if result:
        return True
    return False

def startsWithAuthor(s):
    patterns = [
        '([\w]+):',                        # First Name
        '([\w]+[\s]+[\w]+):',              # First Name + Last Name
        '([\w]+[\s]+[\w]+[\s]+[\w]+):',    # First Name + Middle Name + Last Name
        '([+]\d{2} \d{5} \d{5}):',         # Mobile Number (India)
        '([+]\d{2} \d{3} \d{3} \d{4}):',   # Mobile Number (US)
        '([+]\d{2} \d{4} \d{7})'           # Mobile Number (Europe)
    ]
    pattern = '^' + '|'.join(patterns)
    result = re.match(pattern, s)
    if result:
        return True
    return False

def hasQuestion(s):
    pattern = "/^(\b(?:qual|quais|quando|onde|como|quem*)\b)|(\?)/i"
    result = re.match(pattern, s)
    if result:
        return True
    return False

def getDataPoint(line):
    isQuestion = False
    splitLine = line.split(' - ')
    dateTime = splitLine[0]   
    date, time = dateTime.split(' ')
    message = ' '.join(splitLine[1:])
    
    if startsWithAuthor(message):
        splitMessage = message.split(': ')
        author = splitMessage[0]
        message = ' '.join(splitMessage[1:])
        isQuestion = hasQuestion(message)
    else:
        author = None
    return date, time, author, isQuestion, message
def getData(conversationPath):
    parsedData = []
    with open(conversationPath, encoding="utf-8") as fp:
        fp.readline()
        messageBuffer = []
        date, time, author, isQuestion = None, None, None, False
        
        while True:
            line = fp.readline() 
            if not line:
                break
            line = line.strip()
            if startsWithDate(line):
                if len(messageBuffer) > 0:
                    parsedData.append([date, time, author, ' '.join(messageBuffer)])
                messageBuffer.clear()
                date, time, author, message, isQuestion = getDataPoint(line)
                messageBuffer.append(message)
            else:
                messageBuffer.append(line)
    return parsedData
