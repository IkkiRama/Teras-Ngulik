import React, { useEffect, useRef, useState } from 'react';

export type CodeFile = { path: string; content: string };
export const templates: Record<string, CodeFile[]> = {
  html: [{path:'index.html',content:'<main>\n  <h1>Halo, pengulik!</h1>\n  <p>Ide kecilmu bisa jadi sesuatu.</p>\n  <button onclick="document.querySelector(\'p\').textContent = \'Aku bisa membuat website!\'">Coba klik aku</button>\n</main>'},{path:'style.css',content:'body { font-family: system-ui; padding: 32px; background: #F6F8FF; }\nh1 { color: #2F55D4; }\nbutton { background: #FFE7A0; border: 0; padding: 12px 20px; border-radius: 10px; cursor: pointer; }'},{path:'script.js',content:'console.log("Selamat mengulik!");'}],
  javascript:[{path:'main.js',content:'const teman = ["Tera", "Nara", "Kamu"];\nfor (const nama of teman) {\n  console.log(`Halo, ${nama}!`);\n}'}],
  python:[{path:'main.py',content:'data = [12, 18, 21, 15, 24]\nrata_rata = sum(data) / len(data)\nprint(f"Rata-rata: {rata_rata}")'}],
  sql:[{path:'query.sql',content:"CREATE TABLE peserta (nama TEXT, kota TEXT);\nINSERT INTO peserta VALUES ('Tera', 'Bandung'), ('Nara', 'Jakarta'), ('Rani', 'Bandung');\nSELECT kota, COUNT(*) AS jumlah FROM peserta GROUP BY kota;"}],
  r:[{path:'main.r',content:'nilai <- c(72, 85, 91, 78, 88)\nprint(mean(nilai))\nprint(summary(nilai))'}],
};

function safeJSON(value: unknown) { return JSON.stringify(value).replace(/</g,'\\u003c'); }

/** Code runs in an opaque-origin iframe; worker runtimes never see the application session. */
export default function SandboxRunner({files,runtime='html',runKey=0}: {files:CodeFile[]; runtime?:string; runKey?:number}) {
  const frame=useRef<HTMLIFrameElement>(null);
  const [source,setSource]=useState('');
  const [output,setOutput]=useState('Klik Jalankan untuk melihat hasil.');
  const [running,setRunning]=useState(false);
  const activeToken=useRef('');
  const timeout=useRef<ReturnType<typeof setTimeout>|null>(null);
  function stop() { setSource('');setRunning(false);if(timeout.current)clearTimeout(timeout.current); }
  function run() {
    stop(); const token=crypto.randomUUID();activeToken.current=token;setRunning(true);setOutput(runtime==='html'?'Preview dijalankan dalam sandbox.':'Menyiapkan runtime browser…');
    const emit=`const emit = (text, done=false) => parent.postMessage({type:'teras-output',token:${safeJSON(token)},text:String(text),done},'*');`;
    if(runtime==='html') {
      const html=files.find(f=>f.path.endsWith('.html'))?.content??'';
      const css=files.filter(f=>f.path.endsWith('.css')).map(f=>f.content).join('\n');
      const js=files.filter(f=>f.path.endsWith('.js')).map(f=>f.content).join('\n');
      const boot=`${emit} console.log=(...v)=>emit(v.map(x=>typeof x==='object'?JSON.stringify(x):String(x)).join(' ')); window.onerror=(m)=>emit(m); window.addEventListener('load',()=>emit('Preview siap.',true));`;
      setSource(`<!doctype html><html><head><meta charset="utf-8"><meta http-equiv="Content-Security-Policy" content="default-src 'none'; script-src 'unsafe-inline' 'unsafe-eval'; style-src 'unsafe-inline'; img-src data:; connect-src 'none'; form-action 'none'; base-uri 'none'"><script>${boot}</script><style>${css.replace(/<\/style/gi,'<\\/style')}</style></head><body>${html}<script>${js.replace(/<\/script/gi,'<\\/script')}</script></body></html>`);
    } else {
      const code=files.map(f=>f.content).join('\n');
      const workerCode = runtime==='javascript' ? `self.console.log=(...v)=>postMessage({text:v.map(x=>typeof x==='object'?JSON.stringify(x):String(x)).join(' ')}); self.onmessage=async(e)=>{try{await new Function(e.data)();postMessage({text:'Selesai.',done:true});}catch(err){postMessage({text:String(err),done:true});}};` : runtime==='python' ? `importScripts('https://cdn.jsdelivr.net/pyodide/v0.27.7/full/pyodide.js');self.onmessage=async(e)=>{try{const p=await loadPyodide({indexURL:'https://cdn.jsdelivr.net/pyodide/v0.27.7/full/',stdout:x=>postMessage({text:x}),stderr:x=>postMessage({text:x})});await p.runPythonAsync(e.data);postMessage({text:'Selesai.',done:true});}catch(err){postMessage({text:String(err),done:true});}};` : runtime==='sql' ? `importScripts('https://cdn.jsdelivr.net/npm/sql.js@1.13.0/dist/sql-wasm.js');self.onmessage=async(e)=>{try{const SQL=await initSqlJs({locateFile:f=>'https://cdn.jsdelivr.net/npm/sql.js@1.13.0/dist/'+f});const db=new SQL.Database();for(const r of db.exec(e.data))postMessage({text:r.columns.join(' | ')+'\\n'+r.values.map(v=>v.join(' | ')).join('\\n')});db.close();postMessage({text:'Selesai.',done:true});}catch(err){postMessage({text:String(err),done:true});}};` : `self.onmessage=async(e)=>{try{const {WebR,ChannelType}=await import('https://webr.r-wasm.org/v0.4.4/webr.mjs');const r=new WebR({baseUrl:'https://webr.r-wasm.org/v0.4.4/',channelType:ChannelType.PostMessage});await r.init();const out=await r.evalR('capture.output({'+e.data+'\\n})');postMessage({text:(await out.toArray()).join('\\n'),done:true});await r.close();}catch(err){postMessage({text:String(err),done:true});}};`;
      setSource(`<!doctype html><meta charset="utf-8"><meta http-equiv="Content-Security-Policy" content="default-src 'none'; script-src 'unsafe-inline' 'unsafe-eval' blob: https://cdn.jsdelivr.net https://webr.r-wasm.org; worker-src blob: https://webr.r-wasm.org; connect-src https://cdn.jsdelivr.net https://webr.r-wasm.org; style-src 'unsafe-inline'; base-uri 'none'; form-action 'none'"><script>${emit}const worker=new Worker(URL.createObjectURL(new Blob([${safeJSON(workerCode)}],{type:'application/javascript'})));worker.onmessage=e=>emit(e.data.text,e.data.done);worker.onerror=e=>emit('Runtime tidak dapat dimuat: '+e.message,true);worker.postMessage(${safeJSON(code)});</script><p style="font-family:system-ui;color:#64748b;padding:16px">Output tersedia di panel konsol.</p>`);
    }
    timeout.current=setTimeout(()=>{setSource('');setRunning(false);setOutput(o=>o+'\nEksekusi dihentikan setelah 60 detik.');},60000);
  }
  useEffect(()=>{if(runKey)run();},[runKey]);
  useEffect(()=>{function receive(e:MessageEvent){if(e.source!==frame.current?.contentWindow || e.data?.type!=='teras-output' || e.data?.token!==activeToken.current || typeof e.data.text!=='string')return;setOutput(o=>(o.startsWith('Menyiapkan')||o==='Preview dijalankan dalam sandbox.'?'':o+'\n')+e.data.text.slice(0,20000));if(e.data.done){setRunning(false);if(timeout.current)clearTimeout(timeout.current);}}window.addEventListener('message',receive);return()=>{window.removeEventListener('message',receive);if(timeout.current)clearTimeout(timeout.current);};},[]);
  return <div className="sandbox-runner"><div className="sandbox-toolbar"><span className="badge mint">Run dasar gratis</span><div><button type="button" className="btn btn-primary btn-small" onClick={run}>▶ Jalankan</button> <button type="button" className="btn btn-secondary btn-small" onClick={stop}>■ Hentikan</button></div></div>{running&&<p role="status" className="muted">Sedang berjalan…</p>}<iframe ref={frame} title="Hasil kode dalam sandbox terisolasi" sandbox="allow-scripts" srcDoc={source} className={runtime==='html'?'code-preview':'code-preview compact'} /><pre className="console-output" aria-live="polite">{output}</pre>{!['html','javascript'].includes(runtime)&&<p className="muted runtime-note">{runtime==='python'?'Pyodide 0.27.7':runtime==='sql'?'SQLite / sql.js 1.13.0':'WebR 0.4.4'} dimuat dari CDN saat dijalankan. Perlu internet dan memori perangkat yang memadai. Sesi baru untuk setiap Run; paket tambahan, jaringan bebas, dan input interaktif tidak didukung.</p>}</div>;
}
