export type CodeFile = { path: string; content: string };
export type ScrimEvent={t:number;seq:number;type:'replace'|'file_open'|'selection';path:string;content?:string;start?:number;end?:number};
export type ScrimManifest={schema_version:number;initial_files:CodeFile[];duration_ms:number;events:ScrimEvent[];checkpoints?:{t:number;files:Record<string,string>}[]};

/** Rebuild from immutable instructor events. A practicing learner's files are never input. */
export function reconstruct(manifest:ScrimManifest,time:number) {
  let files=Object.fromEntries(manifest.initial_files.map(f=>[f.path,f.content]));let from=-1;let active=manifest.initial_files[0]?.path??'index.html';let selection:{start:number;end:number}|null=null;
  const checkpoint=manifest.checkpoints?.filter(c=>c.t<=time).at(-1);if(checkpoint){files={...checkpoint.files};from=checkpoint.t;}
  // Whole-document replacements are idempotent. Replay the checkpoint timestamp too,
  // because several ordered events can share the same millisecond.
  for(const event of manifest.events){if(event.t>time)break;if(event.type==='file_open')active=event.path;if(event.type==='selection')selection={start:event.start??0,end:event.end??0};if(event.type==='replace'&&event.t>=from)files[event.path]=event.content??'';}
  return {files:Object.entries(files).map(([path,content])=>({path,content})),active,selection};
}
