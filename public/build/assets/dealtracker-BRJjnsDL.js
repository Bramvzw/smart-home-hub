const M=()=>{var e;return((e=document.querySelector('meta[name="csrf-token"]'))==null?void 0:e.content)??""},b=(e,t,a=null)=>fetch(e,{method:t,headers:{"Content-Type":"application/json",Accept:"application/json","X-CSRF-TOKEN":M(),"X-Requested-With":"XMLHttpRequest"},body:a===null?void 0:JSON.stringify(a)}),w=(e,t={})=>b(e,"POST",t),A=e=>b(e,"DELETE"),T=e=>b(e,"GET"),C=e=>"€"+Number(e).toLocaleString("nl-NL",{minimumFractionDigits:2,maximumFractionDigits:2}),p=e=>String(e??"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;"),k=e=>{if(typeof e!="string")return null;const t=e.trim();if(t==="")return null;try{const a=new URL(t);return a.protocol==="http:"||a.protocol==="https:"?t:null}catch{return null}},_={bol:{label:"bol.com",cls:"bol"},amazon:{label:"Amazon",cls:"amazon"},tweakers:{label:"Tweakers",cls:"tweakers"}},R=e=>{var t;return((t=_[String(e).toLowerCase()])==null?void 0:t.label)??String(e)},L={Search:'<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.2-3.2"/>',Check:'<path d="M5 12.5 10 17l9-10"/>',X:'<path d="M6 6l12 12M18 6 6 18"/>',Shield:'<path d="M12 3 5 6v5c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6z"/><path d="M9.2 12l2 2 3.6-3.8"/>',Box:'<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>'},y=(e,t=16,a=1.7,s="")=>{const n=L[e]||L.Box;return`<svg class="${s}" width="${t}" height="${t}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="${a}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${n}</svg>`},$=(e,t)=>String(e||"").replace("__ID__",encodeURIComponent(t)),j=(e,t,a="flat")=>{const s=e.viewBox.baseVal.width||88,n=e.viewBox.baseVal.height||30;if(!t||t.length<2){e.innerHTML="";return}const d=Math.min(...t),l=Math.max(...t)-d||1,u=3,o=t.map((m,f)=>{const x=u+f/(t.length-1)*(s-u*2),E=n-u-(m-d)/l*(n-u*2);return[x,E]}),c=o.map((m,f)=>(f?"L":"M")+m[0].toFixed(1)+" "+m[1].toFixed(1)).join(" "),i=`${c} L ${o[o.length-1][0].toFixed(1)} ${n} L ${o[0][0].toFixed(1)} ${n} Z`,r=a==="down"?"var(--ok)":a==="up"?"var(--danger)":"var(--tx-3)",h=o[o.length-1],g="dtg-"+Math.round(o.reduce((m,f)=>m+f[1],0));e.innerHTML=`<defs><linearGradient id="${g}" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" stop-color="${r}" stop-opacity="0.18"/>
        <stop offset="1" stop-color="${r}" stop-opacity="0"/></linearGradient></defs>
        <path d="${i}" fill="url(#${g})"/>
        <path d="${c}" fill="none" stroke="${r}" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="${h[0].toFixed(1)}" cy="${h[1].toFixed(1)}" r="2.4" fill="${r}"/>`},D=e=>{const t=e.dataset.historyTpl;e.querySelectorAll("[data-deals-product]").forEach(a=>{const s=a.dataset.dealsProduct,n=a.querySelectorAll("[data-deals-spark]");!s||!t||n.length===0||T($(t,s)).then(d=>d.ok?d.json():null).then(d=>{if(!d||!Array.isArray(d.listings))return;const v=new Map(d.listings.map(l=>[String(l.id),l]));a.querySelectorAll("[data-deals-listing]").forEach(l=>{const u=l.querySelector("[data-deals-spark]"),o=v.get(String(l.dataset.dealsListing));if(!u||!o||!Array.isArray(o.price_points))return;const c=o.price_points.slice().sort((r,h)=>new Date(r.observed_at)-new Date(h.observed_at)).map(r=>Number(r.price));if(c.length<2)return;const i=c[c.length-1]<c[0]?"down":c[c.length-1]>c[0]?"up":"flat";j(u,c,i)})}).catch(()=>{})})},F=e=>{const t=k(e.image_url),a=k(e.url),s=a?`<a class="dt-cand-name" href="${p(a)}" target="_blank" rel="noopener noreferrer">${p(e.title)}</a>`:`<div class="dt-cand-name">${p(e.title)}</div>`,n=t?`<img src="${p(t)}" alt="${p(e.title)}" loading="lazy">`:y("Box",20,1.5,"ic");return`<div class="dt-cand" data-deals-cand="${p(e.id)}">
    <div class="dt-cand-top">
        <div class="dt-cand-thumb">${n}</div>
        <div class="dt-cand-main">
            ${s}
            <div class="dt-cand-row">
                ${e.current_price!=null?`<span class="dt-cand-price tnum">${C(e.current_price)}</span>`:""}
            </div>
        </div>
    </div>
    <div class="dt-cand-actions">
        <button class="dt-cact confirm" data-deals-confirm>${y("Check",14,1.7)} Confirm</button>
        <button class="dt-cact remove" data-deals-remove>${y("X",14,1.7)} Remove</button>
    </div>
</div>`},X=e=>{const t=Array.isArray(e.listings)?e.listings:[],a=["bol","amazon","tweakers"].map(s=>{const n=t.filter(l=>String(l.retailer).toLowerCase()===s),d=n.length===0?`<div class="dt-cand-none">${y("X",20,1.7,"ic")}<div>No match — nothing will be tracked here.</div></div>`:n.map(F).join(""),v=n.length===1?"candidate":"candidates";return`<div class="dt-storecol">
                <div class="dt-storecol-head ${s}">
                    <span class="led"></span>
                    <span class="dt-storecol-name">${p(R(s))}</span>
                    <span class="dt-storecol-count">${n.length} ${v}</span>
                </div>
                <div class="dt-storecol-body">${d}</div>
            </div>`}).join("");return`<div class="dt-add" data-deals-review="${p(e.id)}">
        <div class="dt-review-head">
            <div class="dt-review-q">
                <span>Results for</span>
                <span class="term">${y("Search",13,1.7,"ic")} ${p(e.name)}</span>
            </div>
        </div>
        <div class="dt-guard">
            ${y("Shield",17,1.7,"ic")}
            <div class="dt-guard-tx">
                Confirm the <b>right</b> match per store and remove wrong results — such as a different
                generation or a loose accessory. <b>Only confirmed products are tracked</b>, so you don't
                end up with the wrong price.
            </div>
        </div>
        <div class="dt-review-grid">${a}</div>
    </div>`},q=(e,t)=>{var s,n;const a=t.dataset.dealsCand;a&&((s=t.querySelector("[data-deals-confirm]"))==null||s.addEventListener("click",async()=>{t.classList.add("confirmed"),await w($(e.dataset.confirmTpl,a)).catch(()=>{}),window.location.reload()}),(n=t.querySelector("[data-deals-remove]"))==null||n.addEventListener("click",async()=>{t.classList.add("removed"),await A($(e.dataset.destroyTpl,a)).catch(()=>{});const d=t.closest(".dt-storecol-body");t.remove(),d&&d.querySelectorAll("[data-deals-cand]").length===0&&(d.innerHTML=`<div class="dt-cand-none">${y("X",20,1.7,"ic")}<div>Geen match — hier wordt niets gevolgd.</div></div>`)}))},S=()=>{var o,c;const e=document.querySelector("[data-deals]");if(!e||e.dataset.dealsReady==="true")return;e.dataset.dealsReady="true";const t=e.querySelector("[data-deals-add]"),a=e.querySelector("[data-deals-add-loading]"),s=e.querySelector("[data-deals-main]"),n=e.querySelector("[data-deals-search-input]"),d=e.querySelector("[data-deals-search-submit]"),v=e.querySelector("[data-deals-search-term]"),l=()=>{t&&(t.hidden=!1),a&&(a.hidden=!0),s&&(s.hidden=!0),n==null||n.focus()},u=()=>{t&&(t.hidden=!0),a&&(a.hidden=!0),s&&(s.hidden=!1)};e.querySelectorAll("[data-deals-add-open]").forEach(i=>i.addEventListener("click",l)),(o=e.querySelector("[data-deals-add-cancel]"))==null||o.addEventListener("click",u),n==null||n.addEventListener("input",()=>{d&&(d.disabled=n.value.trim()==="")}),(c=e.querySelector("[data-deals-search-form]"))==null||c.addEventListener("submit",async i=>{i.preventDefault();const r=((n==null?void 0:n.value)||"").trim();if(r){v&&(v.textContent=r),t&&(t.hidden=!0),a&&(a.hidden=!1);try{const h=await w(e.dataset.storeUrl,{name:r});if(!h.ok)throw new Error("store failed");const m=(await h.json()).product;a&&(a.hidden=!0),s&&(s.hidden=!1,s.insertAdjacentHTML("afterbegin",X(m)),s.querySelectorAll("[data-deals-cand]").forEach(f=>q(e,f)))}catch{window.location.reload()}}}),e.querySelectorAll("[data-deals-cand]").forEach(i=>q(e,i)),e.querySelectorAll("[data-deals-check]").forEach(i=>{i.addEventListener("click",async()=>{var r;e.querySelectorAll("[data-deals-check]").forEach(h=>h.disabled=!0),(r=i.querySelector(".ic"))==null||r.classList.add("spin"),await w(e.dataset.checkUrl).catch(()=>{}),window.location.reload()})}),D(e)};document.readyState==="loading"?document.addEventListener("DOMContentLoaded",S,{once:!0}):S();document.addEventListener("livewire:navigated",S);
