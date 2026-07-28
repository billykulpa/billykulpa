import { chromium } from 'playwright';
const b = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });
const p = await b.newPage({ viewport: { width: 1440, height: 900 } });
await p.goto('http://127.0.0.1:8090/work/joie-de-vivre', { waitUntil: 'networkidle' });
await p.addStyleTag({ content: '.site-header { position: static !important; }' });
await p.evaluate(async () => {
  const imgs = [...document.querySelectorAll('img')];
  imgs.forEach(i => i.loading = 'eager');
  await Promise.all(imgs.map(i => i.decode().catch(() => {})));
});
await p.waitForTimeout(500);
await p.screenshot({ path: 'shots/jdv-case.png', fullPage: true });
await b.close();
console.log('done');
