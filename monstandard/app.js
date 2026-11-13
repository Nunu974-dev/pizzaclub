'use strict';
/* PROTOTYPE — données de démonstration uniquement.
   Aucune requête réseau n'est faite : toute "application" d'un changement est SIMULÉE. */
const TZ = 'Indian/Reunion';
const DAYS = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
const ORDER = [1,2,3,4,5,6,0];
const KEY = 'pizzaclub-demo-v1';

const DEFAULTS = {
  forward: '0692 12 34 56',
  greeting: "Bonjour et bienvenue chez Pizza Club ! Nous prenons votre commande : un instant, un membre de l'équipe va vous répondre.",
  closed: "Pizza Club est actuellement fermé. Nous vous accueillons du mardi au dimanche, de 11h à 14h et de 18h à 22h. À bientôt !",
  hours: {
    0:[['18:00','22:00']],1:[],
    2:[['11:00','14:00'],['18:00','22:00']],3:[['11:00','14:00'],['18:00','22:00']],
    4:[['11:00','14:00'],['18:00','22:00']],5:[['11:00','14:00'],['18:00','23:00']],
    6:[['11:00','14:00'],['18:00','23:00']]
  },
  closures: [{date:'2026-12-25', reason:'Noël'}],
  history: []
};
let S;
try { S = JSON.parse(localStorage.getItem(KEY)) || null; } catch (e) { S = null; }
if (!S) S = structuredClone(DEFAULTS);
const save = () => { try { localStorage.setItem(KEY, JSON.stringify(S)); } catch (e) {} };

const $ = s => document.querySelector(s);
const esc = t => String(t).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
let tab = 'home', screen = null, draft = null, failNext = false, live = false;
async function api(path, opt) {
  const r = await fetch('api.php?r=' + path.replace(/^api\//, ''), {credentials:'same-origin', headers:{'content-type':'application/json'}, ...opt});
  const j = await r.json().catch(() => ({}));
  if (!r.ok) throw Object.assign(new Error(j.error || 'Erreur'), {code:r.status});
  return j;
}
function setBanner() {
  const b = $('#banner');
  b.classList.toggle('live', live);
  b.textContent = live ? '🟢 CONNECTÉ À STANDARDFACILE — les changements sont réels' : '🧪 MODE DÉMO — rien n\'est envoyé à StandardFacile';
}
async function loadLive() {
  const st = await api('api/state');
  S = {...S, forward: st.forward, greeting: st.greeting, closed: st.closed, hours: st.hours, closures: [], status: st.status};
  S.history = await api('api/history');
  live = true; setBanner();
}
async function boot() {
  try {
    const p = await api('api/ping');
    if (!p.authed) { screen = 'login'; render(); return; }
    await loadLive();
  } catch (e) { if (e.code === 401) { screen = 'login'; } }
  render();
}

/* ---------- temps ---------- */
function now() {
  const p = Object.fromEntries(new Intl.DateTimeFormat('en-CA', {timeZone: TZ, hourCycle:'h23',
    year:'numeric', month:'2-digit', day:'2-digit', hour:'2-digit', minute:'2-digit', weekday:'short'})
    .formatToParts(new Date()).map(x => [x.type, x.value]));
  return {date:`${p.year}-${p.month}-${p.day}`, time:`${p.hour}:${p.minute}`,
    dow:['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].indexOf(p.weekday)};
}
const addDays = (iso, n) => { const d = new Date(iso + 'T12:00:00Z'); d.setUTCDate(d.getUTCDate() + n); return d.toISOString().slice(0,10); };
const closureOn = date => S.closures.find(c => c.date === date);
const fmtDate = iso => new Date(iso + 'T12:00:00Z').toLocaleDateString('fr-FR', {weekday:'long', day:'numeric', month:'long', timeZone:'UTC'});
const hh = t => t.replace(':', 'h').replace(/h00$/, 'h');

function status() {
  if (live && S.status === 'pause') return {open:false, pause:true};
  const n = now();
  if (!closureOn(n.date)) {
    const slot = (S.hours[n.dow] || []).find(([a,b]) => n.time >= a && n.time < b);
    if (slot) return {open:true, until: slot[1]};
  }
  return {open:false};
}
function nextSlots(count = 4) {
  const n = now(), out = [];
  for (let i = 0; i < 14 && out.length < count; i++) {
    const date = addDays(n.date, i), dow = (n.dow + i) % 7, c = closureOn(date);
    if (c) { out.push({date, closed:c.reason}); continue; }
    const slots = (S.hours[dow] || []).filter(([a,b]) => i > 0 || b > n.time);
    if (slots.length) out.push({date, slots, today: i === 0});
  }
  return out;
}

/* ---------- validation ---------- */
function validPhone(v) { const s = v.replace(/[\s.\-()]/g, ''); return /^(\+|00)?\d{9,15}$/.test(s); }
function validateHours(h) {
  for (const d of ORDER) {
    const s = [...h[d]].sort();
    for (let i = 0; i < s.length; i++) {
      if (!s[i][0] || !s[i][1] || s[i][0] >= s[i][1]) return `${DAYS[d]} : un créneau a une fin avant son début.`;
      if (i && s[i][0] < s[i-1][1]) return `${DAYS[d]} : deux créneaux se chevauchent.`;
    }
  }
  return '';
}
const hoursText = h => ORDER.map(d => `${DAYS[d]} : ${h[d].length ? h[d].map(([a,b]) => `${hh(a)}–${hh(b)}`).join(' et ') : 'fermé'}`).join('\n');
const closuresText = c => c.length ? c.map(x => `${fmtDate(x.date)}${x.reason ? ' (' + x.reason + ')' : ''}`).join('\n') : 'Aucune';

/* ---------- vues ---------- */
function render() {
  document.querySelectorAll('.tabs button').forEach(b => b.classList.toggle('on', b.dataset.tab === tab && !screen));
  const v = $('#view');
  if (screen) v.innerHTML = views[screen]();
  else v.innerHTML = views[tab]();
  window.scrollTo(0, 0);
}
const views = {
  home() {
    const st = status(), nx = nextSlots();
    return `<div class="status ${st.open ? 'open' : 'closed'}">
      <div class="big">${st.open ? '🟢 OUVERT' : st.pause ? '⏸️ EN PAUSE' : '🔴 FERMÉ'}</div>
      <div class="sub">${st.open ? 'jusqu’à ' + hh(st.until) : st.pause ? 'Standard en pause chez StandardFacile' : 'Les appels reçoivent l’annonce de fermeture'}</div></div>
    <div class="card"><div class="label">Appels transférés vers</div><div class="value">📞 ${esc(S.forward)}</div>
      <button class="btn" data-go="forward">Changer le numéro</button></div>
    <div class="card"><div class="label">Prochains horaires</div>
      ${nx.map(x => `<p style="margin:8px 0"><b>${x.today ? 'Aujourd’hui' : esc(fmtDate(x.date))}</b><br>${
        x.closed !== undefined ? '⛔ Fermeture exceptionnelle' + (x.closed ? ' — ' + esc(x.closed) : '') : x.slots.map(([a,b]) => `${hh(a)} – ${hh(b)}`).join(' · ')}</p>`).join('') || '<p>Aucun horaire à venir.</p>'}
    </div>
    <p class="hint">Heure de La Réunion. Statut calculé d’après les horaires de démonstration.</p>`;
  },
  settings() {
    const row = (go, ic, t, s) => `<button class="row-btn" data-go="${go}"><span class="ic">${ic}</span><span><b>${t}</b><small>${esc(s)}</small></span></button>`;
    return `<h2>Réglages</h2>
      ${row('forward','📞','Numéro de transfert',S.forward)}
      ${row('greeting','👋','Annonce d’accueil',S.greeting.slice(0,60) + '…')}
      ${row('closed','🌙','Annonce de fermeture',S.closed.slice(0,60) + '…')}
      ${row('hours','🕒', live ? 'Horaires d’ouverture' : 'Horaires et fermetures', live ? 'Par jour, plusieurs créneaux' : S.closures.length + ' fermeture(s) exceptionnelle(s)')}
      ${live ? '' : `<div class="card"><label><input type="checkbox" id="fail" ${failNext ? 'checked' : ''} style="width:auto"> Démo : simuler un échec au prochain envoi</label></div>
      <button class="btn sec" id="reset">Réinitialiser les données de démo</button>`}`;
  },
  forward() {
    draft ??= S.forward;
    return back() + `<h2>Numéro de transfert</h2><div class="card">
      <label class="label" for="f">Nouveau numéro</label>
      <input id="f" type="tel" inputmode="tel" value="${esc(draft)}" autocomplete="off">
      <div class="err" id="e"></div>
      <button class="btn" id="go">Voir le récapitulatif</button></div>`;
  },
  greeting() { return textView('greeting', 'Annonce d’accueil', 'Lue pendant les heures d’ouverture.'); },
  closed() { return textView('closed', 'Annonce de fermeture', 'Lue en dehors des horaires et lors des fermetures exceptionnelles.'); },
  hours() {
    draft ??= {hours: structuredClone(S.hours), closures: structuredClone(S.closures)};
    return back() + `<h2>Horaires d’ouverture</h2><div class="card">
      ${ORDER.map(d => `<div class="day"><h3>${DAYS[d]}${draft.hours[d].length ? '' : ' <small class="hint">Fermé</small>'}</h3>
        ${draft.hours[d].map((s,i) => `<div class="slot"><input type="time" data-d="${d}" data-i="${i}" data-k="0" value="${s[0]}">→<input type="time" data-d="${d}" data-i="${i}" data-k="1" value="${s[1]}"><button class="x" data-del="${d}:${i}" aria-label="Supprimer le créneau">✕</button></div>`).join('')}
        <button class="btn sec small" data-add="${d}">+ Créneau</button></div>`).join('')}
      </div>
      ${live ? '<div class="warn">StandardFacile ne gère pas de fermeture par date : pour un jour férié, désactivez le jour concerné ici (puis remettez-le), ou utilisez le bouton pause du site.</div>' : ''}
      <h2 ${live ? 'hidden' : ''}>Fermetures exceptionnelles</h2><div class="card" ${live ? 'hidden' : ''}>
      ${draft.closures.map((c,i) => `<div class="slot"><input type="date" data-c="${i}" data-k="date" value="${c.date}"><input type="text" data-c="${i}" data-k="reason" value="${esc(c.reason)}" placeholder="Motif"><button class="x" data-delc="${i}" aria-label="Supprimer">✕</button></div>`).join('') || '<p class="hint">Aucune.</p>'}
      <button class="btn sec small" id="addc" style="margin-top:8px">+ Ajouter une date</button></div>
      <div class="err" id="e"></div>
      <button class="btn" id="go">Voir le récapitulatif</button>`;
  },
  history() {
    return `<h2>Historique</h2>` + (S.history.length ? S.history.map(h => `<div class="card hist ${h.status}">
      <span class="badge ${h.status === 'failed' ? 'failed' : h.status === 'applied' ? 'applied' : ''}">${h.status === 'failed' ? (live ? '❌ Échec' : '❌ Échec simulé') : h.status === 'applied' ? '✅ Appliqué et vérifié' : '🧪 Simulé — non envoyé'}</span>
      <p style="margin:6px 0 2px"><b>${esc(h.label)}</b></p><div class="hint">${new Date(h.at).toLocaleString('fr-FR', {timeZone: TZ})}</div>
      ${h.error ? `<div class="err">${esc(h.error)}</div>` : ''}</div>`).join('') : '<div class="card">Aucune modification pour l’instant.</div>')
      + `${live ? '' : '<p class="hint">En mode démo, aucune de ces entrées n’a été enregistrée chez StandardFacile.</p>'}`;
  }
};
views.login = () => `<div class="card"><h2>Connexion</h2><label class="label" for="pw">Mot de passe de l’application</label>
  <input id="pw" type="password" autocomplete="current-password"><div class="err" id="e"></div><button class="btn" id="dologin">Se connecter</button></div>`;
const back = () => `<button class="btn sec small" id="back" style="margin-bottom:10px">← Retour</button>`;
function textView(k, title, hint) {
  draft ??= S[k];
  return back() + `<h2>${title}</h2><div class="card"><p class="hint">${hint}</p>
    <textarea id="t">${esc(draft)}</textarea>
    <div class="warn">⚠️ Le format accepté par StandardFacile (texte, fichier audio…) n’est pas encore confirmé. Ici, le texte sert de script de l’annonce.</div>
    <div class="err" id="e"></div><button class="btn" id="go">Voir le récapitulatif</button></div>`;
}

/* ---------- récapitulatif / résultat ---------- */
const META = {
  forward:{label:'Numéro de transfert'}, greeting:{label:'Annonce d’accueil'},
  closed:{label:'Annonce de fermeture'}, hours:{label:'Horaires et fermetures'}
};
function recap(key) {
  let before, after, val;
  if (key === 'forward') { before = S.forward; after = val = draft.trim(); if (!validPhone(after)) return err('Numéro invalide. Exemple : 0692 12 34 56 ou +262 692 12 34 56.'); }
  else if (key === 'hours') {
    const m = validateHours(draft.hours); if (m) return err(m);
    if (draft.closures.some(c => !c.date)) return err('Une fermeture exceptionnelle n’a pas de date.');
    val = draft;
    before = hoursText(S.hours) + (live ? '' : '\n\nFermetures :\n' + closuresText(S.closures));
    after = hoursText(draft.hours) + (live ? '' : '\n\nFermetures :\n' + closuresText(draft.closures));
  } else { before = S[key]; after = val = draft.trim(); if (!after) return err('Le texte ne peut pas être vide.'); }
  if (before === after) return err('Aucun changement à valider.');
  openModal(`<h2>Vérifiez avant de valider</h2><p class="label">${META[key].label}</p>
    <div class="label">Avant</div><div class="diff old">${esc(before)}</div>
    <div class="label">Après</div><div class="diff new">${esc(after)}</div>
    ${live ? '<div class="warn">⚠️ Ce changement sera <b>envoyé pour de vrai</b> à StandardFacile, puis relu pour vérification.</div>' : '<div class="warn">🧪 Démo : ce changement sera <b>simulé</b>. Il ne sera pas envoyé à StandardFacile.</div>'}
    <button class="btn ok" id="confirm">${live ? 'Confirmer et appliquer' : 'Confirmer (simulation)'}</button><button class="btn sec" id="cancel">Annuler</button>`);
  $('#cancel').onclick = closeModal;
  $('#confirm').onclick = () => apply(key, val);
}
const err = m => { const e = $('#e'); if (e) e.textContent = m; };
async function applyLive(key, val) {
  openModal('<div class="result">⏳</div><h2 style="text-align:center">Envoi à StandardFacile…</h2>');
  let ok = true, msg = '';
  try { await api('api/apply', {method:'POST', body: JSON.stringify({key, value: val})}); await loadLive(); }
  catch (e) { ok = false; msg = e.message; if (e.code === 401) { closeModal(); screen = 'login'; return render(); } try { S.history = await api('api/history'); } catch {} }
  openModal(ok
    ? `<div class="result">✅</div><h2 style="text-align:center">Appliqué et vérifié</h2><p>StandardFacile a été relu : la valeur enregistrée correspond à votre demande.</p><button class="btn" id="ok">OK</button>`
    : `<div class="result">❌</div><h2 style="text-align:center">Échec</h2><p>${esc(msg)}</p><p><b>Vérifiez sur StandardFacile l’état réel avant de réessayer.</b></p><button class="btn" id="ok">Fermer</button>`);
  $('#ok').onclick = () => { closeModal(); if (ok) { screen = null; draft = null; tab = 'home'; } render(); };
}
function apply(key, val) {
  if (live) return applyLive(key, val);
  const failed = failNext; failNext = false;
  const entry = {at: Date.now(), label: META[key].label, status: failed ? 'failed' : 'simulated',
    error: failed ? 'Échec simulé : la connexion à StandardFacile n’a pas abouti. Rien n’a été modifié.' : ''};
  S.history.unshift(entry);
  if (!failed) {
    if (key === 'hours') { S.hours = val.hours; S.closures = val.closures.sort((a,b) => a.date.localeCompare(b.date)); } else S[key] = val;
  }
  save();
  openModal(failed
    ? `<div class="result">❌</div><h2 style="text-align:center">Échec</h2><p>${entry.error}</p><button class="btn" id="ok">Réessayer plus tard</button>`
    : `<div class="result">🧪</div><h2 style="text-align:center">Simulé (démo)</h2><p>Le changement est visible dans cette démo uniquement. <b>Il n’a PAS été enregistré chez StandardFacile.</b></p><button class="btn" id="ok">OK</button>`);
  $('#ok').onclick = () => { closeModal(); if (!failed) { screen = null; draft = null; tab = 'home'; } render(); };
}
const openModal = h => { const m = $('#modal'); m.innerHTML = `<div class="sheet">${h}</div>`; m.hidden = false; };
const closeModal = () => { $('#modal').hidden = true; };

/* ---------- événements ---------- */
document.addEventListener('click', e => {
  const t = e.target.closest('button'); if (!t) return;
  if (t.dataset.tab) { tab = t.dataset.tab; screen = null; draft = null; return render(); }
  if (t.dataset.go) { screen = t.dataset.go; draft = null; return render(); }
  if (t.id === 'back') { screen = null; draft = null; tab = 'settings'; return render(); }
  if (t.id === 'go') return recap(screen);
  if (t.id === 'dologin') { const pw = $('#pw').value; return api('api/login', {method:'POST', body: JSON.stringify({password: pw})}).then(() => { screen = null; return loadLive(); }).catch(x => { if (x.code === 401 || x.code === 429) return err(x.message); screen = null; }).then(render); }
  if (t.id === 'reset') { S = structuredClone(DEFAULTS); save(); return render(); }
  if (t.dataset.add !== undefined) { draft.hours[t.dataset.add].push(['11:00','14:00']); return render(); }
  if (t.dataset.del) { const [d,i] = t.dataset.del.split(':'); draft.hours[d].splice(i,1); return render(); }
  if (t.id === 'addc') { draft.closures.push({date: addDays(now().date, 1), reason: ''}); return render(); }
  if (t.dataset.delc) { draft.closures.splice(t.dataset.delc,1); return render(); }
});
document.addEventListener('input', e => {
  const t = e.target;
  if (t.id === 'f' || t.id === 't') draft = t.value;
  else if (t.dataset.d !== undefined) draft.hours[t.dataset.d][t.dataset.i][t.dataset.k] = t.value;
  else if (t.dataset.c !== undefined) draft.closures[t.dataset.c][t.dataset.k] = t.value;
});
document.addEventListener('change', e => { if (e.target.id === 'fail') failNext = e.target.checked; });
boot();
