import json, re, unicodedata
from collections import defaultdict, Counter

with open('workbooks.json',encoding='utf-8') as f:data=json.load(f)
def tx(v): return '' if v is None else str(v).strip()
def norm(s):
    s=unicodedata.normalize('NFKC',tx(s)).lower().strip()
    s=s.replace('″','"').replace('“','"').replace('”','"').replace("''",'"').replace('′',"'")
    s=s.replace('obeng (+)','obeng plus').replace('obeng (-)','obeng minus')
    reps=[
      (r'\bkunci\s+pas\s+ring\b','kunci ring pas'),(r'\bring\s+pass\b','ring pas'),
      (r'\bchainblock\b','chain block'),(r'\bshock\b','sok'),(r'\bsackle\b','shackle'),
      (r'\brachet\b','ratchet'),(r'\bpukur\b','pukul'),(r'\bimpack\b','impact'),
      (r'\blevel\s+block\b','lever block'),(r'\bebolt\b','eye bolt'),
      (r'\bminya\b','minyak'),(r'\bhouse\b','hose'),(r'\bwater\s+pas\b','waterpass'),
      (r'\bkombonasi\b','kombinasi'),(r'\bwebing\b','webbing'),
      (r'\bselang\s+udara\b','selang angin'),(r'\bmin\b','minus'),
      (r'\bout\s+side\b','outside'),(r'\beye\s*bolt\b','eye bolt'),
      (r'\bsnapring\b','snap ring'),(r'\btevlon\b','teflon'),
      (r'\bgerindra\b|\bgrindra\b','gerinda'),(r'\bmeter\b','m'),
      (r'\bliter\b','l'),(r'\btons?\b|\bt\b','ton'),(r'\blbs\b','lb'),
    ]
    for a,b in reps:s=re.sub(a,b,s)
    s=re.sub(r'(?<=\d),(?=\d)','.',s)
    s=re.sub(r'(?<=\d)\s*(kg|mm|cm|m|l|lb)\b',r' \1',s)
    s=re.sub(r'(?<=\d)\s*[x×]\s*(?=\d)',' x ',s)
    s=re.sub(r'[^a-z0-9./"\']+',' ',s)
    return ' '.join(s.split())

trows=data['template'][0]['values']; template=[]
for r,row in enumerate(trows[1:],2):
    row=(row+[None]*6)[:6]
    template.append({'row':r,'code':row[0],'name':row[1],'type':row[2],'class':row[3],'total':row[4],'old_qty':row[5],'norm':norm(row[1])})
raw_idx=defaultdict(list); norm_idx=defaultdict(list); name_idx={}
for t in template:
    raw_idx[tx(t['name']).casefold()].append(t);norm_idx[t['norm']].append(t);name_idx[t['name']]=t

sources=[]
for sh in data['source']:
    vals=sh['values'];hi=nc=qc=None
    for i,row in enumerate(vals):
        low=[tx(x).lower() for x in row]
        nc=next((j for j,x in enumerate(low) if x in ('nama tool','nama tools','nama peralatan')),None)
        if nc is not None:
            hi=i;qc=next((j for j,x in enumerate(low) if x in ('qty','qty.','jumlah','quantity')),None);break
    if hi is None or qc is None:raise RuntimeError(f'header missing {sh["name"]}')
    for r,row in enumerate(vals[hi+1:],hi+2):
        if nc>=len(row) or qc>=len(row):continue
        name=row[nc];qty=row[qc]
        if not isinstance(name,str) or not name.strip():continue
        if isinstance(qty,bool) or not isinstance(qty,(int,float)):continue
        sources.append({'sheet':sh['name'],'row':r,'name':name.strip(),'qty':qty,'norm':norm(name)})

manual={
 'Shock (58)':'Mata Sok 58','Shock (55)':'Mata Sok 55','Shock (24)':'Mata Sok 24','Shock (60)':'Mata Sok 60','Shock (38)':'Mata Sok 38',
 'Sackle 8 1/2 TON':'Shackle 8.5 ton','Sackle 6 1/2 TON':'Shackle 6.5 ton','Sackle 4 3/4 TON':'Shackle 4.75 ton','Sackle 3 1/4 TON':'Shackle 3.25 ton',
 'Sling Belt 5 TON 5M':'Sling Belt 5 ton x 5 m','Sling Belt 2 TON 5M':'Sling Belt 2 ton x 5 m',
 'Sling Belt 2 TON 2,5M':'Sling Belt 2 ton x 2.5 m','Sling Belt 2 TON 3,5M':'Sling Belt 2 ton x 3.5 m',
 'Sling Belt 3 TON 3,5M':'Sling Belt 3 ton x 3.5 m','Sling Belt 1 TON 2,5M':'Sling Belt 1 ton x 2.5 m',
 'Sling Belt 1 TON 1M':'Sling Belt 1 ton x 1 m','Obeng Kembang':'Obeng Plus',
 'Outside 150-300':'Outside Micrometer 150-300',
}
for target in manual.values():
    if target not in name_idx:raise RuntimeError(f'manual target missing: {target}')

mapped=[];unresolved=[]
for s in sources:
    if s['name'] in manual:t=name_idx[manual[s['name']]];method='manual'
    elif len(raw_idx[s['name'].casefold()])==1:t=raw_idx[s['name'].casefold()][0];method='exact'
    elif len(norm_idx[s['norm']])==1:t=norm_idx[s['norm']][0];method='canonical'
    else:unresolved.append(s);continue
    mapped.append({**s,'target_row':t['row'],'target_code':t['code'],'target_name':t['name'],'target_total':t['total'],'method':method})
if unresolved:
    print('UNRESOLVED',unresolved);raise SystemExit(2)

agg=defaultdict(float);detail=defaultdict(list)
for m in mapped:agg[m['target_row']]+=m['qty'];detail[m['target_row']].append(m)
for k,v in list(agg.items()):
    if float(v).is_integer():agg[k]=int(v)
over=[]
for row,qty in sorted(agg.items()):
    t=next(x for x in template if x['row']==row)
    if isinstance(t['total'],(int,float)) and qty>t['total']:
        over.append({'row':row,'name':t['name'],'mapped':qty,'total_asset':t['total'],'sources':[(x['sheet'],x['row'],x['name'],x['qty']) for x in detail[row]]})
out={'source_row_count':len(sources),'source_total':sum(x['qty'] for x in sources),'mapped_row_count':len(mapped),'mapped_total':sum(agg.values()),'populated_template_rows':len(agg),'mapping':mapped,'aggregated':{str(k):v for k,v in agg.items()},'over_total_asset':over}
with open('mapping.json','w',encoding='utf-8') as f:json.dump(out,f,ensure_ascii=False,indent=2)
print('mapped',len(mapped),'total',out['mapped_total'],'targets',len(agg),'methods',Counter(x['method'] for x in mapped),'over',len(over))
for x in over:print(json.dumps(x,ensure_ascii=False))
print('\nMANUAL REVIEW')
for m in mapped:
    if m['method']=='manual':print(f"{m['sheet']}!{m['row']} {m['name']} -> {m['target_name']} qty={m['qty']}")
