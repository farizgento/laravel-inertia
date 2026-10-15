import json, re, unicodedata
from collections import defaultdict
from difflib import SequenceMatcher

with open('workbooks.json', encoding='utf-8') as f:
    data = json.load(f)

def txt(v):
    return '' if v is None else str(v).strip()

def norm(s):
    s = unicodedata.normalize('NFKC', txt(s)).lower()
    s = s.replace('″', '"').replace('“', '"').replace('”', '"').replace('′', "'")
    s = re.sub(r'\bout\s+side\b', 'outside', s)
    s = re.sub(r'\bchainblock\b', 'chain block', s)
    s = re.sub(r'\beye\s*bolt\b', 'eyebolt', s)
    s = re.sub(r'\bshekle\b|\bshacle\b', 'shackle', s)
    s = re.sub(r'\brachet\b', 'ratchet', s)
    s = re.sub(r'\bring\s+pass\b', 'ring pas', s)
    s = re.sub(r'\bkunci\s+pass\b', 'kunci pas', s)
    s = re.sub(r'\bbor\s+batre\b', 'bor baterai', s)
    s = re.sub(r'\bboregauge\b', 'bore gauge', s)
    s = re.sub(r'\bzigmat\b', 'sigmat', s)
    s = re.sub(r'\bhidrolik\b', 'hydraulic', s)
    s = re.sub(r'\bjek\b', 'jack', s)
    s = re.sub(r'\bgerindra\b|\bgrindra\b', 'gerinda', s)
    s = re.sub(r'\btevlon\b', 'teflon', s)
    s = re.sub(r'\bsnapring\b', 'snap ring', s)
    s = re.sub(r'\bimpack\b', 'impact', s)
    s = re.sub(r'\bgetok\b', 'ketok', s)
    s = re.sub(r'\btomi\b', 'tommy', s)
    s = re.sub(r'\bshock\b', 'sok', s)
    s = re.sub(r'\bselang\s+udara\b', 'selang angin', s)
    s = re.sub(r'\bmin\b', 'minus', s)
    s = s.replace('obeng +', 'obeng plus').replace('obeng -', 'obeng minus')
    s = s.replace('Ω', ' omega ').replace('ω', ' omega ')
    s = re.sub(r'\binch\b', '"', s)
    s = re.sub(r'(?<=\d),(?=\d)', '.', s)
    s = re.sub(r'\blbs\b', 'lb', s)
    s = re.sub(r'(?<=\d)\s*(kg|mm|cm|m|lb)\b', r' \1', s)
    s = re.sub(r'(?<=\d)\s*[x×]\s*(?=\d)', ' x ', s)
    s = re.sub(r'(?<=\d)\s*(?:t|tons?)\b', ' ton', s)
    s = re.sub(r'\bpcs?\b|\bunits?\b', ' ', s)
    s = re.sub(r'[^a-z0-9./"\']+', ' ', s)
    return ' '.join(s.split())

rows = data['template'][0]['values']
template=[]
for excel_row,row in enumerate(rows[1:],start=2):
    row=(row+[None]*6)[:6]
    template.append({'row':excel_row,'code':row[0],'name':row[1],'type':row[2],'class':row[3],'total':row[4],'old_qty':row[5],'norm':norm(row[1])})

raw_index=defaultdict(list); norm_index=defaultdict(list); name_index={}
for t in template:
    raw_index[txt(t['name']).casefold()].append(t)
    norm_index[t['norm']].append(t)
    name_index[t['name']]=t

sources=[]
for sh in data['source']:
    vals=sh['values']; header=None; name_col=None; qty_col=None
    for i,row in enumerate(vals):
        lowers=[txt(x).lower() for x in row]
        if any('nama peralatan' in x for x in lowers):
            header=i
            name_col=next(j for j,x in enumerate(lowers) if 'nama peralatan' in x)
            qty_col=next((j for j,x in enumerate(lowers) if x in ('jumlah','qty.','qty','quantity')),None)
            break
    if header is None or qty_col is None:
        raise RuntimeError(f'Header tidak ditemukan: {sh["name"]}')
    for excel_row,row in enumerate(vals[header+1:],start=header+2):
        if name_col>=len(row) or qty_col>=len(row): continue
        name=row[name_col]; qty=row[qty_col]
        if not isinstance(name,str) or not name.strip(): continue
        if isinstance(qty,bool) or not isinstance(qty,(int,float)): continue
        sources.append({'sheet':sh['name'],'row':excel_row,'name':name.strip(),'qty':qty,'norm':norm(name)})

manual_names = {
    'Shackle Ω 8-1/2 T': 'Shackle Omega 8.5 ton',
    'Shackle U 3-1/4 T': 'Shackle U 3.25 ton',
    'webbing sling 2t 2m': 'Sling Belt 2 ton x 2 m',
    'webbing sling 3t 2m': 'Sling Belt 3 ton x 2 m',
    'webbing sling 1t 2m': 'Sling Belt 1 ton x 2 m',
    'webbing sling 1t 1m': 'Sling Belt 1 ton x 1 m',
    'webbing sling 2t 1,5m': 'Sling Belt 2 ton x 1.5 m',
    'Sling Wire 3/4" - 1 m': 'Sling Wire 3/4" - 1 m',
    'Sling Wire 3/4" 1M': 'Sling Wire 3/4" 1 m',
    'Sling Wire 1/2" 50CM': 'Sling Wire 1/2" 50 cm',
    'Sling Wire 1/2" x 50CM': 'Sling Wire 1/2" x 50 cm',
    'outside 0-25': 'Outside Micrometer 0-25',
    'Out Side Micrometer 0-25 MM': 'Outside Micrometer 0-25 mm',
    'Kunci Ring Pas 1 inch': 'Kunci Ring Pas 1"',
    'Kunci Shock': 'Kunci Sok',
    'kunci shock 4-19 mm': 'Kunci Sok 4-19 mm',
}

def target_by_name(name):
    if name not in name_index:
        raise RuntimeError(f'Target manual tidak ada: {name}')
    return name_index[name]

def select(s):
    if s['name'] in manual_names:
        return target_by_name(manual_names[s['name']]), 'manual'
    raw=raw_index[s['name'].casefold()]
    if len(raw)==1: return raw[0], 'exact'
    exact=norm_index[s['norm']]
    if len(exact)==1: return exact[0], 'canonical'

    transforms=[]
    if s['norm'].startswith('ring pas '): transforms.append('kunci '+s['norm'])
    if s['norm'].startswith('outside '): transforms.append(s['norm'].replace('outside ', 'outside micrometer ',1))
    if s['norm'].startswith('inside '): transforms.append(s['norm'].replace('inside ', 'inside micrometer ',1))
    if s['norm'].startswith('webbing sling '): transforms.append(s['norm'].replace('webbing sling ', 'sling belt ',1))
    for n in transforms:
        matches=norm_index[n]
        if len(matches)==1: return matches[0], 'canonical-rule'

    ranked=sorted(((SequenceMatcher(None,s['norm'],t['norm']).ratio()*100,t) for t in template),key=lambda x:x[0],reverse=True)
    if ranked[0][0] >= 89 and (len(ranked)<2 or ranked[0][0]-ranked[1][0]>=2.0):
        return ranked[0][1], f'fuzzy-{ranked[0][0]:.1f}'
    return None, 'unresolved'

unresolved=[]; mapped=[]
for s in sources:
    t,method=select(s)
    if t is None:
        ranked=sorted(((SequenceMatcher(None,s['norm'],x['norm']).ratio()*100,x) for x in template),key=lambda x:x[0],reverse=True)[:5]
        unresolved.append({**s,'candidates':[(round(sc,1),x['row'],x['name']) for sc,x in ranked]})
    else:
        mapped.append({**s,'target_row':t['row'],'target_code':t['code'],'target_name':t['name'],'target_total':t['total'],'method':method})

if unresolved:
    print('UNRESOLVED',len(unresolved))
    for u in unresolved: print(u)
    raise SystemExit(2)

agg=defaultdict(float); detail=defaultdict(list)
for m in mapped:
    agg[m['target_row']]+=m['qty']; detail[m['target_row']].append(m)

for k,v in list(agg.items()):
    if float(v).is_integer(): agg[k]=int(v)

over=[]
for row,qty in sorted(agg.items()):
    t=next(x for x in template if x['row']==row)
    if isinstance(t['total'],(int,float)) and qty>t['total']:
        over.append({'row':row,'name':t['name'],'mapped':qty,'total_asset':t['total'],'sources':[(x['sheet'],x['row'],x['name'],x['qty']) for x in detail[row]]})

output={'source_row_count':len(sources),'source_total':sum(x['qty'] for x in sources),'mapped_row_count':len(mapped),'mapped_total':sum(agg.values()),'populated_template_rows':len(agg),'mapping':mapped,'aggregated':{str(k):v for k,v in agg.items()},'over_total_asset':over}
with open('mapping.json','w',encoding='utf-8') as f: json.dump(output,f,ensure_ascii=False,indent=2)
print('mapped rows',len(mapped),'source total',output['source_total'],'template rows populated',len(agg),'mapped total',output['mapped_total'])
print('methods',dict(sorted(__import__('collections').Counter(x['method'] for x in mapped).items())))
print('over total asset',len(over))
for x in over: print(json.dumps(x,ensure_ascii=False))
