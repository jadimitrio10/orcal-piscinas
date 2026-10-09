const { chromium } = require('playwright');
(async () => {
  const ip = process.env.IP;
  const b = await chromium.launch({ args: [`--host-resolver-rules=MAP orcalpiscinas.com ${ip}, MAP www.orcalpiscinas.com ${ip}`, '--ignore-certificate-errors'] });
  const shots = [['inicio', '/'], ['proyecto', '/project/bdg-baru/'], ['contacto', '/contactenos/'], ['empresa', '/empresa/']];
  for (const [vw, w, h] of [['pc', 1366, 900], ['celular', 390, 844]]) {
    const p = await b.newPage({ viewport: { width: w, height: h }, deviceScaleFactor: vw === 'celular' ? 2 : 1 });
    const errores = [];
    p.on('response', r => { if (r.status() >= 400 && r.url().includes('orcalpiscinas.com')) errores.push(r.status() + ' ' + r.url()); });
    for (const [n, path] of shots) {
      await p.goto('https://orcalpiscinas.com' + path, { waitUntil: 'networkidle', timeout: 60000 });
      await p.waitForTimeout(2500);
      await p.screenshot({ path: `capturas/${vw}-${n}.png`, fullPage: false });
    }
    console.log(vw, 'errores de carga:', errores.length ? errores.join('\n') : 'ninguno');
    await p.close();
  }
  await b.close();
})();
