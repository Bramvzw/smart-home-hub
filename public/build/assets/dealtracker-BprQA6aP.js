const M=()=>{var e;return((e=document.querySelector('meta[name="csrf-token"]'))==null?void 0:e.content)??""},b=(e,t,a=null)=>fetch(e,{method:t,headers:{"Content-Type":"application/json",Accept:"application/json","X-CSRF-TOKEN":M(),"X-Requested-With":"XMLHttpRequest"},body:a===null?void 0:JSON.stringify(a)}),w=(e,t={})=>b(e,"POST",t),A=e=>b(e,"DELETE"),T=e=>b(e,"GET"),j=e=>"€"+Number(e).toLocaleString("nl-NL",{minimumFractionDigits:2,maximumFractionDigits:2}),h=e=>String(e??"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;"),k=e=>{if(typeof e!="string")return null;const t=e.trim();if(t==="")return null;try{const a=new URL(t);return a.protocol==="http:"||a.protocol==="https:"?t:null}catch{return null}},C={bol:{label:"bol.com",cls:"bol"},amazon:{label:"Amazon",cls:"amazon"},tweakers:{label:"Tweakers",cls:"tweakers"}},_=e=>{var t;return((t=C[String(e).toLowerCase()])==null?void 0:t.label)??String(e)},L={Search:'<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.2-3.2"/>',Check:'<path d="M5 12.5 10 17l9-10"/>',X:'<path d="M6 6l12 12M18 6 6 18"/>',Shield:'<path d="M12 3 5 6v5c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6z"/><path d="M9.2 12l2 2 3.6-3.8"/>',Box:'<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>'},g=(e,t=16,a=1.7,d="")=>{const n=L[e]||L.Box;return`<svg class="${d}" width="${t}" height="${t}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="${a}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${n}</svg>`},$=(e,t)=>String(e||"").replace("__ID__",encodeURIComponent(t)),D=(e,t,a="flat")=>{const d=e.viewBox.baseVal.width||88,n=e.viewBox.baseVal.height||30;if(!t||t.length<2){e.innerHTML="";return}const s=Math.min(...t),l=Math.max(...t)-s||1,u=3,o=t.map((v,f)=>{const x=u+f/(t.length-1)*(d-u*2),E=n-u-(v-s)/l*(n-u*2);return[x,E]}),c=o.map((v,f)=>(f?"L":"M")+v[0].toFixed(1)+" "+v[1].toFixed(1)).join(" "),i=`${c} L ${o[o.length-1][0].toFixed(1)} ${n} L ${o[0][0].toFixed(1)} ${n} Z`,r=a==="down"?"var(--ok)":a==="up"?"var(--danger)":"var(--tx-3)",p=o[o.length-1],y="dtg-"+Math.round(o.reduce((v,f)=>v+f[1],0));e.innerHTML=`<defs><linearGradient id="${y}" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" stop-color="${r}" stop-opacity="0.18"/>
        <stop offset="1" stop-color="${r}" stop-opacity="0"/></linearGradient></defs>
        <path d="${i}" fill="url(#${y})"/>
        <path d="${c}" fill="none" stroke="${r}" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="${p[0].toFixed(1)}" cy="${p[1].toFixed(1)}" r="2.4" fill="${r}"/>`},R=e=>{const t=e.dataset.historyTpl;e.querySelectorAll("[data-deals-product]").forEach(a=>{const d=a.dataset.dealsProduct,n=a.querySelectorAll("[data-deals-spark]");!d||!t||n.length===0||T($(t,d)).then(s=>s.ok?s.json():null).then(s=>{if(!s||!Array.isArray(s.listings))return;const m=new Map(s.listings.map(l=>[String(l.id),l]));a.querySelectorAll("[data-deals-listing]").forEach(l=>{const u=l.querySelector("[data-deals-spark]"),o=m.get(String(l.dataset.dealsListing));if(!u||!o||!Array.isArray(o.price_points))return;const c=o.price_points.slice().sort((r,p)=>new Date(r.observed_at)-new Date(p.observed_at)).map(r=>Number(r.price));if(c.length<2)return;const i=c[c.length-1]<c[0]?"down":c[c.length-1]>c[0]?"up":"flat";D(u,c,i)})}).catch(()=>{})})},F=e=>{const t=k(e.image_url),a=k(e.url),d=a?`<a class="dt-cand-name" href="${h(a)}" target="_blank" rel="noopener noreferrer">${h(e.title)}</a>`:`<div class="dt-cand-name">${h(e.title)}</div>`,n=t?`<img src="${h(t)}" alt="${h(e.title)}" loading="lazy">`:g("Box",20,1.5,"ic");return`<div class="dt-cand" data-deals-cand="${h(e.id)}">
    <div class="dt-cand-top">
        <div class="dt-cand-thumb">${n}</div>
        <div class="dt-cand-main">
            ${d}
            <div class="dt-cand-row">
                ${e.current_price!=null?`<span class="dt-cand-price tnum">${j(e.current_price)}</span>`:""}
            </div>
        </div>
    </div>
    <div class="dt-cand-actions">
        <button class="dt-cact confirm" data-deals-confirm>${g("Check",14,1.7)} Bevestigen</button>
        <button class="dt-cact remove" data-deals-remove>${g("X",14,1.7)} Verwijderen</button>
    </div>
</div>`},z=e=>{const t=Array.isArray(e.listings)?e.listings:[],a=["bol","amazon","tweakers"].map(d=>{const n=t.filter(l=>String(l.retailer).toLowerCase()===d),s=n.length===0?`<div class="dt-cand-none">${g("X",20,1.7,"ic")}<div>Geen match — hier wordt niets gevolgd.</div></div>`:n.map(F).join(""),m=n.length===1?"kandidaat":"kandidaten";return`<div class="dt-storecol">
                <div class="dt-storecol-head ${d}">
                    <span class="led"></span>
                    <span class="dt-storecol-name">${h(_(d))}</span>
                    <span class="dt-storecol-count">${n.length} ${m}</span>
                </div>
                <div class="dt-storecol-body">${s}</div>
            </div>`}).join("");return`<div class="dt-add" data-deals-review="${h(e.id)}">
        <div class="dt-review-head">
            <div class="dt-review-q">
                <span>Resultaten voor</span>
                <span class="term">${g("Search",13,1.7,"ic")} ${h(e.name)}</span>
            </div>
        </div>
        <div class="dt-guard">
            ${g("Shield",17,1.7,"ic")}
            <div class="dt-guard-tx">
                Bevestig per winkel de <b>juiste</b> match en verwijder verkeerde resultaten — zoals een andere
                generatie of los accessoire. <b>Alleen bevestigde producten worden gevolgd</b>, zodat je geen
                verkeerde prijs binnenhaalt.
            </div>
        </div>
        <div class="dt-review-grid">${a}</div>
    </div>`},q=(e,t)=>{var d,n;const a=t.dataset.dealsCand;a&&((d=t.querySelector("[data-deals-confirm]"))==null||d.addEventListener("click",async()=>{t.classList.add("confirmed"),await w($(e.dataset.confirmTpl,a)).catch(()=>{}),window.location.reload()}),(n=t.querySelector("[data-deals-remove]"))==null||n.addEventListener("click",async()=>{t.classList.add("removed"),await A($(e.dataset.destroyTpl,a)).catch(()=>{});const s=t.closest(".dt-storecol-body");t.remove(),s&&s.querySelectorAll("[data-deals-cand]").length===0&&(s.innerHTML=`<div class="dt-cand-none">${g("X",20,1.7,"ic")}<div>Geen match — hier wordt niets gevolgd.</div></div>`)}))},S=()=>{var o,c;const e=document.querySelector("[data-deals]");if(!e||e.dataset.dealsReady==="true")return;e.dataset.dealsReady="true";const t=e.querySelector("[data-deals-add]"),a=e.querySelector("[data-deals-add-loading]"),d=e.querySelector("[data-deals-main]"),n=e.querySelector("[data-deals-search-input]"),s=e.querySelector("[data-deals-search-submit]"),m=e.querySelector("[data-deals-search-term]"),l=()=>{t&&(t.hidden=!1),a&&(a.hidden=!0),d&&(d.hidden=!0),n==null||n.focus()},u=()=>{t&&(t.hidden=!0),a&&(a.hidden=!0),d&&(d.hidden=!1)};e.querySelectorAll("[data-deals-add-open]").forEach(i=>i.addEventListener("click",l)),(o=e.querySelector("[data-deals-add-cancel]"))==null||o.addEventListener("click",u),n==null||n.addEventListener("input",()=>{s&&(s.disabled=n.value.trim()==="")}),(c=e.querySelector("[data-deals-search-form]"))==null||c.addEventListener("submit",async i=>{i.preventDefault();const r=((n==null?void 0:n.value)||"").trim();if(r){m&&(m.textContent=r),t&&(t.hidden=!0),a&&(a.hidden=!1);try{const p=await w(e.dataset.storeUrl,{name:r});if(!p.ok)throw new Error("store failed");const v=(await p.json()).product;a&&(a.hidden=!0),d&&(d.hidden=!1,d.insertAdjacentHTML("afterbegin",z(v)),d.querySelectorAll("[data-deals-cand]").forEach(f=>q(e,f)))}catch{window.location.reload()}}}),e.querySelectorAll("[data-deals-cand]").forEach(i=>q(e,i)),e.querySelectorAll("[data-deals-check]").forEach(i=>{i.addEventListener("click",async()=>{var r;e.querySelectorAll("[data-deals-check]").forEach(p=>p.disabled=!0),(r=i.querySelector(".ic"))==null||r.classList.add("spin"),await w(e.dataset.checkUrl).catch(()=>{}),window.location.reload()})}),R(e)};document.readyState==="loading"?document.addEventListener("DOMContentLoaded",S,{once:!0}):S();document.addEventListener("livewire:navigated",S);
