import json
from collections import defaultdict
with open('mapping.json',encoding='utf-8') as f:d=json.load(f)
groups=defaultdict(list)
for m in d['mapping']:
    if m['method'].startswith('fuzzy') or m['method'] in ('manual','canonical-rule'):
        groups[(m['name'],m['target_name'],m['method'])].append((m['sheet'],m['row'],m['qty']))
for (src,tgt,method),refs in sorted(groups.items(),key=lambda x:(x[0][2],x[0][0].lower())):
    print(f'{method}: {src} -> {tgt} | {refs}')
