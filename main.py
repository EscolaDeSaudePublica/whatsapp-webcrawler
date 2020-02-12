import sys
import pandas as pd
from analyzer import getData

parsedData = getData(sys.argv[1])
print(parsedData)
df = pd.DataFrame(parsedData, columns=['DateTime', 'Author', 'isQuestion', 'Message'], index=False)
print(df.head())

df.to_csv('exemplo_conversa.csv')