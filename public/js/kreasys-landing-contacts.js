(() => {
    'use strict';
    const root = document.getElementById('contact-module');
    if (!root) return;
    const form = document.getElementById('contact-form'), modal = window.jQuery('#contact-modal');
    const rows = document.getElementById('contact-rows'), errorBox = document.getElementById('contact-error');
    const save = document.getElementById('contact-save'), base = root.dataset.base;
    const types = {whatsapp:'WhatsApp',phone:'Teléfono',email:'Email',address:'Dirección',social:'Red social'};
    const booleans = ['is_primary','use_for_cta','show_in_footer','is_active'];
    let items = JSON.parse(document.getElementById('contact-initial').textContent), editing = null, busy = false, readOnly = false;
    const node = (tag,text,cls) => { const e = document.createElement(tag); if (text != null) e.textContent = text; if (cls) e.className = cls; return e; };
    const notify = (text,icon = 'success') => window.Swal ? window.Swal.fire({text,icon}) : window.alert(text);
    async function request(path,method='GET',data) {
        const response = await fetch(base+path,{method,credentials:'same-origin',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':form.elements._token.value},...(data ? {body:JSON.stringify(data)} : {})});
        const body = await response.json().catch(() => ({}));
        if (!response.ok) throw Object.assign(new Error(body.message || 'No se pudo completar la solicitud.'),{errors:body.errors || {}});
        return body;
    }
    function render() {
        rows.replaceChildren();
        items.sort((a,b) => a.sort_order-b.sort_order || a.id-b.id).forEach(item => {
            const tr = node('tr');
            [item.sort_order,types[item.type],item.label || '—',item.value].forEach(value => tr.append(node('td',value)));
            const usage = node('td');
            [['use_for_cta','CTA'],['is_primary','Principal'],['show_in_footer','Footer']].forEach(([key,label]) => { if (item[key]) usage.append(node('span',label,'badge badge-light')); });
            const status = node('td'); status.append(node('span',item.is_active ? 'Activo' : 'Inactivo','badge badge-'+(item.is_active ? 'success' : 'secondary')));
            const actions = node('td', null, 'lc-actions-cell');
            const actionGroup = node('div', null, 'lc-actions');
            [
                ['show', 'Ver', 'info', 'fa-eye'],
                ['update', 'Editar', 'warning', 'fa-edit'],
                ['toggle', item.is_active ? 'Desactivar' : 'Activar', item.is_active ? 'danger' : 'success', item.is_active ? 'fa-toggle-off' : 'fa-toggle-on'],
            ].forEach(([action,label,tone,icon]) => {
                if (root.dataset[action] !== '1') return;
                const button = node('button', null, `btn btn-sm btn-${tone} lc-action-btn`);
                button.type = 'button';
                button.dataset.action = action;
                button.dataset.id = item.id;
                button.title = label;
                button.setAttribute('aria-label', label + ' contacto');
                const iconNode = node('i', null, `fas ${icon}`);
                iconNode.setAttribute('aria-hidden', 'true');
                button.append(iconNode, node('span', label));
                actionGroup.append(button);
            });
            actions.append(actionGroup);
            tr.append(usage,status,actions); rows.append(tr);
        });
        if (!items.length) { const tr=node('tr'),td=node('td','Aún no hay contactos. La web utiliza la configuración de respaldo.','text-center text-muted py-5'); td.colSpan=7;tr.append(td);rows.append(tr); }
        document.getElementById('lc-active').textContent=items.filter(i=>i.is_active).length;
        document.getElementById('lc-social').textContent=items.filter(i=>i.is_active&&i.type==='social').length;
        document.getElementById('lc-cta').textContent=items.find(i=>i.is_active&&i.use_for_cta)?.value || 'Configuración de respaldo';
    }
    function fields() {
        const type=form.elements.type.value;
        const shown={value:type!=='social',network:type==='social',url:['social','address'].includes(type),use_for_cta:type==='whatsapp',is_primary:['phone','whatsapp','email'].includes(type)};
        Object.entries(shown).forEach(([name,visible])=>{const wrap=form.querySelector(`[data-field="${name}"]`);wrap.hidden=!visible;form.elements[name].disabled=!visible||readOnly;});
        document.getElementById('lc-value-label').textContent=type==='address'?'Dirección':type==='email'?'Email':'Número';
        document.getElementById('lc-value-help').hidden=!['phone','whatsapp'].includes(type);
        document.getElementById('lc-url-label').textContent=type==='address'?'URL de mapa (opcional)':'URL de la red social (HTTPS)';
        form.elements.value.required=type!=='social';form.elements.url.required=type==='social';
    }
    function clearErrors(){errorBox.hidden=true;form.querySelectorAll('[data-error]').forEach(e=>e.textContent='');form.querySelectorAll('.is-invalid').forEach(e=>e.classList.remove('is-invalid'));}
    function open(item=null,view=false) {
        editing=item?.id||null;readOnly=view;form.reset();clearErrors();
        [...form.elements].forEach(e=>{if(e.name&&e.name!=='_token')e.disabled=view;});
        if(item) Object.entries(item).forEach(([key,value])=>{const input=form.elements[key];if(input){if(input.type==='checkbox')input.checked=Boolean(value);else input.value=value??'';}});
        document.getElementById('contact-modal-title').textContent=view?'Detalle del contacto':item?'Editar contacto':'Agregar contacto';save.hidden=view;fields();modal.modal('show');
    }
    document.getElementById('contact-add')?.addEventListener('click',()=>open());
    form.elements.type.addEventListener('change',fields);
    modal.on('shown.bs.modal',()=>{if(!readOnly)form.elements.type.focus();});
    modal.on('hide.bs.modal',event=>{if(busy)event.preventDefault();});
    rows.addEventListener('click',async event=>{
        const button=event.target.closest('[data-action]');if(!button||busy)return;
        button.disabled=true;
        try {
            const id=button.dataset.id;
            if(button.dataset.action==='toggle'){
                const answer=window.Swal ? await window.Swal.fire({title:button.textContent+' contacto',text:'La publicación del contacto cambiará en la web.',icon:'question',showCancelButton:true,confirmButtonText:'Confirmar',cancelButtonText:'Cancelar'}) : {isConfirmed:window.confirm(button.textContent+' contacto?')};
                if(!answer.isConfirmed)return;
                const result=await request('/'+id+'/toggle','PATCH');items=items.map(i=>i.id===result.data.id?result.data:i);render();notify(result.message);
            } else {const result=await request('/'+id);open(result.data,button.dataset.action==='show');}
        } catch(error){notify(error.message,'error');}finally{button.disabled=false;}
    });
    form.addEventListener('submit',async event=>{
        event.preventDefault();if(busy||readOnly)return;busy=true;save.disabled=true;clearErrors();
        const data=Object.fromEntries(new FormData(form));booleans.forEach(key=>data[key]=!form.elements[key].disabled&&form.elements[key].checked);
        try {
            const result=await request(editing?'/'+editing:'',editing?'PUT':'POST',data);
            // Reflect transactional demotion without a second request or a page reload.
            items=items.filter(i=>i.id!==result.data.id).map(i=>({...i,use_for_cta:result.data.use_for_cta?false:i.use_for_cta,is_primary:result.data.is_primary&&i.type===result.data.type?false:i.is_primary}));items.push(result.data);render();busy=false;modal.modal('hide');notify(result.message);
        }catch(error){errorBox.textContent=error.message;errorBox.hidden=false;Object.entries(error.errors||{}).forEach(([key,messages])=>{form.elements[key]?.classList.add('is-invalid');const feedback=form.querySelector(`[data-error="${key}"]`);if(feedback)feedback.textContent=messages.join(' ');});form.querySelector('.is-invalid')?.focus();}
        finally{busy=false;save.disabled=false;}
    });
    render();
})();
