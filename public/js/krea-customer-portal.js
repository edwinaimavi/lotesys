(() => {
    'use strict';
    const form = document.getElementById('customer-portal-login');
    if (!form) return;
    const $ = id => document.getElementById(id);
    const passwordToggle = $('portal-password-toggle');
    if (passwordToggle) {
        const password = $('portal-password');
        const setPasswordVisible = visible => {
            password.type = visible ? 'text' : 'password';
            passwordToggle.setAttribute('aria-pressed',String(visible));
            passwordToggle.setAttribute('aria-label',visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
        };
        passwordToggle.hidden = false;
        passwordToggle.addEventListener('click',() => setPasswordVisible(password.type === 'password'));
        form.addEventListener('reset',() => setPasswordVisible(false));
    }
    const dialog = $('customer-portal'), base = form.dataset.base;
    let csrf = form.elements._token.value, sequence = 0;
    const money = n => new Intl.NumberFormat('es-PE', {style:'currency', currency:'PEN'}).format(n);
    const date = value => value ? String(value).slice(0,10).split('-').reverse().join('/') : '—';
    const el = (tag, text, cls) => { const node = document.createElement(tag); if (text != null) node.textContent = text; if (cls) node.className = cls; return node; };
    function clear() { sequence++; ['summary','schedules','payments','documents'].forEach(k => $('cp-'+k).replaceChildren()); $('cp-operation').replaceChildren(); $('cp-pdf').hidden = true; $('cp-pdf').removeAttribute('href'); $('cp-content').hidden = true; $('cp-content').removeAttribute('aria-busy'); $('cp-message').classList.remove('cp-loading'); $('cp-password').hidden = true; $('cp-title').textContent = 'Bienvenido a tu portal'; }
    function signedOut() { clear(); form.hidden = false; $('portal-reopen').hidden = true; form.reset(); $('cp-password').reset(); }
    async function api(path, data, quiet = false) {
        const response = await fetch(base + path, {method: data ? 'POST' : 'GET', credentials:'same-origin', cache:'no-store', headers:{Accept:'application/json', ...(data ? {'Content-Type':'application/json','X-CSRF-TOKEN':csrf} : {})}, ...(data ? {body:JSON.stringify({...data, _token:csrf})} : {})});
        const result = await response.json().catch(() => ({}));
        if (result.csrf_token) { csrf = result.csrf_token; form.elements._token.value = csrf; }
        if (response.status === 401) { if (!quiet) { signedOut(); dialog.close(); } throw new Error('Tu sesión ha finalizado. Ingresa nuevamente.'); }
        if (!response.ok) throw new Error(response.status >= 500 ? 'No pudimos cargar la información. Intenta nuevamente.' : response.status === 429 ? 'Has realizado varios intentos. Espera un minuto y vuelve a intentar.' : response.status === 419 ? 'Actualiza la página e ingresa nuevamente.' : result.message || 'No pudimos cargar la información. Intenta nuevamente.');
        return result;
    }
    function open() { if (!dialog.open) dialog.showModal(); document.body.classList.add('cp-open'); }
    // Decorative SVGs use static paths; customer content is always textContent.
    function icon(name) {
        const paths = {summary:'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z',schedules:'M4 5h16v16H4zM8 3v4m8-4v4M4 10h16',payments:'M3 6h18v14H3zM3 10h18m-5 5h2',documents:'M6 3h8l4 4v14H6zM14 3v5h4M9 12h6m-6 4h6',check:'m5 12 4 4L19 6',info:'M12 11v6m0-10v1M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0'};
        const svg = document.createElementNS('http://www.w3.org/2000/svg','svg'), path = document.createElementNS('http://www.w3.org/2000/svg','path');
        Object.entries({viewBox:'0 0 24 24',fill:'none',stroke:'currentColor','stroke-width':'1.6','stroke-linecap':'round','stroke-linejoin':'round','aria-hidden':'true',class:'cp-icon'}).forEach(([key,value]) => svg.setAttribute(key,value));
        path.setAttribute('d',paths[name] || paths.documents); svg.append(path); return svg;
    }
    function emptyState(title, description, type) {
        const box = el('div',null,'cp-empty'); box.append(icon(type),el('h3',title),el('p',description)); return box;
    }
    function badge(value) {
        const text = String(value ?? '—'), state = text.toLowerCase();
        const tone = state.includes('vencid') ? 'danger' : ['pagado','pagada','activo','activa'].includes(state) ? 'success' : ['pendiente','parcial'].includes(state) ? 'warning' : ['anulado','anulada'].includes(state) ? 'muted' : '';
        return el('span',text,'cp-badge'+(tone ? ' cp-badge--'+tone : ''));
    }
    function panelHeading(title, description) {
        const heading = el('div',null,'cp-panel-heading');
        heading.append(el('h3',title),el('p',description)); return heading;
    }
    function table(id, headings, rows, nextIndex = -1) {
        const schedules = id === 'cp-schedules';
        const options = schedules ? {title:'Tu cronograma',description:'Consulta el vencimiento, los pagos y el saldo de cada cuota.',money:[2,3,4],status:5,identity:0} : {title:'Tus pagos',description:'Revisa el detalle de los pagos registrados en tu operación.',money:[2],status:4,identity:1};
        const wrap = el('div',null,'cp-table-wrap'), table = el('table'), head = el('thead'), tr = el('tr');
        table.setAttribute('aria-label',options.title);
        headings.forEach((t,i) => { const th = el('th',t,options.money.includes(i) ? 'cp-money' : null); th.scope = 'col'; tr.append(th); });
        head.append(tr); table.append(head);
        const body = el('tbody'), cards = el('div',null,'cp-mobile-list');
        rows.forEach((row,index) => {
            const tr = el('tr');
            row.forEach((value,i) => { const td = el('td',null,options.money.includes(i) ? 'cp-money' : null); td.append(i === options.status ? badge(value) : document.createTextNode(value ?? '—')); tr.append(td); });
            if (index === nextIndex) { tr.className = 'cp-next-row'; tr.children[0].append(el('span','Próxima','cp-next-marker')); }
            body.append(tr);
            const card = el('article',null,'cp-mobile-card'), header = el('div',null,'cp-mobile-card-header'), facts = el('dl');
            header.append(el('strong',row[options.identity]),badge(row[options.status]));
            row.forEach((value,i) => { if (i === options.identity || i === options.status) return; const fact = el('div'); fact.append(el('dt',headings[i]),el('dd',value ?? '—')); facts.append(fact); });
            if (index === nextIndex) { card.className += ' cp-next-row'; header.children[0].append(el('span','Próxima','cp-next-marker')); }
            card.append(header,facts); cards.append(card);
        });
        table.append(body); wrap.append(table);
        $(id).replaceChildren(panelHeading(options.title,options.description));
        $(id).append(...(rows.length ? [wrap,cards] : [emptyState(schedules ? 'Sin cuotas disponibles' : 'Aún no hay pagos registrados','Aquí encontrarás los registros de esta operación.',schedules ? 'schedules' : 'payments')]));
    }
    function render(data) {
        const summary = $('cp-summary'), grid = el('div',null,'cp-grid');
        [['Monto de compra',money(data.summary.total)],['Total pagado',money(data.summary.paid)],['Saldo de cuotas',money(data.summary.balance)],['Próxima cuota',data.summary.next ? money(data.summary.next.balance) : 'Sin pendientes']].forEach(([title,value]) => { const card = el('div',null,'cp-card'); card.append(el('span',title),el('strong',value)); grid.append(card); });
        const metricIcons = ['summary','check','payments','schedules'], hints = ['Valor de tu compra','Pago acumulado','Saldo por cubrir','Siguiente pago pendiente'];
        [...grid.children].forEach((card,index) => { card.prepend(icon(metricIcons[index])); card.append(el('small',hints[index])); });
        const lots = el('details',null,'cp-lots'), lotsTitle = el('summary');
        lotsTitle.append(el('span','Lotes de esta operación'),el('span',data.lots.length+(data.lots.length === 1 ? ' lote' : ' lotes'),'cp-lot-count'));
        lots.append(lotsTitle); data.lots.forEach(lot => lots.append(el('p',`Mz ${lot.block || '—'} · Lote ${lot.number}`)));
        const heading = el('div',null,'cp-summary-heading'), metadata = el('p');
        metadata.append(el('span','Compra: '+date(data.date)),badge(data.status));
        heading.append(el('h3',data.project+' · '+data.code),metadata);
        const details = el('div',null,'cp-summary-details');
        const counts = el('dl',null,'cp-counts');
        [['Cuotas',data.summary.installments],['Pagadas',data.summary.paid_installments],['Pendientes',data.summary.pending_installments]].forEach(([label,value]) => {
            const item = el('div'); item.append(el('dt',label),el('dd',String(value))); counts.append(item);
        });
        const due = el('div',null,'cp-next-due');
        due.append(el('span','Próximo vencimiento'),el('strong',date(data.summary.next?.due_date)));
        if (data.summary.next) due.append(el('small',money(data.summary.next.balance)+' de saldo en esta cuota'));
        details.append(counts,due);
        const activity = el('section',null,'cp-activity'); activity.append(el('h4','Estado del cronograma'),details);
        const note = el('aside',null,'cp-note'), noteCopy = el('div');
        noteCopy.append(el('strong','Sobre tu saldo'),el('p','Saldo de cuotas con mora según las reglas vigentes y aplicaciones registradas. Pagos anulados no integran el total pagado.')); note.append(icon('info'),noteCopy);
        summary.replaceChildren(heading,lots,grid);
        // Display-only ratio. The bar is bounded, the label preserves the actual ratio.
        if (Number(data.summary.total) > 0) {
            const percentage = Number(data.summary.paid) / Number(data.summary.total) * 100;
            if (Number.isFinite(percentage)) {
                const progress = el('section',null,'cp-progress'), top = el('div'), bar = el('progress');
                const label = new Intl.NumberFormat('es-PE',{maximumFractionDigits:1}).format(percentage)+'% pagado';
                top.append(el('h4','Tu avance'),el('strong',label));
                bar.max = 100; bar.value = Math.max(0,Math.min(100,percentage)); bar.setAttribute('aria-label','Tu avance'); bar.setAttribute('aria-valuetext',label);
                progress.append(top,bar,el('p',money(data.summary.paid)+' abonados de '+money(data.summary.total))); summary.append(progress);
            }
        }
        summary.append(activity,note);
        const nextIndex = data.summary.next ? data.schedules.findIndex(r => r.number === data.summary.next.number && r.due_date === data.summary.next.due_date) : -1;
        table('cp-schedules',['N°','Vencimiento','Monto','Pagado','Saldo','Estado'],data.schedules.map(r => [`${r.number} · ${r.label}`,date(r.due_date),money(r.amount),money(r.paid),money(r.balance),r.status + (r.overdue ? ' · Vencida' : '')]),nextIndex);
        table('cp-payments',['Fecha','Código','Monto','Medio','Estado','Concepto'],data.payments.map(r => [date(r.date),'#'+r.id,money(r.amount),r.method,r.status,r.concept]));
        $('cp-documents').replaceChildren(panelHeading('Tus comprobantes','Consulta y descarga los documentos de tu operación.'));
        data.documents.forEach(doc => {
            const row = el('div',null,'cp-doc'), info = el('div',null,'cp-doc-info'), actions = el('div',null,'cp-doc-actions');
            info.append(el('span',doc.label)); if (doc.status) info.append(badge(doc.status));
            ['Ver','Descargar'].forEach((label,i) => { const a = el('a',label); a.href = doc.url+(i ? '?download=1' : ''); a.target = '_blank'; a.rel = 'noopener noreferrer'; a.setAttribute('aria-label',label+' '+doc.label+' (nueva pestaña)'); actions.append(a); });
            row.append(icon('documents'),info,actions); $('cp-documents').append(row);
        });
        if (!data.documents.length) $('cp-documents').append(emptyState('Aún no tienes comprobantes disponibles','Los documentos de esta operación aparecerán aquí.','documents'));
        $('cp-pdf').href = data.statement_url; $('cp-pdf').hidden = false;
    }
    async function operation() {
        const current = ++sequence, id = $('cp-operation').value;
        ['summary','schedules','payments','documents'].forEach(k => $('cp-'+k).replaceChildren()); $('cp-pdf').hidden = true;
        if (!id) return;
        $('cp-message').textContent = 'Cargando tu operación…';
        $('cp-message').classList.add('cp-loading'); $('cp-content').setAttribute('aria-busy','true');
        try { const data = await api('/ventas/'+encodeURIComponent(id)); if (current !== sequence) return; render(data); $('cp-message').textContent = ''; }
        catch(error) { if (current === sequence) $('cp-message').textContent = error.message; $('portal-login-message').textContent = error.message; }
        finally { if (current === sequence) { $('cp-message').classList.remove('cp-loading'); $('cp-content').removeAttribute('aria-busy'); } }
    }
    async function enter(state) {
        clear(); const current = sequence; form.hidden = true; $('portal-reopen').hidden = false; $('cp-message').textContent = ''; open();
        if (state.must_change_password) { $('cp-password').hidden = false; $('cp-new-password').focus(); return; }
        const data = await api('/me'); if (current !== sequence) return;
        // Presentation only: preserve mixed-case names and the original response.
        const displayName = data.name === data.name.toLocaleUpperCase('es-PE') ? data.name.toLocaleLowerCase('es-PE').replace(/(^|[\s-])\p{L}/gu,letter => letter.toLocaleUpperCase('es-PE')) : data.name;
        $('cp-title').textContent = 'Hola, '+displayName; $('cp-content').hidden = false;
        data.operations.forEach(op => { const option = el('option',`${op.code} · ${op.project} · ${op.lots.length === 1 ? 'Mz '+(op.lots[0].block || '—')+' Lote '+op.lots[0].number : op.lots.length+' lotes'}`); option.value = op.id; $('cp-operation').append(option); });
        if (!data.operations.length) $('cp-message').textContent = 'No encontramos operaciones disponibles para esta cuenta.';
        else await operation();
    }
    async function submit(event, path, callback, message) { event.preventDefault(); const target = event.currentTarget, button = target.querySelector('[type=submit]'); button.disabled = true; $(message).textContent = ''; try { const result = await api(path,Object.fromEntries(new FormData(target))); target.reset(); await callback(result); } catch(error) { $(message).textContent = error.message; } finally { button.disabled = false; } }
    form.addEventListener('submit',event => submit(event,'/login',enter,'portal-login-message'));
    $('cp-password').addEventListener('submit',event => submit(event,'/change-password',enter,'cp-message'));
    $('cp-operation').addEventListener('change',operation);
    $('cp-close').addEventListener('click',() => dialog.close());
    dialog.addEventListener('close',() => { document.body.classList.remove('cp-open'); ($('portal-reopen').hidden ? $('portal-dni') : $('portal-reopen')).focus({preventScroll:true}); });
    $('portal-reopen').addEventListener('click',async () => { try { await enter(await api('/session')); } catch(error) { $('portal-login-message').textContent = error.message; } });
    $('cp-logout').addEventListener('click',async () => { try { await api('/logout',{}); signedOut(); dialog.close(); } catch(error) { $('cp-message').textContent = error.message; } });
    const tabs = [...dialog.querySelectorAll('[role=tab]')];
    tabs.forEach(tab => { tab.prepend(icon(tab.dataset.panel)); tab.addEventListener('click',() => { dialog.querySelector('.cp-body').scrollTop = 0; }); });
    tabs.forEach((tab,index) => { tab.addEventListener('click',() => tabs.forEach(other => { const active = other === tab; other.setAttribute('aria-selected',String(active)); other.tabIndex = active ? 0 : -1; $('cp-'+other.dataset.panel).hidden = !active; })); tab.addEventListener('keydown',event => { let next; if (event.key === 'ArrowRight') next = (index+1)%tabs.length; if (event.key === 'ArrowLeft') next = (index+tabs.length-1)%tabs.length; if (event.key === 'Home') next = 0; if (event.key === 'End') next = tabs.length-1; if (next !== undefined) { event.preventDefault(); tabs[next].click(); tabs[next].focus(); } }); });
    window.addEventListener('pageshow',event => { if (event.persisted) { signedOut(); dialog.close(); } });
    const initialSequence = sequence;
    api('/session', undefined, true).then(() => { if (initialSequence === sequence) { form.hidden = true; $('portal-reopen').hidden = false; } }).catch(() => {});
})();
