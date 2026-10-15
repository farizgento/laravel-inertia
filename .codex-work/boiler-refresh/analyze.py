import json, re, unicodedata
from collections import defaultdict, Counter
from difflib import SequenceMatcher

with open('workbooks.json',encoding='utf-8') as f:data=json.load(f)

def tx(v): return '' if v is None else str(v).strip()
def norm(s):
    s=unicodedata.normalize('NFKC',tx(s)).lower().strip()
    s=s.replace('″','"').replace('“','"').replace('”','"').replace("''",'"').replace('′',"'")
    s=s.replace('obeng (+)','obeng plus').replace('obeng (-)','obeng minus')
    replacements=[
      (r'\bkunci\s+pas\s+ring\b','kunci ring pas'),(r'\bring\s+pass\b','ring pas'),
      (r'\bchainblock\b','chain block'),(r'\bshock\b','sok'),(r'\bsackle\b','shackle'),
      (r'\brachet\b','ratchet'),(r'\bpukur\b','pukul'),(r'\bimpack\b','impact'),
      (r'\blevel\s+block\b','lever block'),(r'\bebolt\b','eye bolt'),
      (r'\bminya\b','minyak'),(r'\bhouse\b','hose'),(r'\bwater\s+pas\b','waterpass'),
      (r'\bkombonasi\b','kombinasi'),(r'\bwebing\b','webbing'),
      (r'\bselang\s+udara\b','selang angin'),(r'\bmin\b','minus'),
      (r'\bapar\b','apar'),(r'\bout\s+side\b','outside'),
      (r'\beye\s*bolt\b','eye bolt'),(r'\bsnapring\b','snap ring'),
      (r'\btevlon\b','teflon'),(r'\bgerindra\b|\bgrindra\b','gerinda'),
      (r'\bmeter\b','m'),(r'\bliter\b','l'),(r'\btons?\b|\bt\b','ton'),
      (r'\blbs\b','lb'),
    ]
    for a,b in replacements:s=re.sub(a,b,s)
    s=re.sub(r'(?<=\d),(?=\d)','.',s)
    s=re.sub(r'(?<=\d)\s*(kg|mm|cm|m|l|lb)\b',r' \1',s)
    s=re.sub(r'(?<=\d)\s*[x×]\s*(?=\d)',' x ',s)
    s=re.sub(r'[^a-z0-9./"\']+',' ',s)
    return ' '.join(s.split())

trows=data['template'][0]['values']
template=[]
for r,row in enumerate(trows[1:],2):
    row=(row+[None]*6)[:6]
    template.append({'row':r,'code':row[0],'name':row[1],'total':row[4],'norm':norm(row[1])})
idx=defaultdict(list)
for t in template:idx[t['norm']].append(t)

sources=[]
for sh in data['source']:
    vals=sh['values']; hi=nc=qc=None
    for i,row in enumerate(vals):
        low=[tx(x).lower() for x in row]
        for j,x in enumerate(low):
            if x in ('nama tool','nama tools','nama peralatan'):
                hi=i;nc=j;break
        if hi is not None:
            qc=next((j for j,x in enumerate(low) if x in ('qty','qty.','jumlah','quantity')),None)
            break
    if hi is None or qc is None: raise RuntimeError(f'header missing {sh["name"]}')
    for r,row in enumerate(vals[hi+1:],hi+2):
        if nc>=len(row) or qc>=len(row):continue
        name=row[nc];qty=row[qc]
        if not isinstance(name,str) or not name.strip():continue
        if isinstance(qty,bool) or not isinstance(qty,(int,float)):continue
        sources.append({'sheet':sh['name'],'row':r,'name':name.strip(),'qty':qty,'norm':norm(name)})

print('template',len(template),'source rows',len(sources),'total',sum(x['qty'] for x in sources),'by sheet',Counter(x['sheet'] for x in sources))
print('source unique',len(set(x['norm'] for x in sources)),'exact unique rows',sum(1 for x in sources if len(idx[x['norm']])==1))
print('template duplicate norms',[(k,[(x['row'],x['name']) for x in v]) for k,v in idx.items() if len(v)>1])
for s in sources:
    if len(idx[s['norm']])==1:continue
    ranked=sorted(((SequenceMatcher(None,s['norm'],t['norm']).ratio()*100,t) for t in template),key=lambda x:x[0],reverse=True)[:6]
    print(f"{s['sheet']}!{s['row']} | {s['name']} | {s['qty']} | {s['norm']}")
    print('  '+' || '.join(f'{sc:.1f}:{t["row"]}:{t["name"]}' for sc,t in ranked))
