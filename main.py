import sys
import pandas as pd
from analyzer import getData

parsedData = getData(sys.argv[1])
df = pd.DataFrame(parsedData, columns=['DateTime','Time', 'WeekDay', 'Author', 'isQuestion', 'Message'])
print(df.head())

df.to_csv('exemplo_conversa.csv', index=False)