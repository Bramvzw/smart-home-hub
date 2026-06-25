const S=()=>{var e;return((e=document.querySelector('meta[name="csrf-token"]'))==null?void 0:e.content)??""},M=(e,n={})=>fetch(e,{method:"POST",headers:{"Content-Type":"application/json",Accept:"application/json","X-CSRF-TOKEN":S(),"X-Requested-With":"XMLHttpRequest"},body:JSON.stringify(n)}),L=e=>"€ "+Number(e).toLocaleString("nl-NL",{minimumFractionDigits:2,maximumFractionDigits:2}),w={ah:"AH",lidl:"Lidl"},q=e=>w[String(e).toLowerCase()]??String(e).toUpperCase(),$={ArrowL:'<path d="M19 12H5M11 18l-6-6 6-6"/>',Clock:'<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',Users:'<circle cx="9" cy="8" r="3.2"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0"/><path d="M16 5.2a3.2 3.2 0 0 1 0 6M17.5 19a5.5 5.5 0 0 0-2.5-4.6"/>',Euro:'<path d="M16.5 6.5A6 6 0 1 0 16.5 17.5"/><path d="M4 10.5h8M4 13.5h7"/>',List:'<path d="M8 6.5h12M8 12h12M8 17.5h12"/><circle cx="4" cy="6.5" r="1.1" fill="currentColor" stroke="none"/><circle cx="4" cy="12" r="1.1" fill="currentColor" stroke="none"/><circle cx="4" cy="17.5" r="1.1" fill="currentColor" stroke="none"/>',Flame:'<path d="M12 3s5 4.5 5 9a5 5 0 0 1-10 0c0-1.8.8-3 .8-3 .5 1.2 1.7 1.6 1.7 1.6C9 8 12 3 12 3z"/>',Cart:'<circle cx="9.5" cy="20" r="1.4" fill="currentColor" stroke="none"/><circle cx="17.5" cy="20" r="1.4" fill="currentColor" stroke="none"/><path d="M2.5 4h2.2l2.1 11.2a1.5 1.5 0 0 0 1.5 1.3h8.4a1.5 1.5 0 0 0 1.5-1.2L20.5 8H6"/>',CheckSm:'<path d="M4 12l5 5L20 6"/>',Bowl:'<path d="M3 10.5h18a8 8 0 0 1-8 8h-2a8 8 0 0 1-8-8z"/><path d="M9 6.5c0-1.5 1.2-2 1.2-3M13 6.5c0-1.5 1.2-2 1.2-3"/>',Wok:'<path d="M3 11h18a9 9 0 0 1-9 8 9 9 0 0 1-9-8z"/><path d="M21 11l1.5-1.5M3 11 1.5 9.5"/>',Fish:'<path d="M3 12c4-5 11-5 15 0-4 5-11 5-15 0z"/><path d="M18 12c1.5-1.5 3-1.5 3-1.5s0 3-3 3M8.5 11h.01"/>',Pot:'<path d="M4 9h16v5a6 6 0 0 1-6 6h-4a6 6 0 0 1-6-6z"/><path d="M2.5 9h19M7 9 6 5.5M17 9l1-3.5"/>',Leaf:'<path d="M4 20C4 11 11 4 20 4c0 9-7 16-16 16z"/><path d="M4 20c4.5-5 8-7.5 12-9"/>'},l=(e,n=16,r=1.7,o="")=>{const a=$[e]||$.Bowl;return`<svg class="${o}" width="${n}" height="${n}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="${r}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${a}</svg>`},c=e=>String(e??"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;"),b=e=>{if(!e)return"";const n=String(e).toLowerCase();return`<span class="rc-ingr-store ${c(n)}">${c(q(n))}</span>`},C=e=>c(e).replace(/&lt;b&gt;/g,"<b>").replace(/&lt;\/b&gt;/g,"</b>"),E=e=>{const n=e.ingredients||[],r=e.steps||[],o=(e.shopping_list&&e.shopping_list.length?e.shopping_list:n).map((t,s)=>({...t,key:e.id+"-"+s})),a=[...o.filter(t=>t.on_offer),...o.filter(t=>!t.on_offer)],p=e.estimated_cost!==null&&e.estimated_cost!==void 0?`<span class="rc-meta-pill acc tnum">${l("Euro",14,1.7,"ic")} ${L(e.estimated_cost)}</span>`:"",h=n.map(t=>{const s=!!t.on_offer;return`<div class="rc-ingr ${s?"offer":""}">
                <span class="rc-ingr-mark"></span>
                <span class="rc-ingr-name">${c(t.name)}</span>
                ${s?b(t.store):""}
                ${t.amount?`<span class="rc-ingr-qty tnum">${c(t.amount)}</span>`:""}
            </div>`}).join(""),u=r.map((t,s)=>`<div class="rc-step">
                <span class="rc-step-num tnum">${s+1}</span>
                <span class="rc-step-tx">${C(t)}</span>
            </div>`).join(""),v=a.map(t=>`<button class="rc-shop-row" type="button" data-rc-shop="${c(t.key)}">
                <span class="rc-check">${l("CheckSm",14,2.4)}</span>
                <span class="rc-shop-name">${c(t.name)}</span>
                ${t.on_offer?b(t.store):""}
                ${t.amount?`<span class="rc-shop-qty tnum">${c(t.amount)}</span>`:""}
            </button>`).join("");return`<button class="rc-back" type="button" data-rc-back>${l("ArrowL",15)} Terug naar weekmenu</button>

    <div class="rc-detail-head">
        <div class="rc-detail-thumb">${l(e.icon||"Bowl",48,1.5,"ic")}</div>
        <div class="rc-detail-head-b">
            <h1 class="rc-detail-title disp">${c(e.title)}</h1>
            ${e.description?`<div class="rc-detail-desc">${c(e.description)}</div>`:""}
            <div class="rc-detail-meta">
                <span class="rc-meta-pill">${l("Clock",14,1.7,"ic")} ${Number(e.time_minutes)||0} min</span>
                <span class="rc-meta-pill">${l("Users",14,1.7,"ic")} ${Number(e.servings)||0} personen</span>
                ${p}
            </div>
        </div>
    </div>

    <div class="rc-detail-grid">
        <div>
            <div class="rc-panel">
                <div class="rc-panel-head">
                    ${l("List",17,1.7,"ic")}
                    <span class="rc-panel-title">Ingrediënten</span>
                    <span class="rc-panel-count tnum">${n.length}</span>
                </div>
                <div class="rc-ingr-list">${h}</div>
            </div>

            <div class="rc-panel">
                <div class="rc-panel-head">
                    ${l("Flame",17,1.7,"ic")}
                    <span class="rc-panel-title">Bereiding</span>
                    <span class="rc-panel-count tnum">${r.length} stappen</span>
                </div>
                <div class="rc-steps">${u}</div>
            </div>
        </div>

        <div>
            <div class="rc-panel">
                <div class="rc-panel-head">
                    ${l("Cart",17,1.7,"ic")}
                    <span class="rc-panel-title">Boodschappenlijst</span>
                    <span class="rc-panel-count tnum" data-rc-shop-count>0/${a.length}</span>
                </div>
                <div class="rc-shop" data-rc-shop-list>${v}</div>
                <div class="rc-shop-foot">
                    <span class="rc-shop-prog"><b class="tnum" data-rc-shop-done>0</b> van <b class="tnum">${a.length}</b> afgevinkt</span>
                    <button class="rc-shop-clear" type="button" data-rc-shop-clear disabled>Lijst wissen</button>
                </div>
            </div>
        </div>
    </div>`},g=()=>{const e=document.querySelector("[data-recipes]");if(!e||e.dataset.recipesReady==="true")return;e.dataset.recipesReady="true";const n=e.querySelectorAll("[data-rc-tab]"),r={recepten:e.querySelector('[data-rc-panel="recepten"]'),aanbiedingen:e.querySelector('[data-rc-panel="aanbiedingen"]')},o=e.querySelector("[data-rc-overview]"),a=e.querySelector("[data-rc-detail]"),p=()=>{a&&(a.hidden=!0,a.innerHTML="",o&&(o.hidden=!1))},h=()=>{var y;(y=a.querySelector("[data-rc-back]"))==null||y.addEventListener("click",p),a.querySelector("[data-rc-shop-list]");const t=a.querySelector("[data-rc-shop-count]"),s=a.querySelector("[data-rc-shop-done]"),i=a.querySelector("[data-rc-shop-clear]"),m=a.querySelectorAll("[data-rc-shop]"),k=m.length,f=()=>{const d=a.querySelectorAll(".rc-shop-row.done").length;t&&(t.textContent=`${d}/${k}`),s&&(s.textContent=String(d)),i&&(i.disabled=d===0)};m.forEach(d=>d.addEventListener("click",()=>{d.classList.toggle("done"),f()})),i==null||i.addEventListener("click",()=>{m.forEach(d=>d.classList.remove("done")),f()})},u=t=>{a&&(a.innerHTML=E(t),a.hidden=!1,o&&(o.hidden=!0),h(),e.scrollIntoView({block:"start"}))};e.querySelectorAll("[data-rc-recipe]").forEach(t=>{t.addEventListener("click",()=>{const s=t.querySelector("[data-rc-recipe-data]");if(s)try{u(JSON.parse(s.textContent))}catch{}})});const v=t=>{p(),n.forEach(s=>s.classList.toggle("on",s.dataset.rcTab===t)),r.recepten&&(r.recepten.hidden=t!=="recepten"),r.aanbiedingen&&(r.aanbiedingen.hidden=t!=="aanbiedingen")};n.forEach(t=>t.addEventListener("click",()=>v(t.dataset.rcTab))),e.querySelectorAll("[data-rc-generate]").forEach(t=>{t.addEventListener("click",async()=>{var s;e.querySelectorAll("[data-rc-generate]").forEach(i=>i.disabled=!0),(s=e.querySelector("[data-rc-gen]"))==null||s.removeAttribute("hidden"),await M(e.dataset.generateUrl,{week_key:e.dataset.weekKey,refetch:!0}).catch(()=>{}),window.location.reload()})})};document.readyState==="loading"?document.addEventListener("DOMContentLoaded",g,{once:!0}):g();document.addEventListener("livewire:navigated",g);
