/* Landing page: automatic photo slideshow (no controls), sticky nav, role tabs. */
(function () {
  'use strict';
  const photos = CV.$$('.hp');
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  let i = 0;
  if (photos.length > 1 && !reduce) {
    setInterval(() => {
      if (document.hidden) return;
      photos[i].classList.remove('on');
      i = (i + 1) % photos.length;
      photos[i].classList.add('on');
    }, 7500);
  }

  // nav turns solid after the hero
  const nav = CV.$('#nav');
  const onScroll = () => nav.classList.toggle('solid', window.scrollY > 40);
  onScroll(); window.addEventListener('scroll', onScroll, { passive: true });

  // role tabs
  CV.$$('.role-tabs button').forEach((b) => b.addEventListener('click', () => {
    CV.$$('.role-tabs button').forEach((x) => x.classList.toggle('on', x === b));
    CV.$$('.role-panel').forEach((p) => p.classList.toggle('on', p.dataset.panel === b.dataset.role));
  }));

  // placeholder social links: don't jump to the page top
  CV.$$('.social[href="#"]').forEach((a) => a.addEventListener('click', (e) => { e.preventDefault(); CV.toast(a.dataset.net + ' page link coming soon.', 'ok'); }));
})();
