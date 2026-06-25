const E=()=>{var e;return((e=document.querySelector('meta[name="csrf-token"]'))==null?void 0:e.content)??""},k=(e,t,n=null)=>fetch(e,{method:t,headers:{"Content-Type":"application/json",Accept:"application/json","X-CSRF-TOKEN":E(),"X-Requested-With":"XMLHttpRequest"},body:n===null?void 0:JSON.stringify(n)}),w=(e,t={})=>k(e,"POST",t),M=e=>k(e,"DELETE"),A=e=>k(e,"GET"),T=e=>"€"+Number(e).toLocaleString("nl-NL",{minimumFractionDigits:2,maximumFractionDigits:2}),g=e=>String(e??"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;"),j={bol:{label:"bol.com",cls:"bol"},amazon:{label:"Amazon",cls:"amazon"},tweakers:{label:"Tweakers",cls:"tweakers"}},C=e=>{var t;return((t=j[String(e).toLowerCase()])==null?void 0:t.label)??String(e)},b={Search:'<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.2-3.2"/>',Check:'<path d="M5 12.5 10 17l9-10"/>',X:'<path d="M6 6l12 12M18 6 6 18"/>',Shield:'<path d="M12 3 5 6v5c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6z"/><path d="M9.2 12l2 2 3.6-3.8"/>',Box:'<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>'},m=(e,t=16,n=1.7,d="")=>{const a=b[e]||b.Box;return`<svg class="${d}" width="${t}" height="${t}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="${n}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${a}</svg>`},S=(e,t)=>String(e||"").replace("__ID__",encodeURIComponent(t)),_=(e,t,n="flat")=>{const d=e.viewBox.baseVal.width||88,a=e.viewBox.baseVal.height||30;if(!t||t.length<2){e.innerHTML="";return}const s=Math.min(...t),l=Math.max(...t)-s||1,u=3,i=t.map((h,f)=>{const q=u+f/(t.length-1)*(d-u*2),x=a-u-(h-s)/l*(a-u*2);return[q,x]}),r=i.map((h,f)=>(f?"L":"M")+h[0].toFixed(1)+" "+h[1].toFixed(1)).join(" "),c=`${r} L ${i[i.length-1][0].toFixed(1)} ${a} L ${i[0][0].toFixed(1)} ${a} Z`,o=n==="down"?"var(--ok)":n==="up"?"var(--danger)":"var(--tx-3)",v=i[i.length-1],y="dtg-"+Math.round(i.reduce((h,f)=>h+f[1],0));e.innerHTML=`<defs><linearGradient id="${y}" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" stop-color="${o}" stop-opacity="0.18"/>
        <stop offset="1" stop-color="${o}" stop-opacity="0"/></linearGradient></defs>
        <path d="${c}" fill="url(#${y})"/>
        <path d="${r}" fill="none" stroke="${o}" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="${v[0].toFixed(1)}" cy="${v[1].toFixed(1)}" r="2.4" fill="${o}"/>`},D=e=>{const t=e.dataset.historyTpl;e.querySelectorAll("[data-deals-product]").forEach(n=>{const d=n.dataset.dealsProduct,a=n.querySelectorAll("[data-deals-spark]");!d||!t||a.length===0||A(S(t,d)).then(s=>s.ok?s.json():null).then(s=>{if(!s||!Array.isArray(s.listings))return;const p=new Map(s.listings.map(l=>[String(l.id),l]));n.querySelectorAll("[data-deals-listing]").forEach(l=>{const u=l.querySelector("[data-deals-spark]"),i=p.get(String(l.dataset.dealsListing));if(!u||!i||!Array.isArray(i.price_points))return;const r=i.price_points.slice().sort((o,v)=>new Date(o.observed_at)-new Date(v.observed_at)).map(o=>Number(o.price));if(r.length<2)return;const c=r[r.length-1]<r[0]?"down":r[r.length-1]>r[0]?"up":"flat";_(u,r,c)})}).catch(()=>{})})},F=e=>`<div class="dt-cand" data-deals-cand="${g(e.id)}">
    <div class="dt-cand-top">
        <div class="dt-cand-thumb">${m("Box",20,1.5,"ic")}</div>
        <div class="dt-cand-main">
            <div class="dt-cand-name">${g(e.title)}</div>
            <div class="dt-cand-row">
                ${e.current_price!=null?`<span class="dt-cand-price tnum">${T(e.current_price)}</span>`:""}
            </div>
        </div>
    </div>
    <div class="dt-cand-actions">
        <button class="dt-cact confirm" data-deals-confirm>${m("Check",14,1.7)} Bevestigen</button>
        <button class="dt-cact remove" data-deals-remove>${m("X",14,1.7)} Verwijderen</button>
    </div>
</div>`,R=e=>{const t=Array.isArray(e.listings)?e.listings:[],n=["bol","amazon","tweakers"].map(d=>{const a=t.filter(l=>String(l.retailer).toLowerCase()===d),s=a.length===0?`<div class="dt-cand-none">${m("X",20,1.7,"ic")}<div>Geen match — hier wordt niets gevolgd.</div></div>`:a.map(F).join(""),p=a.length===1?"kandidaat":"kandidaten";return`<div class="dt-storecol">
                <div class="dt-storecol-head ${d}">
                    <span class="led"></span>
                    <span class="dt-storecol-name">${g(C(d))}</span>
                    <span class="dt-storecol-count">${a.length} ${p}</span>
                </div>
                <div class="dt-storecol-body">${s}</div>
            </div>`}).join("");return`<div class="dt-add" data-deals-review="${g(e.id)}">
        <div class="dt-review-head">
            <div class="dt-review-q">
                <span>Resultaten voor</span>
                <span class="term">${m("Search",13,1.7,"ic")} ${g(e.name)}</span>
            </div>
        </div>
        <div class="dt-guard">
            ${m("Shield",17,1.7,"ic")}
            <div class="dt-guard-tx">
                Bevestig per winkel de <b>juiste</b> match en verwijder verkeerde resultaten — zoals een andere
                generatie of los accessoire. <b>Alleen bevestigde producten worden gevolgd</b>, zodat je geen
                verkeerde prijs binnenhaalt.
            </div>
        </div>
        <div class="dt-review-grid">${n}</div>
    </div>`},L=(e,t)=>{var d,a;const n=t.dataset.dealsCand;n&&((d=t.querySelector("[data-deals-confirm]"))==null||d.addEventListener("click",async()=>{t.classList.add("confirmed"),await w(S(e.dataset.confirmTpl,n)).catch(()=>{}),window.location.reload()}),(a=t.querySelector("[data-deals-remove]"))==null||a.addEventListener("click",async()=>{t.classList.add("removed"),await M(S(e.dataset.destroyTpl,n)).catch(()=>{});const s=t.closest(".dt-storecol-body");t.remove(),s&&s.querySelectorAll("[data-deals-cand]").length===0&&(s.innerHTML=`<div class="dt-cand-none">${m("X",20,1.7,"ic")}<div>Geen match — hier wordt niets gevolgd.</div></div>`)}))},$=()=>{var i,r;const e=document.querySelector("[data-deals]");if(!e||e.dataset.dealsReady==="true")return;e.dataset.dealsReady="true";const t=e.querySelector("[data-deals-add]"),n=e.querySelector("[data-deals-add-loading]"),d=e.querySelector("[data-deals-main]"),a=e.querySelector("[data-deals-search-input]"),s=e.querySelector("[data-deals-search-submit]"),p=e.querySelector("[data-deals-search-term]"),l=()=>{t&&(t.hidden=!1),n&&(n.hidden=!0),d&&(d.hidden=!0),a==null||a.focus()},u=()=>{t&&(t.hidden=!0),n&&(n.hidden=!0),d&&(d.hidden=!1)};e.querySelectorAll("[data-deals-add-open]").forEach(c=>c.addEventListener("click",l)),(i=e.querySelector("[data-deals-add-cancel]"))==null||i.addEventListener("click",u),a==null||a.addEventListener("input",()=>{s&&(s.disabled=a.value.trim()==="")}),(r=e.querySelector("[data-deals-search-form]"))==null||r.addEventListener("submit",async c=>{c.preventDefault();const o=((a==null?void 0:a.value)||"").trim();if(o){p&&(p.textContent=o),t&&(t.hidden=!0),n&&(n.hidden=!1);try{const v=await w(e.dataset.storeUrl,{name:o});if(!v.ok)throw new Error("store failed");const h=(await v.json()).product;n&&(n.hidden=!0),d&&(d.hidden=!1,d.insertAdjacentHTML("afterbegin",R(h)),d.querySelectorAll("[data-deals-cand]").forEach(f=>L(e,f)))}catch{window.location.reload()}}}),e.querySelectorAll("[data-deals-cand]").forEach(c=>L(e,c)),e.querySelectorAll("[data-deals-check]").forEach(c=>{c.addEventListener("click",async()=>{var o;e.querySelectorAll("[data-deals-check]").forEach(v=>v.disabled=!0),(o=c.querySelector(".ic"))==null||o.classList.add("spin"),await w(e.dataset.checkUrl).catch(()=>{}),window.location.reload()})}),D(e)};document.readyState==="loading"?document.addEventListener("DOMContentLoaded",$,{once:!0}):$();document.addEventListener("livewire:navigated",$);
