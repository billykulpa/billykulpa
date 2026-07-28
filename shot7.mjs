import { chromium } from 'playwright';
const b = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });
async function shot(url, path) {
  const p = await b.newPage({ viewport: { width: 1440, height: 900 } });
  await p.goto(url, { waitUntil: 'networkidle' });
  await p.addStyleTag({ content: '.site-header { position: static !important; }' });
  await p.evaluate(async () => {  // force lazy images to decode before capture
    const imgs = [...document.querySelectorAll('img[loading=lazy]')];
    imgs.forEach(i => i.loading = 'eager');
    await Promise.all(imgs.map(i => i.decode().catch(() => {})));
  });
  await p.waitForTimeout(400);
  await p.screenshot({ path, fullPage: true });
  await p.close();
}
await shot('http://127.0.0.1:8090/about', 'shots/about-v2.png');
await shot('http://127.0.0.1:8090/work/restreak', 'shots/case-restreak-v2.png');
await b.close();
console.log('done');
