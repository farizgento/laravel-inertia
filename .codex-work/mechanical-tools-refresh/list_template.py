import json
with open('workbooks.json',encoding='utf-8') as f:data=json.load(f)
rows=data['template'][0]['values']
for i,row in enumerate(rows, start=1):
    if 450 <= i <= 550:
        print(i, row[:6])
