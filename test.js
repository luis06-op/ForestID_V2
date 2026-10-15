const puppeteer = require('puppeteer');

(async () => {
    const browser = await puppeteer.launch({ args: ['--no-sandbox', '--disable-setuid-sandbox'] });
    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });
    await page.goto('http://localhost/forestID/publico/index.php');

    const result = await page.evaluate(() => {
        let e = document.querySelector('.hero-copas');
        const logs = [];
        const originalHero = e;
        while (e) {
            const s = getComputedStyle(e);
            logs.push(`${e.tagName} ${String(e.className).slice(0, 40)} | altura: ${s.height} | maxAltura: ${s.maxHeight} | overflow: ${s.overflowY} | position: ${s.position} | contain: ${s.contain} | contentVisibility: ${s.contentVisibility}`);
            e = e.parentElement;
        }

        const metrics = {
            heroScrollHeight: originalHero.scrollHeight,
            heroClientHeight: originalHero.clientHeight,
            heroBounding: originalHero.getBoundingClientRect(),
        };

        return { logs, metrics };
    });

    console.log("DOM Tree Styles:");
    console.log(result.logs.join('\n'));
    console.log("\nMetrics:", result.metrics);
    await browser.close();
})();
