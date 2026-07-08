import{S as h}from"./sortable.esm-D-EvzYhP.js";function v({csrf:a,routes:e,store:l,render:n}){const s=(o,i={})=>Object.entries(i).reduce((d,[r,m])=>d.replace(`__${r.toUpperCase()}__`,m),e[o]),t=async(o,i="GET",d=null)=>{const r=await fetch(o,{method:i,headers:{Accept:"application/json","Content-Type":"application/json","X-CSRF-TOKEN":a},body:d?JSON.stringify(d):null});if(!r.ok){const b=await r.json().catch(()=>({}));throw new Error(b.message||"The change could not be saved.")}const m=await r.json();return m.state&&(l.setState(m.state),m.selected_task_id&&l.setSelectedTaskId(m.selected_task_id),n()),m};return{request:t,route:s,persistTask:(o,i={})=>t(s("taskUpdate",{task:o.id}),"PUT",{title:o.title,description:o.description,priority:o.priority,due_date:o.due_date,completed:o.completed,labels:o.labels,checklist:o.checklist,...i}),routes:e}}function T(a,e,l,n){a.addEventListener("click",s=>{var i;const t=(i=s.target.closest("[data-action]"))==null?void 0:i.dataset.action,c=s.target.closest(".task-card"),o=e.selectedTask();if(c&&!t){e.setSelectedTaskId(Number(c.dataset.taskId)),n();return}if(t){if(t==="new-board"){const d=window.prompt("Board name","New board");d&&l.request(l.routes.boardsStore,"POST",{name:d})}if(t==="delete-board"&&window.confirm(`Delete board "${e.board().name}"?`)&&l.request(l.route("boardDestroy",{board:e.board().id}),"DELETE"),t==="new-task"){const d=e.board().columns[0];d&&l.request(l.route("tasksStore",{board:e.board().id}),"POST",{column_id:d.id,title:"New task"})}if(t==="new-column"){const d=window.prompt("Column name","New column");d&&l.request(l.route("columnsStore",{board:e.board().id}),"POST",{name:d})}if(t==="delete-column"){const d=s.target.closest(".task-column").dataset.columnId;window.confirm("Delete this column and its tasks?")&&l.request(l.route("columnDestroy",{column:d}),"DELETE")}if(t==="toggle-task"){const d=c?e.allTasks().find(r=>r.id===Number(c.dataset.taskId)):o;d&&l.persistTask(d,{completed:!d.completed})}if(t==="close-detail"&&(e.setSelectedTaskId(null),n()),t==="clear-filters"&&(e.setFilters({search:"",label:"",priority:"",deadline:"",showArchived:!1}),n()),t==="archive-task"&&o&&l.request(l.route("taskArchive",{task:o.id}),"POST"),t==="delete-task"&&o&&window.confirm("Permanently delete task?")&&l.request(l.route("taskDestroy",{task:o.id}),"DELETE").then(()=>{e.setSelectedTaskId(null)}),s.target.matches("[data-priority]")&&o&&l.persistTask(o,{priority:s.target.dataset.priority}),s.target.matches("[data-label-id]")&&o){const d=Number(s.target.dataset.labelId),m=o.labels.some(b=>b.id===d)?o.labels.filter(b=>b.id!==d):[...o.labels,e.board().labels.find(b=>b.id===d)];l.persistTask(o,{labels:m})}}}),a.addEventListener("submit",s=>{s.preventDefault();const t=s.target,c=t.dataset.action,o=e.selectedTask();if(c==="quick-add"){const i=t.elements.title.value.trim();if(!i)return;l.request(l.route("tasksStore",{board:e.board().id}),"POST",{column_id:t.closest(".task-column").dataset.columnId,title:i})}if(c==="new-label"&&o){const i=t.elements.name.value.trim();if(!i)return;l.persistTask(o,{labels:[...o.labels,{name:i,color:t.elements.color.value}]})}if(c==="new-checklist"&&o){const i=t.elements.text.value.trim();if(!i)return;l.persistTask(o,{checklist:[...o.checklist,{text:i,completed:!1}]})}}),a.addEventListener("change",s=>{const t=e.selectedTask();if(s.target.matches("[data-filter]")){const c=s.target.dataset.filter;e.filters[c]=s.target.type==="checkbox"?s.target.checked:s.target.value,n()}s.target.matches("[data-field]")&&t&&l.persistTask(t,{[s.target.dataset.field]:s.target.value||null})}),a.addEventListener("input",s=>{if(s.target.matches('[data-filter="search"]')){e.filters.search=s.target.value,n();return}const t=e.selectedTask();if(t){if(s.target.matches('[data-field="title"], [data-field="description"]')){clearTimeout(s.target.saveTimer);const c=s.target.dataset.field;s.target.saveTimer=setTimeout(()=>l.persistTask(t,{[c]:s.target.value}),350)}if(s.target.matches('[data-action="edit-checklist"]')){const c=Number(s.target.closest("[data-checklist-index]").dataset.checklistIndex),o=t.checklist.map((i,d)=>d===c?{...i,text:s.target.value}:i);clearTimeout(s.target.saveTimer),s.target.saveTimer=setTimeout(()=>l.persistTask(t,{checklist:o}),350)}}}),a.addEventListener("focusout",s=>{if(s.target.matches('[data-action="rename-board"]')){const t=s.target.value.trim();t&&t!==e.board().name&&l.request(l.route("boardUpdate",{board:e.board().id}),"PUT",{name:t})}if(s.target.matches('[data-action="rename-column"]')){const t=s.target.closest(".task-column").dataset.columnId,c=e.board().columns.find(i=>String(i.id)===String(t)),o=s.target.value.trim();o&&c&&o!==c.name&&l.request(l.route("columnUpdate",{column:t}),"PUT",{name:o})}}),a.addEventListener("keydown",s=>{s.key==="Enter"&&s.target.matches('[data-action="rename-board"], [data-action="rename-column"]')&&s.target.blur()}),a.addEventListener("click",s=>{const t=s.target.closest(".board-switch");if(!t)return;const c=Number(t.dataset.boardId);c===e.state.activeBoardId||!e.state.boards.find(i=>i.id===c)||(window.location.href=`/tasks?board=${c}`)}),a.addEventListener("click",s=>{const t=s.target.dataset.action,c=e.selectedTask();if(c){if(t==="toggle-checklist"){const o=Number(s.target.closest("[data-checklist-index]").dataset.checklistIndex),i=c.checklist.map((d,r)=>r===o?{...d,completed:!d.completed}:d);l.persistTask(c,{checklist:i})}if(t==="delete-checklist"){const o=Number(s.target.closest("[data-checklist-index]").dataset.checklistIndex);l.persistTask(c,{checklist:c.checklist.filter((i,d)=>d!==o)})}}})}const f={blue:{bg:"rgba(107, 134, 240, 0.15)",fg:"#aebcf9",dot:"#6b86f0"},teal:{bg:"rgba(52, 184, 160, 0.15)",fg:"#95e2d3",dot:"#34b8a0"},amber:{bg:"rgba(226, 179, 90, 0.15)",fg:"#f0ce86",dot:"#e2b35a"},red:{bg:"rgba(232, 112, 95, 0.15)",fg:"#f2a397",dot:"#e8705f"},violet:{bg:"rgba(154, 122, 240, 0.16)",fg:"#c6b5fb",dot:"#9a7af0"},slate:{bg:"rgba(140, 144, 158, 0.14)",fg:"#b4b7c4",dot:"#8c909e"}},u=a=>String(a??"").replaceAll("&","&amp;").replaceAll("<","&lt;").replaceAll(">","&gt;").replaceAll('"',"&quot;").replaceAll("'","&#039;"),g=a=>{if(!a)return"";const e=new Date;e.setHours(0,0,0,0);const l=new Date(`${a}T00:00:00`),n=Math.round((l-e)/864e5);return n<0?"overdue":n===0?"today":n<=7?"week":""},$=a=>a?new Intl.DateTimeFormat("en-GB",{day:"2-digit",month:"short"}).format(new Date(`${a}T00:00:00`)):"",w=a=>a.map(e=>{const l=f[e.color]??f.slate;return`<span class="task-label" style="--label-bg:${l.bg};--label-fg:${l.fg};--label-dot:${l.dot}">
        <span></span>${u(e.name)}
    </span>`}).join(""),y=a=>{const e=a.checklist.filter(s=>s.completed).length,l=a.checklist.length,n=g(a.due_date);return`<article class="task-card ${a.completed?"is-completed":""} ${a.archived?"is-archived":""}" data-task-id="${a.id}">
        <span class="priority-bar priority-${a.priority}"></span>
        <div class="task-card-main">
            <button class="task-check" data-action="toggle-task" title="${a.completed?"Mark as open":"Mark as done"}">${a.completed?"✓":""}</button>
            <h3>${u(a.title)}</h3>
        </div>
        ${a.description?`<p>${u(a.description)}</p>`:""}
        ${a.labels.length?`<div class="task-labels">${w(a.labels)}</div>`:""}
        ${a.due_date||l||a.archived?`<div class="task-meta">
            ${a.due_date?`<span class="deadline ${n}">${$(a.due_date)}</span>`:""}
            ${l?`<span>${e}/${l}</span>`:""}
            ${a.archived?"<span>Archived</span>":""}
        </div>`:""}
    </article>`},S=a=>`<aside class="tasks-sidebar">
    <div class="tasks-brand">
        <div class="tasks-mark">T</div>
        <div>
            <strong>Smart Home Hub</strong>
            <span>Local tasks</span>
        </div>
    </div>
    <div class="sidebar-heading">
        <span>Boards</span>
        <button data-action="new-board" title="New board">+</button>
    </div>
    <nav class="board-list">
        ${a.state.boards.map(e=>`<button class="board-switch ${e.id===a.state.activeBoardId?"active":""}" data-board-id="${e.id}">
            <span>${u(e.name)}</span>
            <small>${e.count}</small>
        </button>`).join("")}
    </nav>
</aside>`,I=a=>{const e=a.board().labels,l=a.allTasks().filter(t=>!t.archived).length,n=a.allTasks().filter(a.matchesFilters).length,s=a.filters;return`<header class="tasks-toolbar">
        <div class="toolbar-primary">
            <input class="board-title-input" value="${u(a.board().name)}" data-action="rename-board" aria-label="Board name">
            <span>${n}/${l} visible</span>
            <button class="toolbar-icon danger" data-action="delete-board" title="Delete board">Delete board</button>
            <button class="primary-action" data-action="new-task">+ New task</button>
        </div>
        <div class="toolbar-filters">
            <input class="search-input" placeholder="Search..." value="${u(s.search)}" data-filter="search">
            <select data-filter="label">
                <option value="">All labels</option>
                ${e.map(t=>`<option value="${t.id}" ${String(t.id)===s.label?"selected":""}>${u(t.name)}</option>`).join("")}
            </select>
            <select data-filter="priority">
                <option value="">All priorities</option>
                <option value="high" ${s.priority==="high"?"selected":""}>High</option>
                <option value="normal" ${s.priority==="normal"?"selected":""}>Normal</option>
                <option value="low" ${s.priority==="low"?"selected":""}>Low</option>
            </select>
            <select data-filter="deadline">
                <option value="">All due dates</option>
                <option value="overdue" ${s.deadline==="overdue"?"selected":""}>Overdue</option>
                <option value="today" ${s.deadline==="today"?"selected":""}>Today</option>
                <option value="week" ${s.deadline==="week"?"selected":""}>This week</option>
            </select>
            <label class="archive-toggle">
                <input type="checkbox" data-filter="showArchived" ${s.showArchived?"checked":""}>
                Archive
            </label>
            <button class="clear-filters" data-action="clear-filters">Clear</button>
        </div>
    </header>`},A=a=>`<main class="tasks-board" data-sortable-columns>
    ${a.board().columns.map(e=>{const l=e.tasks.filter(a.matchesFilters);return`<section class="task-column ${e.name.toLowerCase()==="done"?"done-column":""}" data-column-id="${e.id}">
            <header class="column-header">
                <span class="column-grip"></span>
                <input class="column-name-input" value="${u(e.name)}" data-action="rename-column">
                <small>${l.length}</small>
                <button data-action="delete-column" title="Delete column">×</button>
            </header>
            <div class="task-list" data-sortable-tasks data-column-id="${e.id}">
                ${l.map(y).join("")}
                ${l.length?"":'<div class="column-empty">No tasks</div>'}
            </div>
            <form class="quick-add" data-action="quick-add">
                <input name="title" placeholder="Add task...">
                <button>+</button>
            </form>
        </section>`}).join("")}
    <button class="add-column" data-action="new-column">+ New column</button>
</main>`,E=a=>{const e=a.selectedTask();if(!e)return'<aside class="task-detail"></aside>';const l=new Set(e.labels.map(t=>String(t.id))),n=e.checklist.filter(t=>t.completed).length,s=e.created_at?$(e.created_at):"";return`<aside class="task-detail open">
        <div class="detail-head">
            <span class="priority-pill priority-${e.priority}">${e.priority==="high"?"High":e.priority==="low"?"Low":"Normal"}</span>
            ${e.completed?'<span class="done-pill">Done</span>':""}
            <button data-action="close-detail">×</button>
        </div>
        <div class="detail-body">
            <label class="detail-title">
                <button class="task-check large" data-action="toggle-task">${e.completed?"✓":""}</button>
                <textarea data-field="title" rows="1">${u(e.title)}</textarea>
            </label>
            <div class="field">
                <label>Priority</label>
                <div class="priority-segment">
                    ${["high","normal","low"].map(t=>`<button class="${e.priority===t?"active ":""}priority-${t}" data-priority="${t}">${t==="high"?"High":t==="low"?"Low":"Normal"}</button>`).join("")}
                </div>
            </div>
            <div class="field">
                <label>Due date</label>
                <input type="date" data-field="due_date" value="${u(e.due_date??"")}">
            </div>
            <div class="field">
                <label>Labels</label>
                <div class="label-pool">
                    ${a.board().labels.map(t=>{const c=f[t.color]??f.slate;return`<button class="${l.has(String(t.id))?"active":""}" data-label-id="${t.id}" style="--label-dot:${c.dot}">${u(t.name)}</button>`}).join("")}
                </div>
                <form class="new-label" data-action="new-label">
                    <input name="name" placeholder="New label">
                    <select name="color">
                        ${Object.keys(f).map(t=>`<option value="${t}">${t}</option>`).join("")}
                    </select>
                    <button>Create</button>
                </form>
            </div>
            <div class="field">
                <label>Description</label>
                <textarea class="description" data-field="description">${u(e.description)}</textarea>
            </div>
            <div class="field">
                <label>Checklist ${e.checklist.length?`<span>${n}/${e.checklist.length}</span>`:""}</label>
                <div class="checklist">
                    ${e.checklist.map((t,c)=>`<div class="checklist-item" data-checklist-index="${c}">
                        <button data-action="toggle-checklist">${t.completed?"✓":""}</button>
                        <input value="${u(t.text)}" data-action="edit-checklist" class="${t.completed?"done":""}">
                        <button class="muted-danger" data-action="delete-checklist">×</button>
                    </div>`).join("")}
                </div>
                <form class="new-checklist" data-action="new-checklist">
                    <input name="text" placeholder="Add item...">
                </form>
            </div>
        </div>
        <footer class="detail-foot">
            <button data-action="archive-task">${e.archived?"Restore":"Archive"}</button>
            <button class="danger" data-action="delete-task">Delete</button>
            <small>${s?`Created ${s}`:""}</small>
        </footer>
    </aside>`},_=a=>`<div class="tasks-shell ${a.selectedTask()?"detail-open":""}">
    ${S(a)}
    <section class="tasks-main">
        ${I(a)}
        <div class="board-scroll">${A(a)}</div>
    </section>
    ${E(a)}
</div>`;function L(a,e,l){const n=a.querySelector("[data-sortable-columns]");n&&h.create(n,{animation:160,draggable:".task-column",handle:".column-grip",ghostClass:"drag-ghost",onEnd:()=>{const s=[...a.querySelectorAll(".task-column")].map(t=>t.dataset.columnId);l.request(l.route("columnsReorder",{board:e.board().id}),"POST",{column_ids:s})}}),a.querySelectorAll("[data-sortable-tasks]").forEach(s=>{h.create(s,{group:"tasks",animation:150,draggable:".task-card",ghostClass:"drag-ghost",onEnd:t=>{const c=t.item.dataset.taskId,o=t.to.dataset.columnId,i=[...t.to.querySelectorAll(".task-card")].map(d=>d.dataset.taskId);l.request(l.route("taskMove",{task:c}),"PUT",{column_id:o,task_ids:i})}})})}function D(a){let e=a,l=null,n={search:"",label:"",priority:"",deadline:"",showArchived:!1};const s=()=>e.board,t=()=>s().columns.flatMap(i=>i.tasks.map(d=>({...d,column_id:i.id})));return{get state(){return e},setState(i){e=i},get selectedTaskId(){return l},setSelectedTaskId(i){l=i},get filters(){return n},setFilters(i){n=i},board:s,allTasks:t,selectedTask:()=>t().find(i=>i.id===l)??null,matchesFilters:i=>{if(!n.showArchived&&i.archived)return!1;const d=n.search.trim().toLowerCase();if(d&&!`${i.title} ${i.description}`.toLowerCase().includes(d)||n.label&&!i.labels.some(r=>String(r.id)===n.label)||n.priority&&i.priority!==n.priority)return!1;if(n.deadline){const r=g(i.due_date);return n.deadline==="week"?r==="today"||r==="week":r===n.deadline}return!0}}}const p=document.getElementById("tasks-app");var k;if(p){const a=D(JSON.parse(p.dataset.state));let e;const l=()=>{p.innerHTML=_(a),L(p,a,e)};e=v({csrf:((k=document.querySelector('meta[name="csrf-token"]'))==null?void 0:k.content)??"",routes:JSON.parse(p.dataset.routes),store:a,render:l}),T(p,a,e,l),l()}
