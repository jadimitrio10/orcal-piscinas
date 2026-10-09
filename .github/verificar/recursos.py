import os, re, subprocess, glob, urllib.parse
IP, DOM = os.environ['IP'], os.environ['DOM']
def get(path, head=False):
    cmd = ['curl', '-sk', '-o', '/tmp/r', '-w', '%{http_code}', '--resolve', f'{DOM}:443:{IP}', f'https://{DOM}{path}']
    if head: cmd.insert(1, '-I')
    code = subprocess.run(cmd, capture_output=True, text=True).stdout
    return code, (os.path.getsize('/tmp/r') if os.path.exists('/tmp/r') else 0)

pages = sorted(p for p in glob.glob('**/index.html', recursive=True) if not p.startswith('.'))
print('### Páginas'); print('| Página | HTTP | Igual al repo |'); print('|---|---|---|')
bad = 0; refs = set()
for p in pages:
    url = '/' + p[:-len('index.html')]
    code, size = get(url)
    same = size == os.path.getsize(p)
    bad += (code != '200' or not same)
    print(f'| {url} | {code} | {"sí" if same else "NO"} |')
    html = open(p, encoding='utf-8', errors='ignore').read()
    for m in re.findall(r'''(?:src|href|data-thumb)=["']([^"'#?]+)''', html) + re.findall(r'url\(([^)"\']+)\)', html):
        if m.startswith(('http:', 'https:', '//', 'mailto:', 'tel:', 'javascript:', 'data:')): continue
        refs.add(urllib.parse.urljoin(url, m))
local = [r for r in sorted(refs) if os.path.isfile(urllib.parse.unquote(r.lstrip('/'))) or os.path.isfile(urllib.parse.unquote(r.lstrip('/')) + 'index.html')]
fail = []
for r in local:
    code, _ = get(r)
    if code != '200': fail.append((r, code))
print(f'\n### Recursos: {len(local)} revisados (fotos, estilos, scripts, enlaces), {len(fail)} con error')
for r, c in fail: print(f'- {r} → {c}')
print(f'\n**Resultado: {"TODO BIEN" if not bad and not fail else "HAY PROBLEMAS"}** ({len(pages)} páginas)')
