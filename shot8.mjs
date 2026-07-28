import { chromium } from 'playwright';
const b = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });
async function shot(url, path) {
  const p = await b.newPage({ viewport: { width: 1440, height: 900 } });
  await p.goto(url, { waitUntil: 'networkidle' });
  await p.addStyleTag({ content: '.site-header { position: static !important; }' });
  await p.evaluate(async () => {
    const imgs = [...document.querySelectorAll('img[loading=lazy], img')];
    imgs.forEach(i => i.loading = 'eager');
    await Promise.all(imgs.map(i => i.decode().catch(() => {})));
  });
  await p.waitForTimeout(300);
  await p.screenshot({ path, fullPage: true });
  await p.close();
}
await shot('http://127.0.0.1:8090/notes/clankers-short-story', 'shots/post-clankers.png');
await shot('http://127.0.0.1:8090/notes/my-1994-appearance-in-the-weekly-world-news', 'shots/post-weekly-world.png');
await b.close();
console.log('done');
