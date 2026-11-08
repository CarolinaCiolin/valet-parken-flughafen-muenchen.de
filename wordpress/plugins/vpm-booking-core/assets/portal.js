(() => {
 const root=document.querySelector('.vpm-portal'),nav=root?.querySelector('.vpm-portal-nav');if(!nav)return;
 const links=[...nav.querySelectorAll('a')],sections=links.map(a=>root.querySelector(a.hash)).filter(Boolean),allowed=new Set(sections.map(s=>s.id));
 const mobile=document.createElement('label'),select=document.createElement('select');mobile.className='vpm-portal-mobile-nav';mobile.append(document.createTextNode('Bereich auswählen'));links.forEach(a=>{const o=document.createElement('option');o.value=a.hash.slice(1);o.textContent=a.textContent;select.append(o);});mobile.append(select);nav.prepend(mobile);nav.dataset.enhanced='1';select.addEventListener('change',()=>{location.hash=select.value;});
 const show=()=>{let key=location.hash.slice(1);if(key==='today')key='overview';if(!allowed.has(key))key='overview';sections.forEach(s=>s.hidden=s.id!==key);links.forEach(a=>{if(a.hash==='#'+key)a.setAttribute('aria-current','page');else a.removeAttribute('aria-current');});const platform=root.querySelector('#platform-overview');if(platform)platform.hidden=key!=='overview';select.value=key;root.dataset.view=key;};
 window.addEventListener('hashchange',()=>{show();const active=root.querySelector('#'+root.dataset.view);active?.scrollIntoView({block:'start'});});show();
 root.addEventListener('click',e=>{const a=e.target.closest('a');if(!a)return;const id=a.hash.slice(1);if(allowed.has(id)||id==='today'){if(location.hash===a.hash)show();}});
})();
