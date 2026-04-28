/**
 * Caribbean Gospel Radio HD — Store v6
 * Token sent via ?token= query string (most reliable through Apache)
 * Also sent in body as _token and via Authorization header as fallbacks
 */

const API = '/api';
let _tok = localStorage.getItem('crhd_token') || null;

// ── PUBLIC API ──────────────────────────────────────────
async function getConfig() {
  try {
    const r = await fetch(API + '/config', { cache: 'no-store' });
    if (!r.ok) return defaults();
    return await r.json();
  } catch(e) { return defaults(); }
}

async function login(user, pass) {
  const r = await post('/auth/login', { username: user, password: pass });
  if (r.token) { _tok = r.token; localStorage.setItem('crhd_token', r.token); }
  return r;
}
function logout() { _tok = null; localStorage.removeItem('crhd_token'); }
function isLoggedIn() { return !!_tok; }

async function saveSection(sec, data)       { return put('/' + sec, data); }
async function createItem(res, data)        { return post('/' + res, data); }
async function updateItem(res, id, data)    { return put('/' + res + '/' + id, data); }
async function deleteItem(res, id)          { return del('/' + res + '/' + id); }

// ── HTTP ────────────────────────────────────────────────
function tq() { return _tok ? '?token=' + encodeURIComponent(_tok) : ''; }
function hdrs() {
  const h = { 'Content-Type': 'application/json' };
  if (_tok) h['Authorization'] = 'Bearer ' + _tok;
  return h;
}
async function post(path, data) {
  const body = _tok ? { ...data, _token: _tok } : data;
  const r = await fetch(API + path + tq(), { method:'POST', headers:hdrs(), body:JSON.stringify(body), cache:'no-store' });
  return r.json();
}
async function put(path, data) {
  const body = _tok ? { ...data, _token: _tok } : data;
  const r = await fetch(API + path + tq(), { method:'PUT', headers:hdrs(), body:JSON.stringify(body), cache:'no-store' });
  return r.json();
}
async function del(path) {
  const r = await fetch(API + path + tq(), { method:'DELETE', headers:hdrs(), cache:'no-store' });
  return r.json();
}

// ── IMAGE COMPRESSION ───────────────────────────────────
function compressImage(url, maxDim, q) {
  return new Promise(res => {
    if (!url || !url.startsWith('data:image')) { res(url); return; }
    const i = new Image();
    i.onload = () => {
      let w=i.width, h=i.height;
      if (w>maxDim||h>maxDim) { if(w>h){h=Math.round(h*maxDim/w);w=maxDim;}else{w=Math.round(w*maxDim/h);h=maxDim;} }
      const c=document.createElement('canvas'); c.width=w; c.height=h;
      c.getContext('2d').drawImage(i,0,0,w,h);
      res(c.toDataURL('image/jpeg', q||0.88));
    };
    i.onerror = () => res(url);
    i.src = url;
  });
}
function compressImagePNG(url, maxDim) {
  return new Promise(res => {
    if (!url || !url.startsWith('data:image')) { res(url); return; }
    const i = new Image();
    i.onload = () => {
      if (i.width<=maxDim && i.height<=maxDim) { res(url); return; }
      let w=i.width, h=i.height;
      if(w>h){h=Math.round(h*maxDim/w);w=maxDim;}else{w=Math.round(w*maxDim/h);h=maxDim;}
      const c=document.createElement('canvas'); c.width=w; c.height=h;
      c.getContext('2d').drawImage(i,0,0,w,h);
      res(c.toDataURL('image/png'));
    };
    i.onerror = () => res(url);
    i.src = url;
  });
}

// ── DEFAULTS ────────────────────────────────────────────
function defaults() {
  return {
    colors:{primary:'#FFB700',accent:'#CC0000',dark:'#0a0a0a',navBg:'rgba(0,0,0,0.92)',playerBg:'#111111',sectionBg:'#111111',footerBg:'#0d0d0d',text:'#ffffff'},
    hero:{type:'gradient',src:'',overlayOpacity:0.55,headline1:'Praise',headline2:'Worship',headline3:'Word',tagline:'Broadcasting 24/7 Across the Caribbean & Beyond',subline:'The Sound of the Islands · Gospel · Worship · Inspiration'},
    station:{name:'Caribbean Gospel HD',streamUrl:'https://aud1.sjamz.com:8008/stream',timezone:'America/New_York',logo:'',cboxId:'',cboxTag:'',cboxEmbed:''},
    djs:[],schedule:[],downloads:[],socials:[],flags:[],
  };
}

window.CRHD = { getConfig, login, logout, isLoggedIn, saveSection, createItem, updateItem, deleteItem, compressImage, compressImagePNG };
