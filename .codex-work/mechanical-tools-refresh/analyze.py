import json, re, unicodedata
from collections import Counter, defaultdict
from difflib import SequenceMatcher

with open('workbooks.json', encoding='utf-8') as f:
    data = json.load(f)

def text(v):
    return '' if v is None else str(v).strip()

def norm(s):
    s = unicodedata.normalize('NFKC', text(s)).lower()
    s = s.replace('″', '"').replace('“', '"').replace('”', '"').replace('′', "'")
    s = s.replace('Ω', ' omega ').replace('ω', ' omega ')
    replacements = {
        'chainblock': 'chain block', 'eyebolt': 'eye bolt', 'eye bolt': 'eyebolt',
        'shekle': 'shackle', 'shacle': 'shackle', 'rachet': 'ratchet',
        'ring pass': 'ring pas', 'kunci pass': 'kunci pas', 'bor batre': 'bor baterai',
        'boregauge': 'bore gauge', 'zigmat': 'sigmat', 'vernier caliper': 'sigmat',
        'hidrolik': 'hydraulic', 'jack hydraulic': 'hydraulic jack',
        'min': 'minus', 'obeng +': 'obeng plus', 'obeng -': 'obeng minus',
        'stang magnet dial': 'magnetic stand', 'dial pecock': 'dial peacock',
    }
    for a,b in replacements.items():
        s = s.replace(a,b)
    s = re.sub(r'(?<=\d),(?=\d)', '.', s)
    s = re.sub(r'(?<=\d)\s*[x×]\s*(?=\d)', ' x ', s)
    s = re.sub(r'(?<=\d)\s*(?:ton|tons)\b', ' t', s)
    s = re.sub(r'(?<=\d)\s*t\b', ' t', s)
    s = re.sub(r'\bpcs?\b|\bunits?\b', ' ', s)
    s = re.sub(r'\bmm\b', ' mm ', s)
    s = re.sub(r'\bcm\b', ' cm ', s)
    s = re.sub(r'\bm\b', ' m ', s)
    s = re.sub(r'[^a-z0-9./"\']+', ' ', s)
    return ' '.join(s.split())

template_sheet = data['template'][0]
template_rows = template_sheet['values']
template = []
for excel_row, row in enumerate(template_rows[1:], start=2):
    row = row + [None] * (6-len(row))
    template.append({'row': excel_row, 'code': row[0], 'name': row[1], 'type': row[2], 'class': row[3], 'total': row[4], 'old_qty': row[5], 'norm': norm(row[1])})

sources=[]
for sh in data['source']:
    vals=sh['values']
    header_i=name_col=qty_col=None
    for i,row in enumerate(vals):
        lowered=[text(x).lower() for x in row]
        for j,v in enumerate(lowered):
            if 'nama peralatan' in v:
                header_i=i; name_col=j
                for k,h in enumerate(lowered):
                    if h in ('jumlah','qty.','qty','quantity'):
                        qty_col=k; break
                break
        if header_i is not None: break
    if header_i is None:
        print('NO HEADER',sh['name']); continue
    for i,row in enumerate(vals[header_i+1:], start=header_i+2):
        if name_col >= len(row) or qty_col is None or qty_col >= len(row): continue
        name=row[name_col]; qty=row[qty_col]
        if not isinstance(name,str) or not name.strip(): continue
        if isinstance(qty,bool) or not isinstance(qty,(int,float)): continue
        sources.append({'sheet':sh['name'],'row':i,'name':name.strip(),'qty':qty,'norm':norm(name)})

by_norm=defaultdict(list)
for t in template: by_norm[t['norm']].append(t)

def score(a,b):
    return SequenceMatcher(None,a,b).ratio()*100

print('template rows',len(template),'source rows numeric',len(sources),'source total',sum(s['qty'] for s in sources))
print('source sheets',Counter(s['sheet'] for s in sources))
print('source unique normalized',len(set(s['norm'] for s in sources)))
print('template normalized duplicates',sum(1 for v in by_norm.values() if len(v)>1))
for k,v in by_norm.items():
    if len(v)>1:
        print('DUP TEMPLATE NORM',k,[(x['row'],x['name']) for x in v])
print('\nEXACT NORMALIZED',sum(1 for s in sources if len(by_norm[s['norm']])==1))
print('\nUNMATCHED CANDIDATES')
for s in sources:
    if len(by_norm[s['norm']])==1: continue
    ranked=sorted(((score(s['norm'],t['norm']),t) for t in template), key=lambda x:x[0], reverse=True)[:7]
    print(f"{s['sheet']}!{s['row']} | {s['name']} | {s['qty']} | norm={s['norm']}")
    print('  ' + ' || '.join(f"{sc:.1f}:{t['row']}:{t['name']}" for sc,t in ranked))
