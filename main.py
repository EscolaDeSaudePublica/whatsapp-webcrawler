import sys
import pandas as pd
from analyzer import getData

parsedData = getData(sys.argv[1])

df = pd.DataFrame(parsedData, columns=['Date', 'Time', 'Author', 'Message'])
print(df.head())

df.to_csv('exemplo_conversa.csv')